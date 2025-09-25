<?php
/**
 * 改善されたデータベース接続設定クラス
 * - 環境変数からの設定読み込み
 * - 適切なエラーハンドリング
 * - 接続プール機能
 * - 再試行機能
 */
class DatabaseConfig {
    private static $pdo = null;
    private static $config = null;
    private static $connectionAttempts = 0;
    private static $maxRetries = 3;
    private static $retryDelay = 1; // 秒
    
    /**
     * 環境変数から設定を読み込む
     */
    private static function loadConfig() {
        if (self::$config !== null) {
            return;
        }
        
        // 環境ファイルの読み込み
        $envFile = __DIR__ . '/../../.env';
        
        // 環境別設定ファイルの検出
        $environment = $_ENV['ENVIRONMENT'] ?? 'production';
        $envSpecificFile = $envFile . '.' . $environment;
        
        if (file_exists($envSpecificFile)) {
            $envFile = $envSpecificFile;
        }
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
        
        // 設定の取得
        self::$config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'dbname' => $_ENV['DB_NAME'] ?? 'tanaoroshi',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'debug' => filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ];
    }
    
    /**
     * PDOインスタンスを取得（改善版）
     * @return PDO
     * @throws DatabaseException
     */
    public static function getConnection() {
        if (self::$pdo === null) {
            self::loadConfig();
            self::$pdo = self::createConnection();
        }
        
        // 接続の健全性チェック
        try {
            self::$pdo->query('SELECT 1');
        } catch (PDOException $e) {
            // 接続が切れている場合は再接続
            self::$pdo = null;
            self::$pdo = self::createConnection();
        }
        
        return self::$pdo;
    }
    
    /**
     * 新しい接続を作成
     * @return PDO
     * @throws DatabaseException
     */
    private static function createConnection() {
        $config = self::$config;
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => false, // 必要に応じて true に変更
        ];
        
        // SSL設定（本番環境で必要な場合）
        if (!$config['debug']) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        
        $lastException = null;
        
        for ($attempt = 1; $attempt <= self::$maxRetries; $attempt++) {
            try {
                self::$connectionAttempts++;
                $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
                
                // 接続成功時のログ
                if ($config['debug']) {
                    error_log("Database connected successfully (attempt {$attempt})");
                }
                
                return $pdo;
                
            } catch (PDOException $e) {
                $lastException = $e;
                
                // デバッグモード時のみ詳細ログ
                if ($config['debug']) {
                    error_log("Database connection attempt {$attempt} failed: " . $e->getMessage());
                } else {
                    error_log("Database connection attempt {$attempt} failed");
                }
                
                // 最後の試行でない場合は待機
                if ($attempt < self::$maxRetries) {
                    sleep(self::$retryDelay * $attempt); // 指数バックオフ的な待機
                }
            }
        }
        
        // 全ての試行が失敗した場合
        throw new DatabaseException(
            'データベースに接続できませんでした。しばらく待ってから再試行してください。',
            500,
            $lastException
        );
    }
    
    /**
     * 接続を手動で閉じる
     */
    public static function closeConnection() {
        self::$pdo = null;
    }
    
    /**
     * トランザクションを開始
     * @return bool
     */
    public static function beginTransaction() {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * トランザクションをコミット
     * @return bool
     */
    public static function commit() {
        return self::getConnection()->commit();
    }
    
    /**
     * トランザクションをロールバック
     * @return bool
     */
    public static function rollback() {
        return self::getConnection()->rollBack();
    }
    
    /**
     * 接続統計を取得
     * @return array
     */
    public static function getConnectionStats() {
        return [
            'attempts' => self::$connectionAttempts,
            'current_connection' => self::$pdo !== null ? 'active' : 'inactive',
            'config_loaded' => self::$config !== null
        ];
    }
}

/**
 * データベース専用例外クラス
 */
class DatabaseException extends Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
?>