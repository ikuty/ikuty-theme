<?php
/**
 * Logger Class - 色付きログ出力機能
 * Bash版プラグイン管理スクリプトと同等のログ機能を提供
 */

class Logger
{
    // ANSI カラーコード定義
    private const COLORS = [
        'info'    => "\033[34m",  // 青
        'success' => "\033[32m",  // 緑
        'warning' => "\033[33m",  // 黄
        'error'   => "\033[31m",  // 赤
        'reset'   => "\033[0m"    // リセット
    ];

    /**
     * 情報ログを出力
     *
     * @param string $message ログメッセージ
     */
    public static function info(string $message): void
    {
        self::log('INFO', $message, 'info');
    }

    /**
     * 成功ログを出力
     *
     * @param string $message ログメッセージ
     */
    public static function success(string $message): void
    {
        self::log('SUCCESS', $message, 'success');
    }

    /**
     * 警告ログを出力
     *
     * @param string $message ログメッセージ
     */
    public static function warning(string $message): void
    {
        self::log('WARNING', $message, 'warning');
    }

    /**
     * エラーログを出力
     *
     * @param string $message ログメッセージ
     */
    public static function error(string $message): void
    {
        self::log('ERROR', $message, 'error');
    }

    /**
     * デバッグログを出力（環境変数で制御）
     *
     * @param string $message ログメッセージ
     */
    public static function debug(string $message): void
    {
        if (getenv('DEBUG') === 'true') {
            self::log('DEBUG', $message, 'info');
        }
    }

    /**
     * ログを色付きで出力
     *
     * @param string $level ログレベル
     * @param string $message ログメッセージ
     * @param string $color カラー種別
     */
    private static function log(string $level, string $message, string $color): void
    {
        $colorCode = self::COLORS[$color] ?? '';
        $resetCode = self::COLORS['reset'];
        
        // カラー出力が無効な場合はカラーコードを除去
        if (!self::isColorOutputEnabled()) {
            $colorCode = '';
            $resetCode = '';
        }
        
        echo "{$colorCode}[{$level}]{$resetCode} {$message}" . PHP_EOL;
    }

    /**
     * カラー出力が有効かどうかを判定
     *
     * @return bool カラー出力有効フラグ
     */
    private static function isColorOutputEnabled(): bool
    {
        // 環境変数でカラー出力を制御
        $noColor = getenv('NO_COLOR');
        if ($noColor !== false && $noColor !== '') {
            return false;
        }

        // 強制カラー出力の場合
        if (getenv('FORCE_COLOR') === 'true') {
            return true;
        }

        // ターミナルかどうかを判定
        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }

    /**
     * プログレスバーを表示
     *
     * @param int $current 現在の進捗
     * @param int $total 総数
     * @param string $prefix プレフィックス
     */
    public static function progress(int $current, int $total, string $prefix = ''): void
    {
        $percent = round(($current / $total) * 100);
        $bar = str_repeat('=', intval($percent / 2)) . str_repeat('-', 50 - intval($percent / 2));
        
        $output = "\r{$prefix}[{$bar}] {$percent}% ({$current}/{$total})";
        echo $output;
        
        if ($current === $total) {
            echo PHP_EOL;
        }
    }

    /**
     * セクション区切りを表示
     *
     * @param string $title セクションタイトル
     */
    public static function section(string $title): void
    {
        $separator = str_repeat('=', strlen($title) + 8);
        echo PHP_EOL;
        self::info($separator);
        self::info("=== {$title} ===");
        self::info($separator);
        echo PHP_EOL;
    }
}