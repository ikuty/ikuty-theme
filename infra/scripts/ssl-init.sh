#!/bin/bash

# WordPress Docker Infrastructure - SSL Certificate Initialization
# Let's Encrypt本番環境と開発環境用自己署名証明書の自動切り替え

set -e

# 色付きログ関数
log_info() {
    echo -e "\033[34m[INFO]\033[0m $1"
}

log_success() {
    echo -e "\033[32m[SUCCESS]\033[0m $1"
}

log_warning() {
    echo -e "\033[33m[WARNING]\033[0m $1"
}

log_error() {
    echo -e "\033[31m[ERROR]\033[0m $1"
}

# 設定ファイルから環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# 環境変数の確認
check_environment() {
    log_info "環境変数を確認中..."
    
    if [ -z "$DOMAIN_NAME" ]; then
        log_error "DOMAIN_NAME が設定されていません (.env ファイルを確認してください)"
        exit 1
    fi
    
    if [ -z "$LETSENCRYPT_EMAIL" ]; then
        log_error "LETSENCRYPT_EMAIL が設定されていません (.env ファイルを確認してください)"
        exit 1
    fi
    
    # ENVIRONMENTが設定されていない場合はproductionとして扱う
    if [ -z "$ENVIRONMENT" ]; then
        export ENVIRONMENT="production"
        log_warning "ENVIRONMENT が設定されていません。production として扱います"
    fi
    
    log_success "環境変数の確認完了"
    log_info "ドメイン名: $DOMAIN_NAME"
    log_info "メールアドレス: $LETSENCRYPT_EMAIL"
    log_info "wp_dataボリューム: WordPressファイルを統合管理"
}

# Docker Composeサービスが起動しているかチェック
check_services() {
    echo "サービスの起動状態を確認中..."
    
    # nginxコンテナが起動しているかチェック
    if ! docker compose ps nginx | grep -q "Up"; then
        log_error "nginxサービスが起動していません。'docker compose up -d'でサービスを起動してください。"
        return 1
    fi
    
    # wordpressコンテナが起動しているかチェック
    if ! docker compose ps wordpress | grep -q "Up"; then
        log_error "wordpressサービスが起動していません。'docker compose up -d'でサービスを起動してください。"
        return 1
    fi
    
    log_success "サービスの起動状態確認完了"
    return 0
}

# 環境モードを確認する関数
check_environment_mode() {
    if [ "$ENVIRONMENT" = "development" ]; then
        log_info "開発環境モード: 自己署名証明書を使用します"
        return 0  # development
    else
        log_info "本番環境モード: Let's Encrypt証明書を取得します"
        return 1  # production
    fi
}

# Let's Encrypt証明書の初期化
init_letsencrypt_certificates() {
    log_info "Let's Encrypt証明書の初期化を開始..."
    
    # SSL証明書ディレクトリを作成
    mkdir -p ssl_certs/live/$DOMAIN_NAME
    mkdir -p ssl_certs/archive/$DOMAIN_NAME
    mkdir -p ssl_certs/accounts
    
    # 一時的な自己署名証明書を作成（certbot用）
    log_info "一時的な自己署名証明書を作成中..."
    
    openssl req -x509 -nodes -newkey rsa:2048 \
        -keyout ssl_certs/live/$DOMAIN_NAME/privkey.pem \
        -out ssl_certs/live/$DOMAIN_NAME/fullchain.pem \
        -days 1 \
        -subj "/CN=$DOMAIN_NAME" \
        2>/dev/null
    
    # 証明書の権限設定
    chmod 600 ssl_certs/live/$DOMAIN_NAME/privkey.pem
    chmod 644 ssl_certs/live/$DOMAIN_NAME/fullchain.pem
    
    log_success "一時的な自己署名証明書を作成しました"
    
    # nginxを一度リロードして一時証明書を適用
    log_info "nginx設定を適用中..."
    docker compose exec nginx nginx -s reload || true
    
    # サービスが安定するまで待機
    sleep 10
    
    # Stagingフラグの処理
    STAGING_FLAG=""
    if [ "$1" = "--staging" ]; then
        STAGING_FLAG="--staging"
        log_warning "ステージングモードで実行します。テスト用の証明書が発行されます。"
    fi
    
    # Let's Encrypt証明書を取得
    log_info "Let's Encrypt証明書を取得中... $STAGING_FLAG"
    log_info "ドメイン: $DOMAIN_NAME"
    log_info "メール: $LETSENCRYPT_EMAIL"
    
    if docker compose run --rm certbot certonly \
        --webroot \
        --webroot-path=/var/www/certbot \
        --email $LETSENCRYPT_EMAIL \
        --agree-tos \
        --no-eff-email \
        $STAGING_FLAG \
        -d $DOMAIN_NAME; then
        
        log_success "Let's Encrypt証明書の取得が完了しました"
        
        # nginx設定を確認してからリロード
        log_info "nginx設定をテスト中..."
        if docker compose exec nginx nginx -t; then
            log_info "nginxをリロードしてSSL設定を有効化..."
            docker compose exec nginx nginx -s reload
            
            if [ $? -eq 0 ]; then
                log_success "nginxのリロードが完了しました"
            else
                log_error "nginxのリロードに失敗しました"
                return 1
            fi
        else
            log_error "nginx設定にエラーがあります。リロードをスキップします。"
            return 1
        fi
    else
        log_error "Let's Encrypt証明書の取得に失敗しました"
        log_info "一時的な自己署名証明書でサービスは継続されます"
        return 1
    fi
}

