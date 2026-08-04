#!/bin/bash

# WordPress Docker Infrastructure - SSL Certificate Renewal
# Let's Encrypt証明書の自動更新と開発環境対応

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

# ログファイルの設定
LOG_DIR="/var/log"
LOG_FILE="$LOG_DIR/ssl-renew.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# ログ関数（ファイルとコンソール両方に出力）
log_to_file() {
    echo "[$DATE] $1" | tee -a "$LOG_FILE" 2>/dev/null || echo "[$DATE] $1"
}

# 設定ファイルから環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# 環境変数の確認
check_environment() {
    if [ -z "$DOMAIN_NAME" ]; then
        log_error "DOMAIN_NAME が設定されていません (.env ファイルを確認してください)"
        exit 1
    fi
    
    # ENVIRONMENTが設定されていない場合はproductionとして扱う
    if [ -z "$ENVIRONMENT" ]; then
        export ENVIRONMENT="production"
        log_warning "ENVIRONMENT が設定されていません。production として扱います"
    fi
}

# 環境モードを確認する関数
check_environment_mode() {
    if [ "$ENVIRONMENT" = "development" ]; then
        return 0  # development
    else
        return 1  # production
    fi
}

# Docker Composeサービスが起動しているかチェック
check_services() {
    if ! docker compose ps nginx | grep -q "Up"; then
        log_error "nginxサービスが起動していません。'docker compose up -d'でサービスを起動してください。"
        return 1
    fi
    
    return 0
}

# 開発環境での証明書確認（更新不要）
handle_development_environment() {
    log_info "開発環境モード: 自己署名証明書は更新不要です"
    
    # 証明書の有効期限を確認
    if [ -f "ssl_certs/live/$DOMAIN_NAME/fullchain.pem" ]; then
        CERT_EXPIRY=$(openssl x509 -enddate -noout -in "ssl_certs/live/$DOMAIN_NAME/fullchain.pem" | cut -d= -f2)
        log_info "現在の証明書有効期限: $CERT_EXPIRY"
        
        # 証明書の発行者を確認
        CERT_ISSUER=$(openssl x509 -issuer -noout -in "ssl_certs/live/$DOMAIN_NAME/fullchain.pem" | sed 's/issuer=//')
        log_info "証明書発行者: $CERT_ISSUER"
        
        log_success "開発環境の自己署名証明書は正常です"
    else
        log_warning "自己署名証明書が見つかりません。./scripts/ssl-init.sh を実行してください"
        return 1
    fi
    
    return 0
}

# Let's Encrypt証明書の更新処理
renew_letsencrypt_certificates() {
    log_info "Let's Encrypt証明書の更新処理を開始..."
    log_to_file "Starting SSL certificate renewal process for domain: $DOMAIN_NAME"

    # certbotサービスが利用可能かチェック
    if ! docker compose config | grep -q "certbot"; then
        log_error "certbotサービスが設定されていません"
        log_to_file "ERROR: certbot service not configured"
        exit 1
    fi

    # certbot renewを実行。--post-hookにより、証明書が更新された場合のみnginxがリロードされる
    log_info "証明書の更新を実行中...（更新が必要な場合のみ）"
    if docker compose run --rm certbot renew --post-hook "docker compose exec -T nginx nginx -s reload"; then
        log_success "証明書の更新処理が正常に完了しました。"
        log_to_file "Certificate renewal process completed. Nginx was reloaded if renewal occurred."
    else
        log_error "証明書の更新処理中にエラーが発生しました。"
        log_to_file "ERROR: An error occurred during the certificate renewal process."
    fi

    # 更新後の証明書情報を確認
    check_certificate_status
}

