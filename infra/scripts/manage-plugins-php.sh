#!/bin/bash

# PHP版プラグイン管理スクリプトの実行ラッパー
# manage-plugins.php を Docker コンテナ内で実行

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

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

# 使用方法を表示
show_usage() {
    echo "使用方法: $0 [OPTIONS]"
    echo ""
    echo "オプション:"
    echo "  --sync             plugins.ymlに基づいてプラグインを同期"
    echo "  --status           現在のプラグインの状態を表示"
    echo "  --install          plugins.ymlのプラグインをインストール"
    echo "  --cleanup          不要なプラグインを削除"
    echo "  --update-settings  auto-update設定のみを更新"
    echo "  --activate-plugins active設定のみを更新"
    echo "  --update-versions  バージョン設定のみを更新"
    echo "  --help             このヘルプを表示"
    echo ""
    echo "例:"
    echo "  $0 --sync     # 完全同期（推奨）"
    echo "  $0 --status   # 状態確認のみ"
    echo ""
    echo "環境変数:"
    echo "  DEBUG=true         デバッグ情報を表示"
    echo "  NO_COLOR=1         カラー出力を無効化"
    echo "  FORCE_COLOR=true   カラー出力を強制有効化"
    echo ""
    echo "注意: これはPHP版プラグイン管理スクリプトのDockerラッパーです"
}

# Docker Compose がWordPressサービスを起動しているかチェック
check_wordpress_service() {
    if ! docker compose ps wordpress | grep -q "Up"; then
        log_error "WordPressサービスが起動していません"
        log_info "まず 'docker compose up -d' でサービスを起動してください"
        return 1
    fi
    return 0
}

# PHP環境チェック
check_php_environment() {
    log_info "PHP環境をチェック中..."
    
    # PHPの存在確認
    if ! docker compose exec -T wordpress php --version >/dev/null 2>&1; then
        log_error "WordPressコンテナにPHPがインストールされていません"
        return 1
    fi
    
    # スクリプトファイルの存在確認
    if ! docker compose exec -T wordpress test -f /var/www/html/scripts/php-scripts/manage-plugins.php; then
        log_error "PHP版プラグイン管理スクリプトが見つかりません"
        log_info "スクリプトファイルがコンテナにマウントされているか確認してください"
        return 1
    fi
    
    log_success "PHP環境チェック完了"
    return 0
}

# WP-CLI動作チェック
check_wpcli() {
    log_info "WP-CLI動作をチェック中..."
    
    # 標準のwpコマンドをテスト
    if docker compose exec -T wordpress wp --version --allow-root >/dev/null 2>&1; then
        log_success "標準のwpコマンドが利用可能です"
        return 0
    fi
    
    # php wp-cli.phar をテスト
    if docker compose exec -T wordpress test -f /wp-cli.phar && docker compose exec -T wordpress php /wp-cli.phar --version --allow-root >/dev/null 2>&1; then
        log_success "php wp-cli.pharコマンドが利用可能です"
        return 0
    fi
    
    log_warning "WP-CLIの動作に問題があります。PHP版スクリプト内で自己解決を試みます"
    return 0  # 継続する（スクリプト内でWP-CLIパスを調整）
}

# スクリプトをコンテナにコピー
copy_scripts_to_container() {
    log_info "PHPスクリプトファイルをコンテナにコピー中..."
    
    # scriptsディレクトリ全体をコピー
    docker compose exec -T wordpress mkdir -p /var/www/html/scripts/php-scripts/php-classes
    
    # メインスクリプト
    docker cp "$SCRIPT_DIR/php-scripts/manage-plugins.php" wordpress-app:/var/www/html/scripts/php-scripts/
    
    # クラスファイル
    docker cp "$SCRIPT_DIR/php-scripts/php-classes/Logger.php" wordpress-app:/var/www/html/scripts/php-scripts/php-classes/
    docker cp "$SCRIPT_DIR/php-scripts/php-classes/PluginConfig.php" wordpress-app:/var/www/html/scripts/php-scripts/php-classes/
    docker cp "$SCRIPT_DIR/php-scripts/php-classes/WPCLIWrapper.php" wordpress-app:/var/www/html/scripts/php-scripts/php-classes/
    docker cp "$SCRIPT_DIR/php-scripts/php-classes/PluginManager.php" wordpress-app:/var/www/html/scripts/php-scripts/php-classes/
    
    # 設定ファイル
    docker cp "$SCRIPT_DIR/plugins.yml" wordpress-app:/var/www/html/scripts/
    
    # 実行権限を設定
    docker compose exec -T wordpress chmod +x /var/www/html/scripts/php-scripts/manage-plugins.php
    
    log_success "PHPスクリプトファイルのコピーが完了しました"
}

# メイン処理
main() {
    # 引数チェック
    if [ $# -eq 0 ]; then
        show_usage
        exit 1
    fi
    
    # ヘルプ表示
    if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
        show_usage
        exit 0
    fi
    
    # 前提条件チェック
    if ! check_wordpress_service; then
        exit 1
    fi
    
    # plugins.ymlファイルの存在確認
    PLUGINS_CONFIG_PATH="$SCRIPT_DIR/plugins.yml"
    if [ ! -f "$PLUGINS_CONFIG_PATH" ]; then
        log_error "plugins.yml が見つかりません: $PLUGINS_CONFIG_PATH"
        exit 1
    fi
    
    # スクリプトをコンテナにコピー
    copy_scripts_to_container
    
    if ! check_php_environment; then
        exit 1
    fi
    
    # WP-CLI動作チェック（警告レベル）
    check_wpcli
    
    # PHP版スクリプトを実行
    log_info "PHP版プラグイン管理スクリプトを実行中..."
    
    # 環境変数の設定
    ENV_CMD="docker compose exec -T"
    if [ "$DEBUG" = "true" ]; then
        ENV_CMD="$ENV_CMD -e DEBUG=true"
    fi
    if [ -n "$NO_COLOR" ]; then
        ENV_CMD="$ENV_CMD -e NO_COLOR=$NO_COLOR"
    fi
    if [ "$FORCE_COLOR" = "true" ]; then
        ENV_CMD="$ENV_CMD -e FORCE_COLOR=true"
    fi
    ENV_CMD="$ENV_CMD wordpress php /var/www/html/scripts/php-scripts/manage-plugins.php"
    
    # コンテナ内でPHPスクリプトを実行
    if $ENV_CMD "$@"; then
        log_success "PHP版プラグイン管理スクリプトの実行が完了しました"
    else
        log_error "PHP版プラグイン管理スクリプトの実行中にエラーが発生しました"
        exit 1
    fi
}

# スクリプト実行
main "$@"