<?php
/**
 * PluginConfig Class - YAML設定ファイル解析機能
 * plugins.ymlファイルの読み込みと設定値の取得を行う
 */

require_once __DIR__ . '/Logger.php';

class PluginConfig
{
    private string $configPath;
    private ?array $config = null;

    /**
     * コンストラクタ
     *
     * @param string $configPath 設定ファイルのパス
     * @throws Exception 設定ファイルが存在しない場合
     */
    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        
        if (!file_exists($configPath)) {
            throw new Exception("設定ファイルが見つかりません: {$configPath}");
        }
        
        $this->loadConfig();
    }

    /**
     * 設定ファイルを読み込み
     *
     * @throws Exception 設定ファイルの読み込みに失敗した場合
     */
    private function loadConfig(): void
    {
        try {
            // YAML拡張が利用可能な場合
            if (function_exists('yaml_parse_file')) {
                $this->config = yaml_parse_file($this->configPath);
            } else {
                // カスタムYAMLパーサーを使用
                $this->config = $this->parseYamlFile($this->configPath);
            }

            if ($this->config === false || $this->config === null) {
                throw new Exception("設定ファイルの解析に失敗しました");
            }

            // 設定の検証
            $this->validateConfig();
        } catch (Exception $e) {
            Logger::error("設定ファイル読み込みエラー: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * シンプルなYAMLパーサー（YAML拡張が無い場合の代替）
     *
     * @param string $filePath YAMLファイルパス
     * @return array 解析結果
     * @throws Exception 解析に失敗した場合
     */
    private function parseYamlFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("ファイルの読み込みに失敗しました: {$filePath}");
        }

        $lines = explode("\n", $content);
        $result = [];
        $currentPlugin = null;
        $pluginsFound = false;
        
        foreach ($lines as $line) {
            $originalLine = $line;
            $line = rtrim($line); // 右側の空白を削除（左側は維持）
            
            // コメント行と空行をスキップ
            if (empty($line) || strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // pluginsセクションの開始
            if (preg_match('/^plugins:\s*$/', $line)) {
                $pluginsFound = true;
                $result['plugins'] = [];
                continue;
            }
            
            // pluginsセクション内のプラグイン名
            if ($pluginsFound && preg_match('/^  ([a-zA-Z0-9_-]+):\s*$/', $line, $matches)) {
                $currentPlugin = $matches[1];
                $result['plugins'][$currentPlugin] = [];
                continue;
            }
            
            // プラグイン設定の行
            if ($pluginsFound && $currentPlugin && preg_match('/^    ([a-zA-Z0-9_-]+):\s*(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = trim($matches[2]);
                
                // データ型の変換
                if ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                } elseif (is_numeric($value)) {
                    $value = is_float($value) ? (float)$value : (int)$value;
                }
                
                $result['plugins'][$currentPlugin][$key] = $value;
            }
        }
        
        if (!$pluginsFound) {
            throw new Exception("pluginsセクションが見つかりません");
        }
        
        return $result;
    }

    /**
     * 設定の妥当性を検証
     *
     * @throws Exception 設定が不正な場合
     */
    private function validateConfig(): void
    {
        if (!isset($this->config['plugins']) || !is_array($this->config['plugins'])) {
            throw new Exception("設定ファイルにpluginsセクションが見つかりません");
        }

        foreach ($this->config['plugins'] as $pluginName => $pluginConfig) {
            if (!is_array($pluginConfig)) {
                throw new Exception("プラグイン '{$pluginName}' の設定が不正です");
            }

            // 必須フィールドの確認
            $requiredFields = ['name', 'auto-update', 'active', 'version'];
            foreach ($requiredFields as $field) {
                if (!isset($pluginConfig[$field])) {
                    Logger::warning("プラグイン '{$pluginName}' に '{$field}' フィールドがありません。デフォルト値を使用します。");
                }
            }
        }
    }

    /**
     * 定義されたプラグインのリストを取得
     *
     * @return array プラグイン名の配列
     */
    public function getDefinedPlugins(): array
    {
        return array_keys($this->config['plugins'] ?? []);
    }

    /**
     * プラグインの自動更新設定を取得
     *
     * @param string $pluginName プラグイン名
     * @return bool 自動更新設定
     */
    public function getPluginAutoUpdate(string $pluginName): bool
    {
        return $this->config['plugins'][$pluginName]['auto-update'] ?? false;
    }

    /**
     * プラグインの有効化設定を取得
     *
     * @param string $pluginName プラグイン名
     * @return bool 有効化設定
     */
    public function getPluginActive(string $pluginName): bool
    {
        return $this->config['plugins'][$pluginName]['active'] ?? true;
    }

    /**
     * プラグインのバージョン設定を取得
     *
     * @param string $pluginName プラグイン名
     * @return string バージョン設定
     */
    public function getPluginVersion(string $pluginName): string
    {
        return $this->config['plugins'][$pluginName]['version'] ?? 'latest';
    }

    /**
     * プラグインの名前設定を取得
     *
     * @param string $pluginName プラグイン名
     * @return string プラグイン名
     */
    public function getPluginName(string $pluginName): string
    {
        return $this->config['plugins'][$pluginName]['name'] ?? $pluginName;
    }

    /**
     * プラグインの全設定を取得
     *
     * @param string $pluginName プラグイン名
     * @return array|null プラグイン設定
     */
    public function getPluginConfig(string $pluginName): ?array
    {
        return $this->config['plugins'][$pluginName] ?? null;
    }

    /**
     * プラグインが定義されているかどうかを確認
     *
     * @param string $pluginName プラグイン名
     * @return bool 定義されている場合true
     */
    public function isPluginDefined(string $pluginName): bool
    {
        return isset($this->config['plugins'][$pluginName]);
    }

    /**
     * 設定ファイルのフルパスを取得
     *
     * @return string 設定ファイルパス
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * 生の設定データを取得（デバッグ用）
     *
     * @return array 設定データ
     */
    public function getRawConfig(): array
    {
        return $this->config ?? [];
    }

    /**
     * 設定ファイルを再読み込み
     *
     * @throws Exception 再読み込みに失敗した場合
     */
    public function reload(): void
    {
        $this->config = null;
        $this->loadConfig();
        Logger::debug("設定ファイルを再読み込みしました: {$this->configPath}");
    }
}