# 証明書の状態確認
check_certificate_status() {
    log_info "証明書の状態を確認中..."
    
    # ssl_certsディレクトリの証明書を確認
    if [ -f "ssl_certs/live/$DOMAIN_NAME/fullchain.pem" ]; then
        # 有効期限の確認
        CERT_EXPIRY=$(openssl x509 -enddate -noout -in "ssl_certs/live/$DOMAIN_NAME/fullchain.pem" | cut -d= -f2)
        CERT_EXPIRY_EPOCH=$(date -d "$CERT_EXPIRY" +%s 2>/dev/null || echo "0")
        CURRENT_EPOCH=$(date +%s)
        DAYS_REMAINING=$(( (CERT_EXPIRY_EPOCH - CURRENT_EPOCH) / 86400 ))
        
        log_info "証明書有効期限: $CERT_EXPIRY"
        log_to_file "Certificate valid until: $CERT_EXPIRY"
        
        if [ $DAYS_REMAINING -gt 0 ]; then
            if [ $DAYS_REMAINING -le 30 ]; then
                log_warning "証明書の有効期限まで $DAYS_REMAINING 日です（更新推奨）"
                log_to_file "WARNING: Certificate expires in $DAYS_REMAINING days"
            else
                log_success "証明書の有効期限まで $DAYS_REMAINING 日です"
                log_to_file "Certificate expires in $DAYS_REMAINING days"
            fi
        else
            log_error "証明書が期限切れです！"
            log_to_file "ERROR: Certificate has expired!"
            return 1
        fi
        
        # 証明書の発行者を確認
        CERT_ISSUER=$(openssl x509 -issuer -noout -in "ssl_certs/live/$DOMAIN_NAME/fullchain.pem" | sed 's/issuer=//')
        log_info "証明書発行者: $CERT_ISSUER"
        
        # SANs（Subject Alternative Names）を確認
        CERT_SANS=$(openssl x509 -text -noout -in "ssl_certs/live/$DOMAIN_NAME/fullchain.pem" | grep -A1 "Subject Alternative Name" | tail -1 | sed 's/^[[:space:]]*//')
        if [ -n "$CERT_SANS" ]; then
            log_info "対象ドメイン: $CERT_SANS"
        fi
        
    else
        log_error "証明書ファイルが見つかりません: ssl_certs/live/$DOMAIN_NAME/fullchain.pem"
        log_to_file "ERROR: Certificate file not found"
        return 1
    fi
}

# メイン処理
main() {
    echo "=== WordPress Docker Infrastructure - SSL証明書更新 ==="
    echo "🔄 SSL証明書の自動更新とメンテナンス"
    echo ""
    
    # ログディレクトリの作成（権限がある場合のみ）
    if [ -w "$(dirname "$LOG_FILE")" ] || mkdir -p "$(dirname "$LOG_FILE")" 2>/dev/null; then
        log_to_file "SSL certificate renewal script started"
    else
        log_warning "ログファイルに書き込みできません。コンソール出力のみ実行します"
        LOG_FILE="/dev/null"
    fi
    
    # 環境変数の確認
    check_environment
    
    # サービスの確認
    if ! check_services; then
        log_to_file "ERROR: Required services are not running"
        exit 1
    fi
    
    # 環境モードに応じて処理を分岐
    if check_environment_mode; then
        # 開発環境: 自己署名証明書の確認のみ
        handle_development_environment
    else
        # 本番環境: Let's Encrypt証明書の更新
        renew_letsencrypt_certificates
    fi
    
    echo ""
    log_success "=== SSL証明書の更新処理が完了しました ==="
    log_to_file "SSL certificate renewal script completed successfully"
    
    if [ "$ENVIRONMENT" = "development" ]; then
        echo "💻 開発環境: 自己署名証明書は手動更新が必要です"
        echo "   更新が必要な場合: ./scripts/ssl-init.sh"
    else
        echo "🌐 本番環境: Let's Encrypt証明書の自動更新"
        echo "🔒 次回の自動実行: cron設定に従って実行されます"
        echo ""
        echo "📋 cron設定例（毎日午前2時に実行）:"
        echo "   0 2 * * * cd /path/to/wordpress-infra && ./scripts/ssl-renew.sh"
    fi
    
    echo ""
    echo "🔧 便利コマンド:"
    echo "  証明書詳細確認: openssl x509 -in ssl_certs/live/$DOMAIN_NAME/fullchain.pem -text -noout"
    echo "  ログ確認: tail -f $LOG_FILE"
}

# スクリプト実行
main "$@"