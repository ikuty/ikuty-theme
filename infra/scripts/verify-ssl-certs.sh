#!/bin/bash

# SSL証明書検証スクリプト
# 配置された自己署名証明書の状態を確認します

set -e

# 設定ファイルから環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

DOMAIN_NAME=${DOMAIN_NAME:-test.ikuty.com}

echo "=== SSL証明書検証スクリプト ==="
echo "ドメイン名: $DOMAIN_NAME"
echo ""

echo "1. ローカルディレクトリの証明書確認..."
LOCAL_CERT_DIR="./ssl_certs/live/$DOMAIN_NAME"

if [ -d "$LOCAL_CERT_DIR" ]; then
    echo "✓ ローカル証明書ディレクトリが存在します"
    echo "📁 $LOCAL_CERT_DIR"
    echo ""
    echo "ファイル一覧:"
    ls -la "$LOCAL_CERT_DIR" | while read line; do
        echo "  $line"
    done
    echo ""
    
    # 証明書の詳細確認
    if [ -f "$LOCAL_CERT_DIR/fullchain.pem" ]; then
        echo "証明書詳細情報:"
        echo "Subject: $(openssl x509 -in "$LOCAL_CERT_DIR/fullchain.pem" -noout -subject | sed 's/subject=//')"
        echo "Issuer:  $(openssl x509 -in "$LOCAL_CERT_DIR/fullchain.pem" -noout -issuer | sed 's/issuer=//')"
        echo "Valid:   $(openssl x509 -in "$LOCAL_CERT_DIR/fullchain.pem" -noout -dates | grep notBefore | sed 's/notBefore=//')"
        echo "Expires: $(openssl x509 -in "$LOCAL_CERT_DIR/fullchain.pem" -noout -dates | grep notAfter | sed 's/notAfter=//')"
        echo ""
    else
        echo "❌ fullchain.pem が見つかりません"
    fi
else
    echo "❌ ローカル証明書ディレクトリが見つかりません: $LOCAL_CERT_DIR"
fi

echo "2. Dockerボリュームの証明書確認..."

# Dockerボリュームの確認
if docker volume ls | grep -q "wordpress-infra_ssl_certs"; then
    echo "✓ ssl_certsボリュームが存在します"
    
    # ボリューム内の証明書確認
    docker run --rm \
        -v wordpress-infra_ssl_certs:/etc/letsencrypt \
        alpine:latest sh -c "
        if [ -d '/etc/letsencrypt/live/$DOMAIN_NAME' ]; then
            echo '✓ ボリューム内証明書ディレクトリが存在します'
            echo '📁 /etc/letsencrypt/live/$DOMAIN_NAME'
            echo ''
            echo 'ファイル一覧:'
            ls -la /etc/letsencrypt/live/$DOMAIN_NAME | while read line; do
                echo \"  \$line\"
            done
            echo ''
            
            if [ -f '/etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem' ]; then
                echo '証明書詳細情報:'
                echo \"Subject: \$(openssl x509 -in /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem -noout -subject | sed 's/subject=//')\"
                echo \"Issuer:  \$(openssl x509 -in /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem -noout -issuer | sed 's/issuer=//')\"
                echo \"Valid:   \$(openssl x509 -in /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem -noout -dates | grep notBefore | sed 's/notBefore=//')\"
                echo \"Expires: \$(openssl x509 -in /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem -noout -dates | grep notAfter | sed 's/notAfter=//')\"
            else
                echo '❌ fullchain.pem が見つかりません'
            fi
        else
            echo '❌ ボリューム内証明書ディレクトリが見つかりません'
        fi
    "
else
    echo "❌ ssl_certsボリュームが見つかりません"
fi

echo ""
echo "3. Docker Composeでのマウント確認..."

if [ -f "docker-compose.yml" ]; then
    if grep -q "ssl_certs\|letsencrypt" docker-compose.yml; then
        echo "✓ docker-compose.ymlにSSL証明書のマウント設定があります"
        echo ""
        echo "マウント設定:"
        grep -n -A 2 -B 2 "ssl_certs\|letsencrypt" docker-compose.yml | sed 's/^/  /'
    else
        echo "❌ docker-compose.ymlにSSL証明書のマウント設定が見つかりません"
    fi
else
    echo "❌ docker-compose.ymlが見つかりません"
fi

echo ""
echo "4. nginx設定の確認..."

if [ -f "nginx/sites-enabled/virtual.conf" ] || [ -f "nginx/sites-available/wordpress.conf" ]; then
    echo "✓ nginx SSL設定ファイルが存在します"
    
    # SSL設定の確認
    for config_file in nginx/sites-enabled/virtual.conf nginx/sites-available/wordpress.conf; do
        if [ -f "$config_file" ]; then
            echo ""
            echo "📁 $config_file の SSL設定:"
            grep -n "ssl_certificate\|443" "$config_file" | sed 's/^/  /' || echo "  SSL設定が見つかりません"
        fi
    done
else
    echo "❌ nginx SSL設定ファイルが見つかりません"
fi

echo ""
echo "=== 検証完了 ==="
echo ""
echo "次のステップ:"
echo "1. 問題がある場合: ./scripts/setup-self-signed-certs.sh を実行"
echo "2. 環境を起動: docker compose up -d"
echo "3. HTTPS接続テスト: curl -k https://$DOMAIN_NAME"
echo "4. ブラウザアクセス: https://$DOMAIN_NAME"