# 自己署名証明書の初期化（開発環境用）
init_self_signed_certificates() {
    log_info "開発環境用自己署名証明書の初期化を開始..."
    
    # 開発環境用自己署名証明書のディレクトリを作成
    mkdir -p ssl_certs/live/$DOMAIN_NAME
    mkdir -p ssl_certs/private
    
    # CA（認証局）用の秘密鍵を作成
    log_info "CA（認証局）証明書を作成中..."
    
    openssl genrsa -out ssl_certs/private/ca-key.pem 4096 2>/dev/null
    
    openssl req -new -x509 -days 365 -key ssl_certs/private/ca-key.pem \
        -out ssl_certs/live/$DOMAIN_NAME/ca-cert.pem \
        -subj "/C=JP/ST=Tokyo/L=Tokyo/O=Development/OU=IT Department/CN=Development CA" \
        2>/dev/null
    
    # サーバー用の秘密鍵を作成
    log_info "サーバー証明書を作成中..."
    
    openssl genrsa -out ssl_certs/live/$DOMAIN_NAME/privkey.pem 4096 2>/dev/null
    
    # サーバー証明書署名要求（CSR）を作成
    openssl req -new -key ssl_certs/live/$DOMAIN_NAME/privkey.pem \
        -out ssl_certs/private/server-csr.pem \
        -subj "/C=JP/ST=Tokyo/L=Tokyo/O=Development/OU=IT Department/CN=$DOMAIN_NAME" \
        2>/dev/null
    
    # CA証明書でサーバー証明書に署名
    openssl x509 -req -days 365 \
        -in ssl_certs/private/server-csr.pem \
        -CA ssl_certs/live/$DOMAIN_NAME/ca-cert.pem \
        -CAkey ssl_certs/private/ca-key.pem \
        -CAcreateserial \
        -out ssl_certs/live/$DOMAIN_NAME/fullchain.pem \
        2>/dev/null
    
    # 証明書の権限設定
    chmod 600 ssl_certs/live/$DOMAIN_NAME/privkey.pem
    chmod 644 ssl_certs/live/$DOMAIN_NAME/fullchain.pem
    chmod 600 ssl_certs/private/ca-key.pem
    
    # 一時ファイルを削除
    rm -f ssl_certs/private/server-csr.pem
    
    log_success "自己署名証明書を作成しました"
    
    # nginx設定を確認してからリロード
    log_info "nginx設定をテスト中..."
    if docker compose exec nginx nginx -t; then
        log_info "nginxをリロードして設定を有効化..."
        docker compose exec nginx nginx -s reload
        
        if [ $? -eq 0 ]; then
            log_success "nginxのリロードが完了しました"
        else
            log_error "nginxのリロードに失敗しました"
            return 1
        fi
    else
        log_error "nginx設定にエラーがあります。設定を確認してください。"
        return 1
    fi
}

# メイン処理
main() {
    echo "=== WordPress Docker Infrastructure - SSL証明書初期化 ==="
    echo "🔒 環境に応じたSSL証明書の自動設定"
    echo ""
    
    # 環境変数の確認
    check_environment
    
    # サービスの確認
    if ! check_services; then
        exit 1
    fi
    
    # 環境モードに応じてSSL証明書を初期化
    if check_environment_mode; then
        # 開発環境: 自己署名証明書
        init_self_signed_certificates
    else
        # 本番環境: Let's Encrypt
        init_letsencrypt_certificates "$@"
    fi
    
    echo ""
    log_success "=== SSL証明書の初期化が完了しました ==="
    echo ""
    echo "WordPressサイト: https://$DOMAIN_NAME"
    echo "WordPress管理画面: https://$DOMAIN_NAME/wp-admin"
    echo "HTTPリダイレクト: http://$DOMAIN_NAME -> https://$DOMAIN_NAME"
    echo ""
    if [ "$ENVIRONMENT" = "development" ]; then
        echo "💻 開発環境: 自己署名証明書を使用中"
        echo "⚠️  ブラウザでセキュリティ警告が表示されますが、"
        echo "    「詳細設定」から「危険を承知でアクセスする」でアクセスできます。"
    else
        echo "🌐 本番環境: Let's Encryptの有効な証明書でHTTPSアクセスが可能です。"
        echo "🔒 SSL証明書の自動更新設定: ./scripts/ssl-renew.sh"
    fi
    echo ""
    echo "📁 WordPressデータ管理:"
    echo "  テーマ: wp_dataボリューム/wp-content/themes/"
    echo "  プラグイン: wp_dataボリューム/wp-content/plugins/"
    echo "  アップロード: wp_dataボリューム/wp-content/uploads/"
    echo ""
    echo "🔧 便利コマンド:"
    echo "  証明書確認: openssl x509 -in ssl_certs/live/$DOMAIN_NAME/fullchain.pem -text -noout"
    echo "  プラグイン管理: ./scripts/manage-plugins-php.sh --status"
}

# スクリプト実行
main "$@"