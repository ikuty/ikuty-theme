#!/bin/bash

# 自己署名証明書配置スクリプト
# ssl_certsボリュームに開発用自己署名証明書を配置します

set -e

# 設定ファイルから環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# デフォルト値設定
DOMAIN_NAME=${DOMAIN_NAME:-test.ikuty.com}
COUNTRY=${COUNTRY:-JP}
STATE=${STATE:-Tokyo}
CITY=${CITY:-Machida}
ORGANIZATION=${ORGANIZATION:-ikuty}
ORG_UNIT=${ORG_UNIT:-ikuty}
EMAIL=${LETSENCRYPT_EMAIL:-admin@ikuty.com}

echo "=== 自己署名証明書配置スクリプト ==="
echo "ドメイン名: $DOMAIN_NAME"
echo "組織名: $ORGANIZATION"
echo ""

# 一時ディレクトリ作成
TEMP_DIR="./ssl-temp-$(date +%s)"
mkdir -p $TEMP_DIR

echo "1. CA（認証局）証明書の作成..."

# CA秘密鍵の生成
openssl genrsa -out $TEMP_DIR/ca-privatekey.pem 2048

# CA証明書署名要求（CSR）の作成
openssl req -new -key $TEMP_DIR/ca-privatekey.pem -out $TEMP_DIR/ca-csr.pem \
    -subj "/C=$COUNTRY/ST=$STATE/L=$CITY/O=$ORGANIZATION/OU=$ORG_UNIT/CN=$ORGANIZATION/emailAddress=$EMAIL"

# CA証明書の作成（自己署名）
openssl x509 -req -days 3650 -in $TEMP_DIR/ca-csr.pem \
    -signkey $TEMP_DIR/ca-privatekey.pem \
    -out $TEMP_DIR/ca-crt.pem

echo "2. ドメイン証明書の作成..."

# ドメイン用秘密鍵の生成
openssl genrsa -out $TEMP_DIR/privkey.pem 2048

# ドメイン証明書署名要求（CSR）の作成
openssl req -new -key $TEMP_DIR/privkey.pem -out $TEMP_DIR/csr.pem \
    -subj "/C=$COUNTRY/ST=$STATE/L=$CITY/O=$ORGANIZATION/OU=$ORG_UNIT/CN=$DOMAIN_NAME/emailAddress=$EMAIL"

# ドメイン証明書の作成（CA署名）
openssl x509 -req -days 365 -in $TEMP_DIR/csr.pem \
    -CA $TEMP_DIR/ca-crt.pem -CAkey $TEMP_DIR/ca-privatekey.pem \
    -CAcreateserial -out $TEMP_DIR/cert.pem

# フルチェーン証明書の作成（ドメイン証明書 + CA証明書）
cat $TEMP_DIR/cert.pem $TEMP_DIR/ca-crt.pem > $TEMP_DIR/fullchain.pem

echo "3. ssl_certsボリュームへの配置..."

# Dockerボリュームの作成（存在しない場合）
docker volume create wordpress-infra_ssl_certs 2>/dev/null || true

