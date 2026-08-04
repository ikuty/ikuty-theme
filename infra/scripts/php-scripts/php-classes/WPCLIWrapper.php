<?php
/**
 * WPCLIWrapper Class - WP-CLI実行ラッパー
 * WordPressコンテナ内でWP-CLIコマンドを実行する機能を提供
 */

require_once __DIR__ . '/Logger.php';

class WPCLIWrapper
{
    private string $wpPath;
    private string $wpCliPath;

    /**
     * コンストラクタ
     *
     * @param string $wpPath WordPressのパス
     * @param string $wpCliPath WP-CLIのパス
     */
    public function __construct(string $wpPath = '/var/www/html', string $wpCliPath = 'wp')
    {
        $this->wpPath = $wpPath;
        $this->wpCliPath = "/usr/local/bin/wp";
    }

    /**
     * WP-CLIコマンドを実行
     *
     * @param array $command コマンド配列
     * @param bool $returnOutput 出力を返すかどうか
     * @return array [success, output, error]
     */
    public function execute(array $command, bool $returnOutput = true): array
    {
        // WP-CLIのパスを適切に構築
        if (strpos($this->wpCliPath, 'php /wp-cli.phar') === 0) {
            // php /wp-cli.phar の場合
            $baseCommand = [
                'php',
                '/wp-cli.phar',
                '--path=' . $this->wpPath,
                '--allow-root'
            ];
        } else {
            // その他の場合
            $baseCommand = [
                $this->wpCliPath,
                '--path=' . $this->wpPath,
                '--allow-root'
            ];
        }
        
        $fullCommand = array_merge($baseCommand, $command);
        $commandString = implode(' ', array_map('escapeshellarg', $fullCommand));
        
        Logger::debug("WP-CLI実行: {$commandString}");
        
        $output = [];
        $returnCode = 0;
        
        if ($returnOutput) {
            exec($commandString . ' 2>&1', $output, $returnCode);
            $outputString = implode("\n", $output);
            $errorString = $returnCode !== 0 ? $outputString : '';
        } else {
            system($commandString, $returnCode);
            $outputString = '';
            $errorString = '';
        }
        
        $success = $returnCode === 0;
        
        if (!$success) {
            Logger::debug("WP-CLIエラー (戻り値: {$returnCode}): {$errorString}");
        }
        
        return [$success, $outputString, $errorString];
    }

    /**
     * WordPressがインストールされているかチェック
     *
     * @return bool インストール済みの場合true
     */
    public function isWordPressInstalled(): bool
    {
        [$success, $output] = $this->execute(['core', 'is-installed']);
        return $success;
    }

    /**
     * プラグインリストを取得
     *
     * @param string $format 出力フォーマット
     * @param string $fields 取得フィールド
     * @return array プラグイン情報の配列
     */
    public function getPluginList(string $format = 'json', string $fields = ''): array
    {
        $command = ['plugin', 'list', '--format=' . $format];
        
        if (!empty($fields)) {
            $command[] = '--fields=' . $fields;
        }
        
        [$success, $output] = $this->execute($command);
        
        if (!$success) {
            Logger::error("プラグインリストの取得に失敗しました");
            return [];
        }
        
        if ($format === 'json') {
            $data = json_decode($output, true);
            return $data ?: [];
        } elseif ($format === 'csv') {
            return $this->parseCsvOutput($output);
        }
        
        return [];
    }

    /**
     * プラグイン情報を取得
     *
     * @param string $pluginName プラグイン名
     * @return array|null プラグイン情報
     */
    public function getPluginInfo(string $pluginName): ?array
    {
        $plugins = $this->getPluginList('json');
        
        foreach ($plugins as $plugin) {
            if ($plugin['name'] === $pluginName) {
                return $plugin;
            }
        }
        
        return null;
    }

    /**
     * プラグインをインストール
     *
     * @param string $pluginName プラグイン名
     * @param string|null $version バージョン（nullの場合は最新）
     * @return bool 成功の場合true
     */
    public function installPlugin(string $pluginName, ?string $version = null): bool
    {
        $command = ['plugin', 'install', $pluginName];
        
        if ($version && $version !== 'latest') {
            $command[] = '--version=' . $version;
        }
        
        [$success, $output, $error] = $this->execute($command);
        
        if ($success) {
            Logger::success("プラグイン '{$pluginName}' のインストールが完了しました");
        } else {
            Logger::error("プラグイン '{$pluginName}' のインストールに失敗しました: {$error}");
        }
        
        return $success;
    }

    /**
     * プラグインをアンインストール
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功の場合true
     */
    public function uninstallPlugin(string $pluginName): bool
    {
        // まず非アクティブ化
        $this->deactivatePlugin($pluginName);
        
        [$success, $output, $error] = $this->execute(['plugin', 'uninstall', $pluginName]);
        
        if ($success) {
            Logger::success("プラグイン '{$pluginName}' のアンインストールが完了しました");
        } else {
            Logger::error("プラグイン '{$pluginName}' のアンインストールに失敗しました: {$error}");
        }
        
        return $success;
    }

