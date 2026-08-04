#!/bin/bash

# ikuty-theme セットアップスクリプト
# ボリューム方式でテーマを配置します

set -e

# 使用方法を表示
show_usage() {
    echo "使用方法: $0 [OPTIONS]"
    echo ""
    echo "オプション:"
    echo "  --help      このヘルプを表示"
    echo ""
    echo "このスクリプトはボリューム方式でテーマをセットアップします。"
}

# パラメータ解析
for arg in "$@"; do
    case $arg in
        --help)
            show_usage
            exit 0
            ;;
        *)
            echo "不明なオプション: $arg"
            show_usage
            exit 1
            ;;
    esac
done

echo "=== ikuty-theme セットアップスクリプト ==="
echo "ボリューム方式でセットアップを実行中..."

# ボリューム専用スクリプトを実行
exec ./scripts/setup-theme-volume.sh