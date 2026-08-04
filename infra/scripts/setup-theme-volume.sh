#!/bin/bash

# テーマボリューム用セットアップスクリプト
# ikuty-themeをDockerボリュームに配置します

set -e

# 設定ファイルから環境変数を読み込み
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

THEME_REPO="https://github.com/ikuty/ikuty-theme.git"
THEME_BRANCH="dev"
THEME_NAME="ikuty-theme"
TEMP_DIR="./theme-temp-$(date +%s)"

echo "=== テーマボリューム用セットアップスクリプト ==="
echo "リポジトリ: $THEME_REPO"
echo "ブランチ: $THEME_BRANCH"
echo "テーマ名: $THEME_NAME"
echo ""

echo "1. 一時ディレクトリでテーマを取得..."

# 一時ディレクトリの作成
mkdir -p "$TEMP_DIR"

# テーマをクローン
echo "クローン中: $THEME_REPO (branch: $THEME_BRANCH)"
git clone -b "$THEME_BRANCH" "$THEME_REPO" "$TEMP_DIR/$THEME_NAME"

# .gitディレクトリを削除
if [ -d "$TEMP_DIR/$THEME_NAME/.git" ]; then
    echo "Gitディレクトリを削除中..."
    rm -rf "$TEMP_DIR/$THEME_NAME/.git"
fi

echo "2. Dockerボリュームの作成と確認..."

# wp_dataボリュームの作成（存在しない場合）
docker volume create wordpress-infra_wp_data 2>/dev/null || true

# 既存のテーマディレクトリをクリア
echo "既存のテーマディレクトリ内容をクリア中..."
docker run --rm \
    -v wordpress-infra_wp_data:/var/www/html \
    alpine:latest sh -c "mkdir -p /var/www/html/wp-content/themes && rm -rf /var/www/html/wp-content/themes/*"

echo "3. テーマファイルをボリュームに配置..."

# ikuty-theme/themes/ikuty-theme構造を前提とした配置
if [ -d "$TEMP_DIR/$THEME_NAME/themes/ikuty-theme" ]; then
    echo "ikuty-theme/themes/ikuty-theme構造を検出"
    docker run --rm \
        -v wordpress-infra_wp_data:/var/www/html \
        -v $(pwd)/$TEMP_DIR:/temp-themes \
        alpine:latest sh -c "
        echo 'テーマファイルをコピー中...'
        mkdir -p /var/www/html/wp-content/themes
        cp -r /temp-themes/$THEME_NAME/themes/ikuty-theme /var/www/html/wp-content/themes/ikuty-theme
        chmod -R 755 /var/www/html/wp-content/themes/ikuty-theme
        echo 'テーマファイル一覧:'
        ls -la /var/www/html/wp-content/themes/ikuty-theme/ | head -10
        "
else
    echo "❌ ikuty-theme/themes/ikuty-theme構造が見つかりません"
    echo "期待される構造: ikuty-theme/themes/ikuty-theme/"
    echo "実際のディレクトリ構造:"
    ls -la "$TEMP_DIR/$THEME_NAME/"
    if [ -d "$TEMP_DIR/$THEME_NAME/themes" ]; then
        echo "themes/配下の内容:"
        ls -la "$TEMP_DIR/$THEME_NAME/themes/"
    fi
    exit 1
fi

echo "4. テーマ情報の確認..."

# テーマ情報の表示
docker run --rm \
    -v wordpress-infra_wp_data:/var/www/html \
    alpine:latest sh -c "
    if [ -f '/var/www/html/wp-content/themes/ikuty-theme/style.css' ]; then
        echo 'テーマ情報:'
        head -20 /var/www/html/wp-content/themes/ikuty-theme/style.css | grep -E 'Theme Name|Description|Version|Author' || true
    else
        echo '警告: style.css が見つかりません'
        echo 'ディレクトリ内容:'
        ls -la /var/www/html/wp-content/themes/ikuty-theme/ | head -10
    fi
    
    echo ''
    echo 'WordPressテーマディレクトリ一覧:'
    ls -la /var/www/html/wp-content/themes/
    "

echo "5. 一時ファイルの削除..."
rm -rf "$TEMP_DIR"

echo ""
echo "=== テーマボリュームセットアップ完了 ==="
echo ""
echo "配置されたテーマ:"
echo "  📁 Docker volume: wordpress-infra_wp_data"
echo "  📂 パス: /var/www/html/wp-content/themes/ikuty-theme"
echo "  🎨 テーマ名: ikuty-theme"
echo ""
echo "次のステップ:"
echo "1. docker compose up -d でコンテナを起動"
echo "2. WordPress管理画面でテーマを有効化"
echo "3. テーマのカスタマイズ"
echo ""
echo "テーマディレクトリ内容確認:"
echo "  docker run --rm -v wordpress-infra_wp_data:/var/www/html alpine:latest ls -la /var/www/html/wp-content/themes/"
echo ""
echo "WordPress コンテナからのテーマ確認:"
echo "  docker compose exec wordpress ls -la /var/www/html/wp-content/themes/"