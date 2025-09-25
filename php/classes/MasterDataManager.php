<?php
// 呼び出し元に関係なく正しいパスを設定
$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    // admin.phpから呼ばれた場合の相対パス
    $configPath = dirname(__FILE__) . '/../config/database.php';
}
require_once $configPath;
require_once __DIR__ . '/SimpleLogger.php';

/**
 * マスターデータ管理クラス
 */
class MasterDataManager {
    private $pdo;
    private $logger;
    
    public function __construct() {
        $this->pdo = DatabaseConfig::getConnection();
        $this->logger = new SimpleLogger();
    }
    
    /**
     * テキストデータからマスターデータを更新する
     * @param string $textData タブ区切りまたはCSV形式のテキスト
     * @param bool $replaceAll 全置換かどうか
     * @return array 処理結果
     */
    public function importFromText($textData, $replaceAll = false) {
        try {
            $this->logger->info('Starting import', ['replaceAll' => $replaceAll]);
            
            $lines = array_filter(array_map('trim', explode("\n", $textData)));
            if (empty($lines)) {
                return ['status' => 'error', 'message' => 'データが空です。'];
            }
            
            $parsedData = [];
            $errors = [];
            
            foreach ($lines as $lineNum => $line) {
                // タブ区切りまたはカンマ区切りを検出
                $columns = preg_split('/\t|,/', $line);
                
                if (count($columns) < 3) {
                    $errors[] = "行" . ($lineNum + 1) . ": 列数が不足しています";
                    continue;
                }
                
                $code1 = trim($columns[0], '"');
                $code2 = trim($columns[1], '"');
                $productName = trim($columns[2], '"');
                
                if (empty($code1) || empty($code2) || empty($productName)) {
                    $errors[] = "行" . ($lineNum + 1) . ": 必要な項目が空です";
                    continue;
                }
                
                $parsedData[] = [
                    'code1' => $code1,
                    'code2' => $code2,
                    'product_name' => $productName
                ];
            }
            
            if (empty($parsedData)) {
                return ['status' => 'error', 'message' => '有効なデータがありません。', 'errors' => $errors];
            }
            
            $this->pdo->beginTransaction();
            
            // 全置換の場合、既存データを削除
            if ($replaceAll) {
                $this->pdo->exec("DELETE FROM master_products");
            }
            
            // データ挿入
            $stmt = $this->pdo->prepare("
                INSERT INTO master_products (code1, code2, product_name) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    product_name = VALUES(product_name),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $insertedCount = 0;
            foreach ($parsedData as $data) {
                $stmt->execute([$data['code1'], $data['code2'], $data['product_name']]);
                $insertedCount++;
            }
            
            $this->pdo->commit();
            
            $this->logger->info('Import completed', [
                'insertedCount' => $insertedCount, 
                'replaceAll' => $replaceAll,
                'errorCount' => count($errors)
            ]);
            
            return [
                'status' => 'success',
                'message' => "{$insertedCount}件のデータを" . ($replaceAll ? '置換' : '更新') . "しました。",
                'inserted' => $insertedCount,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            $this->logger->error('Import failed', ['error' => $e->getMessage()]);
            error_log('Import error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'インポートに失敗しました: ' . $e->getMessage()];
        }
    }
    
    /**
     * マスターデータの件数を取得
     * @return int データ件数
     */
    public function getMasterDataCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM master_products");
            return $stmt->fetch()['count'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * マスターデータのサンプルを取得
     * @param int $limit 取得件数
     * @return array サンプルデータ
     */
    public function getMasterDataSample($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT code1, code2, product_name, updated_at 
                FROM master_products 
                ORDER BY updated_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * マスターデータを全削除
     * @return array 処理結果
     */
    public function clearMasterData() {
        try {
            $this->logger->info('Clearing all master data');
            $this->pdo->exec("DELETE FROM master_products");
            $this->logger->info('All master data cleared successfully');
            return ['status' => 'success', 'message' => 'マスターデータを全削除しました。'];
        } catch (Exception $e) {
            $this->logger->error('Failed to clear master data', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => '削除に失敗しました。'];
        }
    }
}
?>