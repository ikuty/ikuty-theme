#!/usr/bin/env php
<?php
/**
 * WordPress プラグイン管理スクリプト（PHP版）
 * plugins.yml に定義されたプラグインのみがインストールされた状態を維持
 */

// エラー報告を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// クラスファイルを読み込み
require_once __DIR__ . '/php-classes/Logger.php';
require_once __DIR__ . '/php-classes/PluginConfig.php';
require_once __DIR__ . '/php-classes/WPCLIWrapper.php';
require_once __DIR__ . '/php-classes/PluginManager.php';

/**
 * メイン関数
 */
function main(): void
{
    global $argc, $argv;

    // 引数チェック
    if ($argc < 2) {
        showUsage();
        exit(1);
    }

    $command = $argv[1];

    // ヘルプ表示
    if ($command === '--help' || $command === '-h') {
        showUsage();
        exit(0);
    }

    // 設定ファイルのパス
    $configPath = dirname(__DIR__) . '/plugins.yml';

    try {
        // プラグイン管理インスタンス作成
        $pluginManager = new PluginManager($configPath);

        // コマンド実行
        switch ($command) {
            case '--sync':
                $pluginManager->syncPlugins();
                break;

            case '--status':
                $pluginManager->showPluginStatus();
                break;

            case '--install':
                $pluginManager->installPlugins();
                break;

            case '--cleanup':
                $pluginManager->cleanupPlugins();
                break;

            case '--update-settings':
                $pluginManager->updateAutoUpdateSettings();
                break;

            case '--activate-plugins':
                $pluginManager->activatePlugins();
                break;

            case '--update-versions':
                $pluginManager->updateVersions();
                break;

            default:
                Logger::error("不明なオプション: {$command}");
                showUsage();
                exit(1);
        }

    } catch (Exception $e) {
        Logger::error("実行中にエラーが発生しました: " . $e->getMessage());
        if (getenv('DEBUG') === 'true') {
            Logger::error("スタックトレース:\n" . $e->getTraceAsString());
        }
        exit(1);
    }
}

/**
 * 使用方法を表示
 */
function showUsage(): void
{
    echo "使用方法: php " . basename(__FILE__) . " [OPTIONS]\n\n";
    echo "オプション:\n";
    echo "  --sync             plugins.ymlに基づいてプラグインを同期\n";
    echo "  --status           現在のプラグインの状態を表示\n";
    echo "  --install          plugins.ymlのプラグインをインストール\n";
    echo "  --cleanup          不要なプラグインを削除\n";
    echo "  --update-settings  auto-update設定のみを更新\n";
    echo "  --activate-plugins active設定のみを更新\n";
    echo "  --update-versions  バージョン設定のみを更新\n";
    echo "  --help             このヘルプを表示\n\n";
    echo "例:\n";
    echo "  php " . basename(__FILE__) . " --sync     # 完全同期（推奨）\n";
    echo "  php " . basename(__FILE__) . " --status   # 状態確認のみ\n\n";
    echo "環境変数:\n";
    echo "  DEBUG=true         デバッグ情報を表示\n";
    echo "  NO_COLOR=1         カラー出力を無効化\n";
    echo "  FORCE_COLOR=true   カラー出力を強制有効化\n";
}

/**
 * シグナルハンドラーの設定
 */
function setupSignalHandlers(): void
{
    // SIGINT (Ctrl+C) のシグナルハンドラーを設定
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGINT, function($signo) {
            Logger::error("\n処理が中断されました");
            exit(1);
        });
    }
}

/**
 * エラーハンドラーの設定
 */
function setupErrorHandlers(): void
{
    // 未捕捉例外ハンドラー
    set_exception_handler(function($exception) {
        Logger::error("未捕捉例外: " . $exception->getMessage());
        if (getenv('DEBUG') === 'true') {
            Logger::error("ファイル: " . $exception->getFile() . ":" . $exception->getLine());
            Logger::error("スタックトレース:\n" . $exception->getTraceAsString());
        }
        exit(1);
    });

    // PHP エラーハンドラー
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorType = match($severity) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => 'FATAL ERROR',
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'WARNING',
            E_NOTICE, E_USER_NOTICE => 'NOTICE',
            E_STRICT => 'STRICT',
            E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
            default => 'UNKNOWN ERROR'
        };
        
        Logger::error("{$errorType}: {$message} in {$file} on line {$line}");
        
        // FATAL エラーの場合は終了
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            exit(1);
        }
        
        return true;
    });

    // シャットダウン関数（FATALエラー検出用）
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
            Logger::error("FATAL ERROR: {$error['message']} in {$error['file']} on line {$error['line']}");
        }
    });
}

// スクリプトが直接実行された場合のみメイン処理を実行
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    // エラーハンドリングとシグナルハンドリングを設定
    setupErrorHandlers();
    setupSignalHandlers();
    
    // メイン処理を実行
    main();
}