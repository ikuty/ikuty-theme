#!/bin/bash

# WordPress媒体アップロードテスト
# WP-CLIを使用してメディアファイルをアップロードし、機能をテストします

set -e

echo "Testing WordPress media upload functionality..."

# テスト用画像ファイルを作成
TEST_IMAGE="/tmp/test-image.jpg"
echo "Creating test image file..."
docker compose exec wordpress sh -c "
    convert -size 300x200 xc:lightblue -pointsize 20 -fill black -gravity center -annotate +0+0 'WordPress Test Image' /tmp/test-image.jpg
" 2>/dev/null || {
    # ImageMagickがない場合は簡単なテストファイルを作成
    echo "Creating simple test file..."
    docker compose exec wordpress sh -c "
        echo 'WordPress Media Upload Test' > /tmp/test-file.txt
    "
    TEST_FILE="/tmp/test-file.txt"
}

# アップロード機能をテスト
echo "Testing media upload with WP-CLI..."
if [ "$TEST_FILE" ]; then
    docker compose exec wordpress wp media import /tmp/test-file.txt --path=/var/www/html --allow-root
else
    docker compose exec wordpress wp media import /tmp/test-image.jpg --path=/var/www/html --allow-root
fi

# アップロードされたファイルを確認
echo "Checking uploaded media files..."
docker compose exec wordpress wp media list --path=/var/www/html --allow-root

# アップロードディレクトリの確認
echo "Checking upload directory..."
docker compose exec wordpress ls -la /var/www/html/wp-content/uploads/

# アップロード設定の確認
echo "Checking PHP upload settings..."
docker compose exec wordpress php -i | grep -E "(upload_max_filesize|post_max_size|max_file_uploads)"

# WordPress設定の確認
echo "Checking WordPress upload settings..."
docker compose exec wordpress wp eval "
    echo 'Upload Max Filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;
    echo 'Post Max Size: ' . ini_get('post_max_size') . PHP_EOL;
    echo 'Max File Uploads: ' . ini_get('max_file_uploads') . PHP_EOL;
    echo 'WordPress Upload Directory: ' . wp_upload_dir()['basedir'] . PHP_EOL;
    echo 'WordPress Upload URL: ' . wp_upload_dir()['baseurl'] . PHP_EOL;
" --path=/var/www/html --allow-root

echo "Media upload test completed!"