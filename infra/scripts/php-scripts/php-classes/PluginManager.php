<?php
/**
 * PluginManager Class - プラグイン管理メインクラス
 * WordPressプラグインの統合管理機能を提供
 */

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/PluginConfig.php';
require_once __DIR__ . '/WPCLIWrapper.php';

class PluginManager
{
    private PluginConfig $config;
    private WPCLIWrapper $wpCli;

    /**
     * コンストラクタ
     *
     * @param string $configPath 設定ファイルのパス
     * @throws Exception 初期化に失敗した場合
     */
    public function __construct(string $configPath)
    {
        try {
            $this->config = new PluginConfig($configPath);
            $this->wpCli = new WPCLIWrapper();
        } catch (Exception $e) {
            Logger::error("初期化エラー: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 使用方法を表示
     */
    public function showUsage(): void
    {
        echo "使用方法: php manage-plugins.php [OPTIONS]\n\n";
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
        echo "  php manage-plugins.php --sync     # 完全同期（推奨）\n";
        echo "  php manage-plugins.php --status   # 状態確認のみ\n";
    }

    /**
     * インストール済みプラグインのリストを取得
     *
     * @return array インストール済みプラグイン名の配列
     */
    public function getInstalledPlugins(): array
    {
        $plugins = $this->wpCli->getPluginList('json', 'name');
        return array_column($plugins, 'name');
    }

    /**
     * プラグインの状態を表示
     */
    public function showPluginStatus(): void
    {
        Logger::section("プラグイン状態確認");

        // WordPress接続確認
        if (!$this->wpCli->isWordPressInstalled()) {
            Logger::error("WordPressが正しくインストールされていません");
            return;
        }

        // 定義されたプラグインの表示
        Logger::info("定義されたプラグイン (plugins.yml):");
        $definedPlugins = $this->config->getDefinedPlugins();

        if (!empty($definedPlugins)) {
            // 全プラグインの詳細情報を一度に取得
            $allPlugins = $this->wpCli->getPluginList('json');
            $pluginsInfo = [];
            foreach ($allPlugins as $plugin) {
                $pluginsInfo[$plugin['name']] = $plugin;
            }

            foreach ($definedPlugins as $pluginName) {
                $autoUpdateSetting = $this->config->getPluginAutoUpdate($pluginName);
                $activeSetting = $this->config->getPluginActive($pluginName);
                $versionSetting = $this->config->getPluginVersion($pluginName);

                // 現在のバージョンを取得
                if (isset($pluginsInfo[$pluginName])) {
                    $currentVersion = $pluginsInfo[$pluginName]['version'];
                    $status = $pluginsInfo[$pluginName]['status'];
                    $autoUpdate = $pluginsInfo[$pluginName]['auto_update'] ?? 'off';
                    
                    echo "  - {$pluginName} (active: " . ($activeSetting ? 'true' : 'false') . 
                         ", auto-update: " . ($autoUpdateSetting ? 'true' : 'false') . 
                         ", version: {$versionSetting}, current: v{$currentVersion}, " .
                         "status: {$status}, auto_update: {$autoUpdate})\n";
                } else {
                    echo "  - {$pluginName} (active: " . ($activeSetting ? 'true' : 'false') . 
                         ", auto-update: " . ($autoUpdateSetting ? 'true' : 'false') . 
                         ", version: {$versionSetting}, current: 未インストール)\n";
                }
            }
        } else {
            echo "  (なし)\n";
        }

        echo "\n";

        // インストール済みプラグインの表示
        Logger::info("インストール済みプラグイン:");
        $installedPlugins = $this->getInstalledPlugins();
        if (!empty($installedPlugins)) {
            sort($installedPlugins);
            foreach ($installedPlugins as $plugin) {
                echo "  - {$plugin}\n";
            }
        } else {
            echo "  (なし)\n";
        }

        echo "\n";

        // 詳細なプラグイン情報の表示
        Logger::info("詳細なプラグイン情報:");
        [$success, $output] = $this->wpCli->execute(['plugin', 'list', '--format=table']);
        if ($success) {
            echo $output . "\n";
        }

        echo "\n";

        // 自動更新状態の表示
        Logger::info("自動更新状態:");
        if (!empty($installedPlugins)) {
            $autoUpdateData = $this->wpCli->getPluginList('json', 'name,auto_update');
            foreach ($autoUpdateData as $pluginInfo) {
                $pluginName = $pluginInfo['name'];
                $autoUpdateStatus = ($pluginInfo['auto_update'] === 'on') ? 'enabled' : 'disabled';
                echo "  - {$pluginName}: {$autoUpdateStatus}\n";
            }
        } else {
            echo "  (インストール済みプラグインなし)\n";
        }
    }

    /**
     * プラグインをバージョン管理でインストール
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功の場合true
     */
    public function installPluginWithVersion(string $pluginName): bool
    {
        $autoUpdateSetting = $this->config->getPluginAutoUpdate($pluginName);
        $versionSetting = $this->config->getPluginVersion($pluginName);

        Logger::info("プラグイン '{$pluginName}' をインストール中...");

        // auto-update=trueの場合は常に最新版
        if ($autoUpdateSetting) {
            Logger::info("auto-update有効のため最新版をインストール");
            $success = $this->wpCli->installPlugin($pluginName);
        } else {
            if ($versionSetting === 'latest') {
                Logger::info("最新版をインストール");
                $success = $this->wpCli->installPlugin($pluginName);
            } else {
                Logger::info("バージョン {$versionSetting} をインストール");
                $success = $this->wpCli->installPlugin($pluginName, $versionSetting);

                // 指定バージョンのインストールに失敗した場合は最新版にフォールバック
                if (!$success) {
                    Logger::warning("指定バージョン {$versionSetting} が見つかりません。最新版をインストールします");
                    $success = $this->wpCli->installPlugin($pluginName);
                }
            }
        }

        if ($success) {
            // active設定を適用
            $this->configureActive($pluginName);
            // auto-update設定を適用
            $this->configureAutoUpdate($pluginName);
        }

        return $success;
    }

    /**
     * プラグインのactive設定を適用
     *
     * @param string $pluginName プラグイン名
     */
    public function configureActive(string $pluginName): void
    {
        $desiredSetting = $this->config->getPluginActive($pluginName);

        // 現在の状態を取得
        $pluginInfo = $this->wpCli->getPluginInfo($pluginName);
        if (!$pluginInfo) {
            return;
        }

        $currentActive = ($pluginInfo['status'] === 'active');

        // 現在の設定と希望の設定が同じ場合はスキップ
        if ($currentActive === $desiredSetting) {
            Logger::info("プラグイン '{$pluginName}' の有効化設定は既に正しく設定されています (active: " . ($desiredSetting ? 'true' : 'false') . ")");
            return;
        }

        if ($desiredSetting) {
            Logger::info("プラグイン '{$pluginName}' を有効化中... (現在: " . ($currentActive ? 'true' : 'false') . " -> 設定: true)");
            $this->wpCli->activatePlugin($pluginName);
        } else {
            Logger::info("プラグイン '{$pluginName}' を無効化中... (現在: " . ($currentActive ? 'true' : 'false') . " -> 設定: false)");
            $this->wpCli->deactivatePlugin($pluginName);
        }
    }

    /**
     * プラグインのauto-update設定を適用
     *
     * @param string $pluginName プラグイン名
     */
    public function configureAutoUpdate(string $pluginName): void
    {
        $desiredSetting = $this->config->getPluginAutoUpdate($pluginName);

        // 現在の状態を取得
        $pluginInfo = $this->wpCli->getPluginInfo($pluginName);
        if (!$pluginInfo) {
            return;
        }

        $currentAutoUpdate = (($pluginInfo['auto_update'] ?? 'off') === 'on');

        // 現在の設定と希望の設定が同じ場合はスキップ
        if ($currentAutoUpdate === $desiredSetting) {
            Logger::info("プラグイン '{$pluginName}' の自動更新設定は既に正しく設定されています (auto-update: " . ($desiredSetting ? 'true' : 'false') . ")");
            return;
        }

        if ($desiredSetting) {
            Logger::info("プラグイン '{$pluginName}' の自動更新を有効化中... (現在: " . ($currentAutoUpdate ? 'true' : 'false') . " -> 設定: true)");
            $this->wpCli->enableAutoUpdates($pluginName);
        } else {
            Logger::info("プラグイン '{$pluginName}' の自動更新を無効化中... (現在: " . ($currentAutoUpdate ? 'true' : 'false') . " -> 設定: false)");
            $this->wpCli->disableAutoUpdates($pluginName);
        }
    }

    /**
     * プラグインを完全同期
     */
    public function syncPlugins(): void
    {
        Logger::section("プラグイン同期開始");

        // WordPress接続確認
        if (!$this->wpCli->isWordPressInstalled()) {
            Logger::error("WordPressが正しくインストールされていません");
            return;
        }

        $definedPlugins = $this->config->getDefinedPlugins();
        $installedPlugins = $this->getInstalledPlugins();

        echo "\n";
        Logger::info("Step 1: 不要なプラグインを削除中...");

        // 不要なプラグインを削除
        $pluginsToRemove = array_diff($installedPlugins, $definedPlugins);
        foreach ($pluginsToRemove as $pluginName) {
            Logger::warning("不要なプラグイン検出: {$pluginName}");
            $this->wpCli->uninstallPlugin($pluginName);
        }

        echo "\n";
        Logger::info("Step 2: 必要なプラグインをインストール・バージョン確認中...");

        // 必要なプラグインをインストール
        foreach ($definedPlugins as $pluginName) {
            if (!in_array($pluginName, $installedPlugins)) {
                $this->installPluginWithVersion($pluginName);
            } else {
                Logger::info("プラグイン '{$pluginName}' は既にインストール済み - バージョンを確認中...");
                $this->updatePluginVersion($pluginName);
            }
        }

        echo "\n";
        Logger::info("Step 3: 既存プラグインのauto-update設定を更新中...");

        // プラグインリストを更新
        $currentInstalled = $this->getInstalledPlugins();
        $pluginsChecked = 0;
        $pluginsUpdated = 0;

        foreach ($definedPlugins as $pluginName) {
            if (in_array($pluginName, $currentInstalled)) {
                $pluginsChecked++;
                $currentAutoUpdate = $this->getCurrentAutoUpdateStatus($pluginName);
                $desiredAutoUpdate = $this->config->getPluginAutoUpdate($pluginName);

                if ($currentAutoUpdate !== $desiredAutoUpdate) {
                    $pluginsUpdated++;
                    $this->configureAutoUpdate($pluginName);
                } else {
                    Logger::info("プラグイン '{$pluginName}' の自動更新設定は既に正しく設定されています (auto-update: " . ($desiredAutoUpdate ? 'true' : 'false') . ")");
                }
            }
        }

        Logger::info("auto-update設定確認: {$pluginsChecked} 個のプラグインを確認、{$pluginsUpdated} 個のプラグインを更新");

        echo "\n";
        Logger::info("Step 4: 既存プラグインのactive設定を更新中...");

        // active設定の更新
        $activePluginsChecked = 0;
        $activePluginsUpdated = 0;

        foreach ($definedPlugins as $pluginName) {
            if (in_array($pluginName, $currentInstalled)) {
                $activePluginsChecked++;
                $currentActive = $this->getCurrentActiveStatus($pluginName);
                $desiredActive = $this->config->getPluginActive($pluginName);

                if ($currentActive !== $desiredActive) {
                    $activePluginsUpdated++;
                    $this->configureActive($pluginName);
                } else {
                    Logger::info("プラグイン '{$pluginName}' の有効化設定は既に正しく設定されています (active: " . ($desiredActive ? 'true' : 'false') . ")");
                }
            }
        }

        Logger::info("active設定確認: {$activePluginsChecked} 個のプラグインを確認、{$activePluginsUpdated} 個のプラグインを更新");

        echo "\n";
        Logger::success("=== プラグイン同期完了 ===");
        $this->showPluginStatus();
    }

    /**
     * 現在のauto-update状態を取得
     *
     * @param string $pluginName プラグイン名
     * @return bool auto-update状態
     */
    private function getCurrentAutoUpdateStatus(string $pluginName): bool
    {
        $pluginInfo = $this->wpCli->getPluginInfo($pluginName);
        return $pluginInfo ? (($pluginInfo['auto_update'] ?? 'off') === 'on') : false;
    }

    /**
     * 現在のactive状態を取得
     *
     * @param string $pluginName プラグイン名
     * @return bool active状態
     */
    private function getCurrentActiveStatus(string $pluginName): bool
    {
        $pluginInfo = $this->wpCli->getPluginInfo($pluginName);
        return $pluginInfo ? ($pluginInfo['status'] === 'active') : false;
    }

    /**
     * プラグインのバージョンを更新
     *
     * @param string $pluginName プラグイン名
     */
    private function updatePluginVersion(string $pluginName): void
    {
        $autoUpdateSetting = $this->config->getPluginAutoUpdate($pluginName);
        $versionSetting = $this->config->getPluginVersion($pluginName);

        // 現在のバージョンを取得
        $pluginInfo = $this->wpCli->getPluginInfo($pluginName);
        if (!$pluginInfo) {
            return;
        }

        $currentVersion = $pluginInfo['version'];

        // auto-update=trueの場合は最新版に更新
        if ($autoUpdateSetting) {
            // 利用可能な更新があるかチェック
            if ($this->wpCli->hasPluginUpdate($pluginName)) {
                Logger::info("プラグイン '{$pluginName}' のauto-update有効で新しいバージョンが利用可能です (現在: v{$currentVersion}) - 更新中...");
                if ($this->wpCli->updatePlugin($pluginName)) {
                    // 更新後のバージョンを取得
                    $updatedInfo = $this->wpCli->getPluginInfo($pluginName);
                    if ($updatedInfo) {
                        Logger::success("プラグイン '{$pluginName}' を v{$currentVersion} から v{$updatedInfo['version']} に更新");
                    }
                }
            } else {
                Logger::info("プラグイン '{$pluginName}' は既に最新版です (v{$currentVersion})");
            }
        } else {
            // auto-update=falseの場合はversion設定を確認
            if ($versionSetting !== 'latest' && $currentVersion !== $versionSetting) {
                Logger::info("プラグイン '{$pluginName}' のバージョンを v{$currentVersion} から v{$versionSetting} に変更中...");
                
                // 現在のactive状態を保存
                $wasActive = $this->getCurrentActiveStatus($pluginName);

                // 一度削除してから指定バージョンをインストール
                if ($this->wpCli->deactivatePlugin($pluginName) &&
                    $this->wpCli->uninstallPlugin($pluginName) &&
                    $this->wpCli->installPlugin($pluginName, $versionSetting)) {
                    
                    Logger::success("プラグイン '{$pluginName}' をバージョン {$versionSetting} に変更完了");

                    // 以前アクティブだった場合は再度アクティブ化
                    if ($wasActive) {
                        $this->wpCli->activatePlugin($pluginName);
                        Logger::info("プラグイン '{$pluginName}' を再度有効化");
                    }
                } else {
                    Logger::warning("プラグイン '{$pluginName}' のバージョン変更に失敗");
                }
            } else {
                Logger::info("プラグイン '{$pluginName}' のバージョンは適切です (現在: v{$currentVersion}, 設定: {$versionSetting})");
            }
        }
    }

    /**
     * インストールのみ実行
     */
    public function installPlugins(): void
    {
        Logger::section("プラグインインストール開始");

        $definedPlugins = $this->config->getDefinedPlugins();
        $installedPlugins = $this->getInstalledPlugins();

        foreach ($definedPlugins as $pluginName) {
            if (!in_array($pluginName, $installedPlugins)) {
                $this->installPluginWithVersion($pluginName);
            } else {
                Logger::info("プラグイン '{$pluginName}' は既にインストール済み");
                // 既存プラグインの設定も更新
                $this->configureActive($pluginName);
                $this->configureAutoUpdate($pluginName);
            }
        }

        Logger::success("=== プラグインインストール完了 ===");
    }

    /**
     * クリーンアップのみ実行
     */
    public function cleanupPlugins(): void
    {
        Logger::section("プラグインクリーンアップ開始");

        $definedPlugins = $this->config->getDefinedPlugins();
        $installedPlugins = $this->getInstalledPlugins();

        $pluginsToRemove = array_diff($installedPlugins, $definedPlugins);
        foreach ($pluginsToRemove as $pluginName) {
            $this->wpCli->uninstallPlugin($pluginName);
        }

        Logger::success("=== プラグインクリーンアップ完了 ===");
    }

    /**
     * auto-update設定のみ更新
     */
    public function updateAutoUpdateSettings(): void
    {
        Logger::section("auto-update設定更新開始");

        if (!$this->wpCli->isWordPressInstalled()) {
            Logger::error("WordPressが正しくインストールされていません");
            return;
        }

        $definedPlugins = $this->config->getDefinedPlugins();
        $installedPlugins = $this->getInstalledPlugins();

        $pluginsChecked = 0;
        $pluginsUpdated = 0;
        $pluginsSkipped = 0;

        foreach ($definedPlugins as $pluginName) {
            $pluginsChecked++;

            if (in_array($pluginName, $installedPlugins)) {
                $currentAutoUpdate = $this->getCurrentAutoUpdateStatus($pluginName);
                $desiredAutoUpdate = $this->config->getPluginAutoUpdate($pluginName);

                if ($currentAutoUpdate !== $desiredAutoUpdate) {
                    $pluginsUpdated++;
                    $this->configureAutoUpdate($pluginName);
                } else {
                    Logger::info("プラグイン '{$pluginName}' の自動更新設定は既に正しく設定されています (auto-update: " . ($desiredAutoUpdate ? 'true' : 'false') . ")");
                }
            } else {
                $pluginsSkipped++;
                Logger::warning("プラグイン '{$pluginName}' はインストールされていません（設定をスキップ）");
            }
        }

        Logger::info("auto-update設定確認: {$pluginsChecked} 個のプラグインを確認、{$pluginsUpdated} 個のプラグインを更新、{$pluginsSkipped} 個のプラグインをスキップ");

        Logger::success("=== auto-update設定更新完了 ===");
        $this->showPluginStatus();
    }

    /**
     * active設定のみ更新
     */
    public function activatePlugins(): void
    {
        Logger::section("active設定更新開始");

        if (!$this->wpCli->isWordPressInstalled()) {
            Logger::error("WordPressが正しくインストールされていません");
            return;
        }

        $definedPlugins = $this->config->getDefinedPlugins();
        $installedPlugins = $this->getInstalledPlugins();

        $pluginsChecked = 0;
        $pluginsUpdated = 0;
        $pluginsSkipped = 0;

        foreach ($definedPlugins as $pluginName) {
            $pluginsChecked++;

            if (in_array($pluginName, $installedPlugins)) {
                $currentActive = $this->getCurrentActiveStatus($pluginName);
                $desiredActive = $this->config->getPluginActive($pluginName);

                if ($currentActive !== $desiredActive) {
                    $pluginsUpdated++;
                    $this->configureActive($pluginName);
                } else {
                    Logger::info("プラグイン '{$pluginName}' の有効化設定は既に正しく設定されています (active: " . ($desiredActive ? 'true' : 'false') . ")");
                }
            } else {
                $pluginsSkipped++;
                Logger::warning("プラグイン '{$pluginName}' はインストールされていません（設定をスキップ）");
            }
        }

        Logger::info("active設定確認: {$pluginsChecked} 個のプラグインを確認、{$pluginsUpdated} 個のプラグインを更新、{$pluginsSkipped} 個のプラグインをスキップ");

        Logger::success("=== active設定更新完了 ===");
        $this->showPluginStatus();
    }

    /**
     * バージョン設定のみ更新
     */
    public function updateVersions(): void
    {
        Logger::section("バージョン設定更新開始");

        if (!$this->wpCli->isWordPressInstalled()) {
            Logger::error("WordPressが正しくインストールされていません");
            return;
        }

        $definedPlugins = $this->config->getDefinedPlugins();
        $installedPlugins = $this->getInstalledPlugins();

        $pluginsChecked = 0;
        $pluginsUpdated = 0;
        $pluginsSkipped = 0;

        foreach ($definedPlugins as $pluginName) {
            $pluginsChecked++;

            if (in_array($pluginName, $installedPlugins)) {
                // バージョン更新処理
                $oldInfo = $this->wpCli->getPluginInfo($pluginName);
                $oldVersion = $oldInfo ? $oldInfo['version'] : 'unknown';

                $this->updatePluginVersion($pluginName);

                // 更新後の情報を取得
                $newInfo = $this->wpCli->getPluginInfo($pluginName);
                $newVersion = $newInfo ? $newInfo['version'] : 'unknown';

                if ($oldVersion !== $newVersion) {
                    $pluginsUpdated++;
                }
            } else {
                $pluginsSkipped++;
                Logger::warning("プラグイン '{$pluginName}' はインストールされていません（バージョン設定をスキップ）");
            }
        }

        Logger::info("バージョン設定確認: {$pluginsChecked} 個のプラグインを確認、{$pluginsUpdated} 個のプラグインを更新、{$pluginsSkipped} 個のプラグインをスキップ");

        Logger::success("=== バージョン設定更新完了 ===");
        $this->showPluginStatus();
    }
}