<?php
// 絶対パスでの安全な設定ファイル読み込み
$configPath = dirname(__DIR__) . '/config/database.php';
require_once $configPath;
require_once __DIR__ . '/SimpleLogger.php';

/**
 * 改善された棚卸システムのAPIクラス
 * - トランザクション管理の統一
 * - 適切なエラーハンドリング
 * - ログ機能の追加
 * - セキュリティの向上
 */
class InventoryAPI {
    private $pdo;
    private $logger;
    
    public function __construct() {
        try {
            $this->pdo = DatabaseConfig::getConnection();
            $this->logger = new SimpleLogger();
        } catch (DatabaseException $e) {
            $this->logger = new SimpleLogger();
            $this->logger->error('Database initialization failed', ['error' => $e->getMessage()]);
            throw new InventoryException('システム初期化エラーが発生しました', 500, $e);
        }
    }
    
    /**
     * 商品を棚卸済みに追加する（改善版）
     * @param string $combinedCode コード（code1-code2形式）
     * @return array レスポンス
     * @throws InventoryException
     */
    public function addItem($combinedCode) {
        if (empty($combinedCode)) {
            throw new InventoryException('コードが指定されていません');
        }
        
        try {
            DatabaseConfig::beginTransaction();
            
            // 正規化
            $normalizedCode = $this->normalizeString($combinedCode);
            $this->logger->info('Adding item', ['code' => $normalizedCode]);
            
            // マスターデータから商品情報を取得
            $productInfo = $this->getMasterProduct($normalizedCode);
            if (!$productInfo) {
                DatabaseConfig::rollback();
                throw new InventoryException('マスターに存在しないコードです: ' . $normalizedCode);
            }
            
            // 重複チェック
            if ($this->isItemAlreadyInventoried($normalizedCode)) {
                DatabaseConfig::rollback();
                throw new InventoryException('このコードは既に棚卸済みです: ' . $normalizedCode);
            }
            
            // 棚卸データに追加
            $stmt = $this->pdo->prepare("
                INSERT INTO inventory_items (code, product_name, created_at) 
                VALUES (?, ?, NOW())
            ");
            
            $success = $stmt->execute([$normalizedCode, $productInfo['product_name']]);
            
            if (!$success || $stmt->rowCount() === 0) {
                throw new InventoryException('データの挿入に失敗しました');
            }
            
            DatabaseConfig::commit();
            
            $this->logger->info('Item added successfully', [
                'code' => $normalizedCode,
                'product_name' => $productInfo['product_name']
            ]);
            
            return [
                'status' => 'success',
                'message' => "「{$productInfo['product_name']}」を追加しました。",
                'code' => $normalizedCode,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (InventoryException $e) {
            DatabaseConfig::rollback();
            throw $e;
        } catch (Exception $e) {
            DatabaseConfig::rollback();
            $this->logger->error('Add item error', [
                'code' => $combinedCode,
                'error' => $e->getMessage()
            ]);
            throw new InventoryException('商品の追加中にエラーが発生しました', 500, $e);
        }
    }
    
    /**
     * 未発見商品リストを取得する（改善版）
     * @param int $offset オフセット
     * @param int $limit 取得件数
     * @param string $hiraganaFilter ひらがな行フィルター（あ、か、さ、た、な、は、ま、や、ら、わ）
     * @param string $columnFilter ひらがな段フィルター（あ、い、う、え、お）
     * @return array レスポンス
     * @throws InventoryException
     */
    public function getUnfoundItems($offset = 0, $limit = 50, $hiraganaFilter = null, $columnFilter = null) {
        try {
            // パラメータの検証
            $offset = max(0, intval($offset));
            $limit = min(1000, max(1, intval($limit))); // 最大1000件まで制限
            
            // ひらがな行フィルターの条件を構築
            $hiraganaCondition = '';
            $hiraganaParam = [];
            if (!empty($hiraganaFilter)) {
                $hiraganaMap = $this->getHiraganaRangeMap();
                if (isset($hiraganaMap[$hiraganaFilter])) {
                    if (!empty($columnFilter)) {
                        // 段が指定されている場合は、具体的な文字でフィルタリング
                        $specificChar = $this->getSpecificCharacter($hiraganaFilter, $columnFilter);
                        if ($specificChar) {
                            // 文字を正規化してその文字で始まるコードを検索
                            $normalizedChar = $this->normalizeString($specificChar);
                            $hiraganaCondition = " AND m.code1 LIKE CONCAT(?, '%')";
                            $hiraganaParam = [$normalizedChar];
                        }
                    } else {
                        // 行のみが指定されている場合は、範囲でフィルタリング
                        $range = $hiraganaMap[$hiraganaFilter];
                        $hiraganaCondition = " AND m.code1 >= ? AND m.code1 < ?";
                        $hiraganaParam = [$range['start'], $range['end']];
                    }
                }
            }
            
            // 全体件数を取得（パフォーマンス最適化）
            $totalStmt = $this->pdo->prepare("
                SELECT COUNT(*) as total 
                FROM master_products m 
                WHERE NOT EXISTS (
                    SELECT 1 FROM inventory_items i 
                    WHERE i.code = CONCAT(m.code1, '-', m.code2)
                )" . $hiraganaCondition . "
            ");
            $totalStmt->execute($hiraganaParam);
            $total = intval($totalStmt->fetchColumn());
            
            // 未発見商品を取得（自然順序ソート対応）
            $itemsStmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(m.code1, '-', m.code2) as code, 
                    m.product_name as name,
                    m.updated_at
                FROM master_products m 
                WHERE NOT EXISTS (
                    SELECT 1 FROM inventory_items i 
                    WHERE i.code = CONCAT(m.code1, '-', m.code2)
                )" . $hiraganaCondition . "
                ORDER BY 
                    m.code1, 
                    LENGTH(m.code2), 
                    m.code2
                LIMIT ? OFFSET ?
            ");
            $itemsStmt->execute(array_merge($hiraganaParam, [$limit, $offset]));
            $items = $itemsStmt->fetchAll();
            
            $this->logger->info('Unfound items retrieved', [
                'total' => $total,
                'offset' => $offset,
                'limit' => $limit,
                'hiragana_filter' => $hiraganaFilter,
                'column_filter' => $columnFilter,
                'returned' => count($items)
            ]);
            
            return [
                'items' => $items,
                'total' => $total,
                'offset' => $offset,
                'limit' => $limit,
                'hiragana_filter' => $hiraganaFilter,
                'column_filter' => $columnFilter,
                'has_more' => ($offset + $limit) < $total,
                'filter_applied' => !empty($hiraganaFilter) || !empty($columnFilter)
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Get unfound items error', [
                'offset' => $offset,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw new InventoryException('未発見商品リストの取得に失敗しました', 500, $e);
        }
    }
    
    /**
     * 未発見商品の検索候補を取得する（改善版）
     * @param string $prefix プレフィックス
     * @return array 検索候補
     * @throws InventoryException
     */
    public function getUnfoundSuggestions($prefix) {
        try {
            if (empty($prefix) || strlen($prefix) < 1) {
                return [];
            }
            
            // SQLインジェクション対策を強化
            $normalizedPrefix = $this->normalizeString($prefix);
            $normalizedPrefix = $this->escapeForLike($normalizedPrefix);
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    CONCAT(m.code1, '-', m.code2) as code, 
                    m.product_name as name,
                    m.updated_at
                FROM master_products m 
                WHERE (
                    CONCAT(m.code1, '-', m.code2) LIKE CONCAT(?, '%') OR
                    m.product_name LIKE CONCAT('%', ?, '%')
                )
                AND NOT EXISTS (
                    SELECT 1 FROM inventory_items i 
                    WHERE i.code = CONCAT(m.code1, '-', m.code2)
                )
                ORDER BY 
                    CASE WHEN CONCAT(m.code1, '-', m.code2) LIKE CONCAT(?, '%') THEN 1 ELSE 2 END,
                    m.code1, 
                    LENGTH(m.code2), 
                    m.code2
                LIMIT 20
            ");
            
            $stmt->execute([$normalizedPrefix, $normalizedPrefix, $normalizedPrefix]);
            $results = $stmt->fetchAll();
            
            $this->logger->info('Suggestions retrieved', [
                'prefix' => $prefix,
                'count' => count($results)
            ]);
            
            return $results;
            
        } catch (Exception $e) {
            $this->logger->error('Get suggestions error', [
                'prefix' => $prefix,
                'error' => $e->getMessage()
            ]);
            throw new InventoryException('検索候補の取得に失敗しました', 500, $e);
        }
    }
    
    /**
     * Access用エクスポートデータを生成する（改善版）
     * @return array エクスポートデータ
     * @throws InventoryException
     */
    public function generateAccessExport() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.code1, 
                    m.code2, 
                    m.product_name,
                    DATE_FORMAT(i.created_at, '%Y/%m/%d %H:%i:%s') as inventory_date
                FROM inventory_items i
                JOIN master_products m ON i.code = CONCAT(m.code1, '-', m.code2)
                ORDER BY m.code1, LENGTH(m.code2), m.code2
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();
            
            $this->logger->info('Access export generated', ['count' => count($data)]);
            
            return [
                'status' => 'success',
                'data' => $data,
                'count' => count($data),
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Generate export error', ['error' => $e->getMessage()]);
            throw new InventoryException('エクスポートの生成に失敗しました', 500, $e);
        }
    }
    
    /**
     * 商品が既に棚卸済みかチェック
     * @param string $normalizedCode 正規化されたコード
     * @return bool
     */
    private function isItemAlreadyInventoried($normalizedCode) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM inventory_items WHERE code = ?
        ");
        $stmt->execute([$normalizedCode]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * マスターデータから商品情報を取得（改善版）
     * @param string $normalizedCode 正規化されたコード
     * @return array|null 商品情報
     */
    private function getMasterProduct($normalizedCode) {
        $parts = explode('-', $normalizedCode, 2);
        if (count($parts) !== 2) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("
            SELECT 
                product_name,
                created_at,
                updated_at
            FROM master_products 
            WHERE code1 = ? AND code2 = ?
        ");
        $stmt->execute([$parts[0], $parts[1]]);
        
        return $stmt->fetch();
    }
    
    /**
     * 文字列を正規化する（改善版）
     * @param string $str 入力文字列
     * @return string 正規化された文字列
     */
    private function normalizeString($str) {
        if (!class_exists('Normalizer')) {
            // Normalizerクラスが使用できない場合の代替処理
            $str = trim($str);
            $str = mb_convert_kana($str, 'askKV', 'UTF-8');
            return $str;
        }
        
        $normalized = \Normalizer::normalize(trim($str), \Normalizer::FORM_KC);
        return $normalized !== false ? $normalized : trim($str);
    }
    
    /**
     * LIKE句で使用するためのエスケープ処理
     * @param string $str エスケープする文字列
     * @return string エスケープされた文字列
     */
    private function escapeForLike($str) {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $str);
    }
    
    /**
     * ひらがな行の範囲マップを取得
     * @return array 範囲マップ
     */
    private function getHiraganaRangeMap() {
        return [
            'あ' => ['start' => 'あ', 'end' => 'お゙'], // あ行: あいうえお
            'か' => ['start' => 'か', 'end' => 'ご゙'], // か行: かきくけこ
            'さ' => ['start' => 'さ', 'end' => 'ぞ゙'], // さ行: さしすせそ
            'た' => ['start' => 'た', 'end' => 'ど゙'], // た行: たちつてと
            'な' => ['start' => 'な', 'end' => 'の゙'], // な行: なにぬねの
            'は' => ['start' => 'は', 'end' => 'ぽ゙'], // は行: はひふへほ + ばびぶべぼ + ぱぴぷぺぽ
            'ま' => ['start' => 'ま', 'end' => 'も゙'], // ま行: まみむめも
            'や' => ['start' => 'や', 'end' => 'よ゙'], // や行: やゆよ
            'ら' => ['start' => 'ら', 'end' => 'ろ゙'], // ら行: らりるれろ
            'わ' => ['start' => 'わ', 'end' => 'ん゙']  // わ行: わをん
        ];
    }
    
    /**
     * 行と段から具体的な文字を取得
     * @param string $row 行（あ、か、さ...）
     * @param string $column 段（あ、い、う、え、お）
     * @return string|null 具体的な文字
     */
    private function getSpecificCharacter($row, $column) {
        $characterMap = [
            'あ' => ['あ' => 'あ', 'い' => 'い', 'う' => 'う', 'え' => 'え', 'お' => 'お'],
            'か' => ['あ' => 'か', 'い' => 'き', 'う' => 'く', 'え' => 'け', 'お' => 'こ'],
            'さ' => ['あ' => 'さ', 'い' => 'し', 'う' => 'す', 'え' => 'せ', 'お' => 'そ'],
            'た' => ['あ' => 'た', 'い' => 'ち', 'う' => 'つ', 'え' => 'て', 'お' => 'と'],
            'な' => ['あ' => 'な', 'い' => 'に', 'う' => 'ぬ', 'え' => 'ね', 'お' => 'の'],
            'は' => ['あ' => 'は', 'い' => 'ひ', 'う' => 'ふ', 'え' => 'へ', 'お' => 'ほ'],
            'ま' => ['あ' => 'ま', 'い' => 'み', 'う' => 'む', 'え' => 'め', 'お' => 'も'],
            'や' => ['あ' => 'や', 'い' => 'い', 'う' => 'ゆ', 'え' => 'え', 'お' => 'よ'], // やゆよ行
            'ら' => ['あ' => 'ら', 'い' => 'り', 'う' => 'る', 'え' => 'れ', 'お' => 'ろ'],
            'わ' => ['あ' => 'わ', 'い' => 'い', 'う' => 'う', 'え' => 'え', 'お' => 'を'] // わ行
        ];
        
        return isset($characterMap[$row][$column]) ? $characterMap[$row][$column] : null;
    }
}

/**
 * 在庫管理専用例外クラス
 */
class InventoryException extends Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
?>