    /**
     * プラグインを有効化
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功の場合true
     */
    public function activatePlugin(string $pluginName): bool
    {
        [$success, $output, $error] = $this->execute(['plugin', 'activate', $pluginName]);
        
        if ($success) {
            Logger::success("プラグイン '{$pluginName}' を有効化しました");
        } else {
            Logger::error("プラグイン '{$pluginName}' の有効化に失敗しました: {$error}");
        }
        
        return $success;
    }

    /**
     * プラグインを無効化
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功の場合true
     */
    public function deactivatePlugin(string $pluginName): bool
    {
        [$success, $output, $error] = $this->execute(['plugin', 'deactivate', $pluginName]);
        
        if ($success) {
            Logger::success("プラグイン '{$pluginName}' を無効化しました");
        } else {
            // 既に無効化されている場合は成功とみなす
            if (strpos($error, 'not active') !== false) {
                Logger::debug("プラグイン '{$pluginName}' は既に無効化されています");
                return true;
            }
            Logger::error("プラグイン '{$pluginName}' の無効化に失敗しました: {$error}");
        }
        
        return $success;
    }

    /**
     * プラグインを更新
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功の場合true
     */
    public function updatePlugin(string $pluginName): bool
    {
        [$success, $output, $error] = $this->execute(['plugin', 'update', $pluginName]);
        
        if ($success) {
            Logger::success("プラグイン '{$pluginName}' を更新しました");
        } else {
            // 既に最新の場合は成功とみなす
            if (strpos($error, 'up to date') !== false || strpos($output, 'up to date') !== false) {
                Logger::info("プラグイン '{$pluginName}' は既に最新版です");
                return true;
            }
            Logger::error("プラグイン '{$pluginName}' の更新に失敗しました: {$error}");
        }
        
        return $success;
    }

    /**
     * プラグインの自動更新を有効化
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功の場合true
     */
    public function enableAutoUpdates(string $pluginName): bool
    {
        [$success, $output, $error] = $this->execute(['plugin', 'auto-updates', 'enable', $pluginName]);
        
        if ($success) {
            Logger::success("プラグイン '{$pluginName}' の自動更新を有効化しました");
        } else {
            Logger::error("プラグイン '{$pluginName}' の自動更新有効化に失敗しました: {$error}");
        }
        
        return $success;
    }

    /**
     * プラグインの自動更新を無効化
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功の場合true
     */
    public function disableAutoUpdates(string $pluginName): bool
    {
        [$success, $output, $error] = $this->execute(['plugin', 'auto-updates', 'disable', $pluginName]);
        
        if ($success) {
            Logger::success("プラグイン '{$pluginName}' の自動更新を無効化しました");
        } else {
            Logger::error("プラグイン '{$pluginName}' の自動更新無効化に失敗しました: {$error}");
        }
        
        return $success;
    }

    /**
     * プラグインに利用可能な更新があるかチェック
     *
     * @param string $pluginName プラグイン名
     * @return bool 更新が利用可能な場合true、最新版の場合false
     */
    public function hasPluginUpdate(string $pluginName): bool
    {
        [$success, $output] = $this->execute(['plugin', 'list', '--format=json', '--fields=name,update_version']);
        
        if (!$success || empty($output)) {
            return false;
        }
        
        $pluginList = json_decode($output, true);
        if (!is_array($pluginList)) {
            return false;
        }
        
        // 対象プラグインを探してupdate_versionをチェック
        foreach ($pluginList as $plugin) {
            if (isset($plugin['name']) && $plugin['name'] === $pluginName) {
                // update_versionが空文字列であれば最新版（更新不要）
                $updateVersion = $plugin['update_version'] ?? '';
                return !empty($updateVersion);
            }
        }
        
        return false;
    }

    /**
     * CSV出力をパース
     *
     * @param string $csvOutput CSV形式の出力
     * @return array パースされたデータ
     */
    private function parseCsvOutput(string $csvOutput): array
    {
        $lines = explode("\n", trim($csvOutput));
        if (empty($lines)) {
            return [];
        }
        
        $headers = str_getcsv(array_shift($lines));
        $data = [];
        
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $data[] = array_combine($headers, $values);
            }
        }
        
        return $data;
    }

    /**
     * WP-CLIのパスを設定
     *
     * @param string $wpCliPath WP-CLIのパス
     */
    public function setWpCliPath(string $wpCliPath): void
    {
        $this->wpCliPath = $wpCliPath;
    }

    /**
     * WordPressのパスを設定
     *
     * @param string $wpPath WordPressのパス
     */
    public function setWpPath(string $wpPath): void
    {
        $this->wpPath = $wpPath;
    }
}