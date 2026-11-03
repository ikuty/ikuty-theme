#!/bin/bash

# ikuty-theme 自動デプロイスクリプト
# GitHub Actions から SSH 強制コマンド(command=)経由でのみ呼び出される想定。
# 引数は受け取らず、常に dev ブランチの最新テーマを本番へ反映する。

set -euo pipefail

REPO_DIR="$HOME/ikuty-theme"
THEME_REL_PATH="themes/ikuty-theme"
KEEP_GENERATIONS=3

log() {
    echo "[deploy-theme] $1"
}

cd "$REPO_DIR"

log "dev ブランチの最新を取得中..."
git fetch origin dev
git reset --hard origin/dev

VERSION=$(git rev-parse --short HEAD)
TARGET_THEME="ikuty-theme-${VERSION}"
log "デプロイ対象バージョン: ${VERSION}"

# 注: このテーマには sass/ ソースが存在せず、style.css は直接コミットされたものを
# そのまま使う運用のため、ビルドステップは無い(npm run compile:css は sass/ が
# 存在せず実質何もコンパイルしない上、既存style.cssに対するstylelintの非ゼロ終了で
# 誤って失敗扱いになるため使用しない)。

log "テーマを wp_data ボリュームへ配置中(${TARGET_THEME})..."
docker run --rm \
    -v wordpress-infra_wp_data:/var/www/html \
    -v "$(pwd)/${THEME_REL_PATH}:/src:ro" \
    alpine:latest \
    sh -c "
        set -e
        mkdir -p /var/www/html/wp-content/themes
        rm -rf /var/www/html/wp-content/themes/${TARGET_THEME}
        cp -r /src /var/www/html/wp-content/themes/${TARGET_THEME}
        rm -rf /var/www/html/wp-content/themes/${TARGET_THEME}/.git
        rm -rf /var/www/html/wp-content/themes/${TARGET_THEME}/vendor
        rm -rf /var/www/html/wp-content/themes/${TARGET_THEME}/node_modules
    "

log "テーマを有効化中(${TARGET_THEME})..."
cd "$REPO_DIR/infra"
docker compose exec -T wordpress wp theme activate "${TARGET_THEME}" --path=/var/www/html --allow-root

# 静的ファイル(CSS/JS/画像)のURLをデプロイで変えないため、wp-content/ikuty-theme-current に
# 現在の世代へのリンクを張る(テーマ側は functions.php の ikuty_theme_asset_uri() がこのURLを使う)。
# PHPのopcacheとrealpathキャッシュの影響を避けるため、PHPが読むテーマ自体は従来どおり世代ごとのディレクトリ。
# themes/ の外に置くのは、WordPressにテーマとして認識させず、下の世代削除の対象にもしないため。
# リンクの付け替えは、一時リンクを作ってから mv -T で置き換えて、一瞬も欠けないようにする。
log "静的ファイル用のリンクを更新中(ikuty-theme-current -> ${TARGET_THEME})..."
docker run --rm \
    -v wordpress-infra_wp_data:/var/www/html \
    alpine:latest \
    sh -c "
        set -e
        cd /var/www/html/wp-content
        ln -sfn themes/${TARGET_THEME} .ikuty-theme-current.tmp
        mv -fT .ikuty-theme-current.tmp ikuty-theme-current
    "

log "古い世代を削除中(直近 ${KEEP_GENERATIONS} 世代を保持)..."
docker run --rm \
    -v wordpress-infra_wp_data:/var/www/html \
    alpine:latest \
    sh -c "
        cd /var/www/html/wp-content/themes
        ls -dt ikuty-theme-* 2>/dev/null | tail -n +$((KEEP_GENERATIONS + 1)) | xargs -r rm -rf
    "

log "ヘルスチェック中..."
sleep 2
if ! curl -sf -o /dev/null https://ikuty.com/; then
    log "エラー: ヘルスチェックに失敗しました(${TARGET_THEME})"
    exit 1
fi

log "デプロイ完了: ${TARGET_THEME}"