# Dockerボリュームに証明書ファイルをコピー
docker run --rm \
    -v wordpress-infra_ssl_certs:/etc/letsencrypt \
    -v $(pwd)/$TEMP_DIR:/ssl-temp \
    alpine:latest sh -c "
    echo 'Creating directory structure...'
    mkdir -p /etc/letsencrypt/live/$DOMAIN_NAME
    
    echo 'Copying certificate files...'
    cp /ssl-temp/fullchain.pem /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem
    cp /ssl-temp/privkey.pem /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem
    cp /ssl-temp/ca-crt.pem /etc/letsencrypt/live/$DOMAIN_NAME/ca-crt.pem
    cp /ssl-temp/ca-privatekey.pem /etc/letsencrypt/live/$DOMAIN_NAME/ca-privatekey.pem
    cp /ssl-temp/ca-csr.pem /etc/letsencrypt/live/$DOMAIN_NAME/ca-csr.pem
    cp /ssl-temp/csr.pem /etc/letsencrypt/live/$DOMAIN_NAME/csr.pem
    
    echo 'Setting file permissions...'
    chmod 600 /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem
    chmod 600 /etc/letsencrypt/live/$DOMAIN_NAME/ca-privatekey.pem
    chmod 644 /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem
    chmod 644 /etc/letsencrypt/live/$DOMAIN_NAME/ca-crt.pem
    chmod 644 /etc/letsencrypt/live/$DOMAIN_NAME/ca-csr.pem
    chmod 644 /etc/letsencrypt/live/$DOMAIN_NAME/csr.pem
    
    echo 'Certificate files created successfully:'
    ls -la /etc/letsencrypt/live/$DOMAIN_NAME/
"

echo "4. ローカルディレクトリへのバックアップ..."

# ローカルのssl_certsディレクトリにもコピー
mkdir -p ./ssl_certs/live/$DOMAIN_NAME
cp $TEMP_DIR/fullchain.pem ./ssl_certs/live/$DOMAIN_NAME/
cp $TEMP_DIR/privkey.pem ./ssl_certs/live/$DOMAIN_NAME/
cp $TEMP_DIR/ca-crt.pem ./ssl_certs/live/$DOMAIN_NAME/
cp $TEMP_DIR/ca-privatekey.pem ./ssl_certs/live/$DOMAIN_NAME/
cp $TEMP_DIR/ca-csr.pem ./ssl_certs/live/$DOMAIN_NAME/
cp $TEMP_DIR/csr.pem ./ssl_certs/live/$DOMAIN_NAME/

# 適切な権限設定
chmod 600 ./ssl_certs/live/$DOMAIN_NAME/privkey.pem
chmod 600 ./ssl_certs/live/$DOMAIN_NAME/ca-privatekey.pem
chmod 644 ./ssl_certs/live/$DOMAIN_NAME/fullchain.pem
chmod 644 ./ssl_certs/live/$DOMAIN_NAME/ca-crt.pem
chmod 644 ./ssl_certs/live/$DOMAIN_NAME/ca-csr.pem
chmod 644 ./ssl_certs/live/$DOMAIN_NAME/csr.pem

# CA証明書シリアル番号ファイルもコピー
if [ -f $TEMP_DIR/ca-crt.srl ]; then
    cp $TEMP_DIR/ca-crt.srl ./ssl_certs/live/$DOMAIN_NAME/
    chmod 644 ./ssl_certs/live/$DOMAIN_NAME/ca-crt.srl
fi

echo "5. 一時ファイルの削除..."
rm -rf $TEMP_DIR

echo ""
echo "=== 自己署名証明書の配置が完了しました ==="
echo ""
echo "配置された証明書ファイル:"
echo "📁 Dockerボリューム: wordpress-infra_ssl_certs"
echo "📁 ローカルディレクトリ: ./ssl_certs/live/$DOMAIN_NAME/"
echo ""
echo "証明書ファイル一覧:"
echo "  ✓ fullchain.pem       - 完全な証明書チェーン"
echo "  ✓ privkey.pem         - ドメイン秘密鍵"
echo "  ✓ ca-crt.pem          - CA証明書"
echo "  ✓ ca-privatekey.pem   - CA秘密鍵"
echo "  ✓ ca-csr.pem          - CA証明書署名要求"
echo "  ✓ csr.pem             - ドメイン証明書署名要求"
echo ""
echo "次のステップ:"
echo "1. docker compose up -d で環境を起動"
echo "2. https://$DOMAIN_NAME でアクセス"
echo "3. ブラウザの証明書警告を承認"
echo ""
echo "証明書情報の確認:"
echo "  openssl x509 -in ./ssl_certs/live/$DOMAIN_NAME/fullchain.pem -text -noout"
echo "