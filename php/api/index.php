<?php
/**
 * 改善された棚卸システムAPIエンドポイント
 * - 適切なエラーハンドリング
 * - レスポンス形式の統一
 * - セキュリティ強化
 * - ログ機能
 */

// レスポンスヘッダーの設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// デバッグモードの設定
$debugMode = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);

// エラーレポート設定
if ($debugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
}

ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__, 2) . '/logs/api_errors.log');

// APIクラスの初期化
try {
    require_once dirname(__DIR__) . '/classes/InventoryAPI.php';
    $api = new InventoryAPI();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'システム初期化エラーが発生しました',
        'error_code' => 'INIT_ERROR',
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    
    error_log('API initialization failed: ' . $e->getMessage());
    exit;
}

// OPTIONSリクエストの処理（CORS プリフライト）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// リクエスト情報の取得
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';
$action = $_GET['action'] ?? '';

// PATH_INFOまたはactionパラメータで処理を分岐
if (empty($path) && !empty($action)) {
    $path = '/' . $action;
}

// ログ出力用のリクエスト情報
$requestInfo = [
    'method' => $method,
    'path' => $path,
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'timestamp' => date('c')
];

try {
    $startTime = microtime(true);
    
    switch ($method) {
        case 'POST':
            $result = handlePostRequest($path, $api);
            break;
            
        case 'GET':
            $result = handleGetRequest($path, $api);
            break;
            
        default:
            throw new InvalidArgumentException('サポートされていないHTTPメソッドです: ' . $method);
    }
    
    // パフォーマンス測定
    $executionTime = round((microtime(true) - $startTime) * 1000, 2);
    
    // 成功レスポンス
    if (is_array($result)) {
        $result['execution_time_ms'] = $executionTime;
        $result['timestamp'] = date('c');
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    
    // 成功ログ
    error_log(sprintf(
        "API Success: %s %s (%.2fms) - %s",
        $method,
        $path,
        $executionTime,
        json_encode($requestInfo, JSON_UNESCAPED_UNICODE)
    ));
    
} catch (InventoryException $e) {
    handleApiError($e, $e->getCode() ?: 400, 'INVENTORY_ERROR', $requestInfo);
} catch (InvalidArgumentException $e) {
    handleApiError($e, 400, 'INVALID_ARGUMENT', $requestInfo);
} catch (Exception $e) {
    handleApiError($e, 500, 'INTERNAL_ERROR', $requestInfo);
}

/**
 * POSTリクエストの処理
 */
function handlePostRequest($path, $api) {
    switch ($path) {
        case '/items':
        case '/item':
            // 商品追加
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('不正なJSONデータです');
            }
            
            $code = $input['code'] ?? '';
            
            if (empty($code)) {
                throw new InvalidArgumentException('コードが指定されていません');
            }
            
            // 入力値のサニタイズ
            $code = trim(strip_tags($code));
            
            return $api->addItem($code);
            
        default:
            throw new InvalidArgumentException('無効なエンドポイントです: ' . $path);
    }
}

/**
 * GETリクエストの処理
 */
function handleGetRequest($path, $api) {
    switch ($path) {
        case '/unfound':
        case '/unfound-items':
            // 未発見リスト取得
            $offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]);
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 50, 'min_range' => 1, 'max_range' => 1000]]);
            $hiraganaFilter = filter_input(INPUT_GET, 'hiragana', FILTER_SANITIZE_STRING);
            $hiraganaFilter = trim($hiraganaFilter ?? '');
            $columnFilter = filter_input(INPUT_GET, 'column', FILTER_SANITIZE_STRING);
            $columnFilter = trim($columnFilter ?? '');
            
            // ひらがな行フィルターの検証
            $validFilters = ['あ', 'か', 'さ', 'た', 'な', 'は', 'ま', 'や', 'ら', 'わ'];
            if (!empty($hiraganaFilter) && !in_array($hiraganaFilter, $validFilters)) {
                $hiraganaFilter = null; // 無効なフィルターは無視
            }
            
            // 段フィルターの検証
            $validColumns = ['あ', 'い', 'う', 'え', 'お'];
            if (!empty($columnFilter) && !in_array($columnFilter, $validColumns)) {
                $columnFilter = null; // 無効な段は無視
            }
            
            return $api->getUnfoundItems($offset, $limit, $hiraganaFilter, $columnFilter);
            
        case '/suggestions':
        case '/search':
            // 検索候補取得
            $prefix = filter_input(INPUT_GET, 'prefix', FILTER_SANITIZE_STRING);
            $prefix = trim($prefix ?? '');
            
            if (strlen($prefix) > 50) {
                throw new InvalidArgumentException('検索文字列が長すぎます（最大50文字）');
            }
            
            $results = $api->getUnfoundSuggestions($prefix);
            
            return [
                'suggestions' => $results,
                'prefix' => $prefix,
                'count' => count($results)
            ];
            
        case '/export':
        case '/export-access':
            // Access用エクスポート
            return $api->generateAccessExport();
            
        case '/health':
        case '/status':
            // ヘルスチェック
            return [
                'status' => 'ok',
                'service' => 'inventory-api',
                'version' => '2.0.0',
                'database' => 'connected',
                'debug_mode' => filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ];
            
        default:
            throw new InvalidArgumentException('無効なエンドポイントです: ' . $path);
    }
}

/**
 * APIエラーハンドリング
 */
function handleApiError($exception, $httpCode, $errorCode, $requestInfo) {
    http_response_code($httpCode);
    
    $response = [
        'status' => 'error',
        'message' => $exception->getMessage(),
        'error_code' => $errorCode,
        'timestamp' => date('c')
    ];
    
    // デバッグモードでは詳細情報を追加
    $debugMode = filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);
    if ($debugMode) {
        $response['debug'] = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
    // エラーログ
    error_log(sprintf(
        "API Error: %s (%d) - %s - %s",
        $errorCode,
        $httpCode,
        $exception->getMessage(),
        json_encode($requestInfo, JSON_UNESCAPED_UNICODE)
    ));
}
?>