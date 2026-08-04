#!/bin/bash

# バックアップスクリプト
# WordPress、MySQL、設定ファイル、SSL証明書のバックアップを行います

set -e

# 設定
BACKUP_DIR="/tmp/backups"
DATE=$(date '+%Y%m%d_%H%M%S')
BACKUP_NAME="wordpress_backup_$DATE"
BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"

# 設定ファイルから環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# バックアップディレクトリの作成
mkdir -p $BACKUP_PATH

echo "Starting backup process: $BACKUP_NAME"

# WordPressファイルのバックアップ
echo "Backing up WordPress files..."
docker run --rm \
    -v wordpress-infra_wp_data:/var/www/html \
    -v $(pwd)/$BACKUP_PATH:/backup \
    alpine:latest \
    tar -czf /backup/wordpress_files.tar.gz -C /var/www/html .

# MySQLデータベースのバックアップ
echo "Backing up MySQL database..."
docker compose exec -T mysql mysqldump \
    -u root -p$MYSQL_ROOT_PASSWORD \
    --single-transaction \
    --routines \
    --triggers \
    $MYSQL_DATABASE > $BACKUP_PATH/database.sql

# 設定ファイルのバックアップ
echo "Backing up configuration files..."
tar -czf $BACKUP_PATH/config_files.tar.gz \
    nginx/ php/ mysql/ scripts/ \
    docker-compose.yml .env

# SSL証明書のバックアップ
echo "Backing up SSL certificates..."
docker run --rm \
    -v wordpress-infra_ssl_certs:/etc/letsencrypt \
    -v $(pwd)/$BACKUP_PATH:/backup \
    alpine:latest \
    tar -czf /backup/ssl_certificates.tar.gz -C /etc/letsencrypt .

# バックアップ情報ファイルの作成
echo "Creating backup information file..."
cat > $BACKUP_PATH/backup_info.txt << EOF
Backup created: $(date)
Domain: $DOMAIN_NAME
MySQL Database: $MYSQL_DATABASE
MySQL User: $MYSQL_USER
Backup includes:
- WordPress files (wordpress_files.tar.gz)
- MySQL database (database.sql)
- Configuration files (config_files.tar.gz)
- SSL certificates (ssl_certificates.tar.gz)
EOF

# バックアップファイルのサイズを表示
echo "Backup completed successfully!"
echo "Backup location: $BACKUP_PATH"
echo "Backup size:"
du -sh $BACKUP_PATH
echo ""
echo "Backup contents:"
ls -la $BACKUP_PATH

# 古いバックアップファイルの削除（30日以上古いもの）
echo "Cleaning up old backups (older than 30 days)..."
find $BACKUP_DIR -type d -name "wordpress_backup_*" -mtime +30 -exec rm -rf {} \; 2>/dev/null || true

echo "Backup process completed successfully!"