#!/bin/bash

# 復旧スクリプト
# バックアップファイルからWordPressを復旧します

set -e

# 使用方法を表示
show_usage() {
    echo "Usage: $0 <backup_directory>"
    echo "Example: $0 /backups/wordpress_backup_20241216_120000"
    exit 1
}

# 引数チェック
if [ $# -ne 1 ]; then
    show_usage
fi

BACKUP_PATH="$1"

# バックアップディレクトリの存在確認
if [ ! -d "$BACKUP_PATH" ]; then
    echo "Error: Backup directory not found: $BACKUP_PATH"
    exit 1
fi

# 必要なファイルの存在確認
REQUIRED_FILES=(
    "wordpress_files.tar.gz"
    "database.sql"
    "config_files.tar.gz"
    "ssl_certificates.tar.gz"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$BACKUP_PATH/$file" ]; then
        echo "Error: Required backup file not found: $file"
        exit 1
    fi
done

echo "Starting restore process from: $BACKUP_PATH"

# 確認プロンプト
echo "WARNING: This will overwrite all current WordPress data!"
echo "Are you sure you want to continue? (yes/no)"
read -r confirmation

if [ "$confirmation" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

# 設定ファイルから環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# サービスを停止
echo "Stopping WordPress services..."
docker compose down

# WordPressファイルの復旧
echo "Restoring WordPress files..."
docker run --rm \
    -v wordpress-infra_wp_data:/var/www/html \
    -v $BACKUP_PATH:/backup \
    alpine:latest \
    sh -c "rm -rf /var/www/html/* && tar -xzf /backup/wordpress_files.tar.gz -C /var/www/html"

# MySQLデータベースの復旧
echo "Starting MySQL service for database restore..."
docker compose up -d mysql

# MySQLが完全に起動するまで待機
echo "Waiting for MySQL to be ready..."
sleep 30

# データベースの復旧
echo "Restoring MySQL database..."
docker compose exec -T mysql mysql -u root -p$MYSQL_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS $MYSQL_DATABASE; CREATE DATABASE $MYSQL_DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker compose exec -T mysql mysql -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE < $BACKUP_PATH/database.sql

# SSL証明書の復旧
echo "Restoring SSL certificates..."
docker run --rm \
    -v wordpress-infra_ssl_certs:/etc/letsencrypt \
    -v $BACKUP_PATH:/backup \
    alpine:latest \
    tar -xzf /backup/ssl_certificates.tar.gz -C /etc/letsencrypt

# 設定ファイルの復旧（オプション）
echo "Do you want to restore configuration files? (yes/no)"
echo "WARNING: This will overwrite current nginx, php, mysql configurations"
read -r restore_config

if [ "$restore_config" = "yes" ]; then
    echo "Restoring configuration files..."
    tar -xzf $BACKUP_PATH/config_files.tar.gz
    echo "Configuration files restored."
fi

# すべてのサービスを開始
echo "Starting all services..."
docker compose up -d

# サービスが完全に起動するまで待機
echo "Waiting for services to be ready..."
sleep 30

# nginx設定をリロード
echo "Reloading nginx configuration..."
docker compose exec nginx nginx -s reload

# 復旧完了の確認
echo "Restore process completed!"
echo ""
echo "Verifying services..."
docker compose ps

echo ""
echo "WordPress should now be accessible at: https://$DOMAIN_NAME"
echo "Please verify that all data has been restored correctly."
echo ""
echo "If you encounter any issues, check the logs:"
echo "  docker compose logs nginx"
echo "  docker compose logs wordpress"
echo "  docker compose logs mysql"