<?php
/**
 * スプレッドシートからデータベースへの移行スクリプト
 * 
 * 使用方法:
 * 1. Google スプレッドシートから CSV エクスポート
 * 2. migration_data フォルダに以下のファイルを配置:
 *    - master_data.csv (マスターデータ)
 *    - inventory_data.csv (棚卸データ)
 * 3. php migration.php を実行
 */

require_once '../php/config/database.php';

class DataMigration {
    private $pdo;
    
    public function __construct() {
        $this->pdo = DatabaseConfig::getConnection();
    }
    
    /**
     * CSVファイルからマスターデータを移行
     * @param string $csvFile CSVファイルパス
     * @return array 結果
     */
    public function migrateMasterData($csvFile) {
        if (!file_exists($csvFile)) {
            return ['status' => 'error', 'message' => 'マスターデータCSVが見つかりません: ' . $csvFile];
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // 既存データをクリア
            $this->pdo->exec("DELETE FROM master_products");
            
            $handle = fopen($csvFile, 'r');
            $stmt = $this->pdo->prepare("
                INSERT INTO master_products (code1, code2, product_name) 
                VALUES (?, ?, ?)
            ");
            
            $count = 0;
            $errors = [];
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // 最低3列必要
                if (count($data) < 3) {
                    $errors[] = "行" . ($count + 1) . ": 列数が不足";
                    continue;
                }
                
                $code1 = trim($data[0]);
                $code2 = trim($data[1]);
                $productName = trim($data[2]);
                
                if (empty($code1) || empty($code2) || empty($productName)) {
                    $errors[] = "行" . ($count + 1) . ": 必要データが空";
                    continue;
                }
                
                $stmt->execute([$code1, $code2, $productName]);
                $count++;
            }
            
            fclose($handle);
            $this->pdo->commit();
            
            return [
                'status' => 'success',
                'message' => "マスターデータ {$count}件を移行しました。",
                'count' => $count,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            return ['status' => 'error', 'message' => 'マスターデータ移行エラー: ' . $e->getMessage()];
        }
    }
    
    /**
     * CSVファイルから棚卸データを移行
     * @param string $csvFile CSVファイルパス
     * @return array 結果
     */
    public function migrateInventoryData($csvFile) {
        if (!file_exists($csvFile)) {
            return ['status' => 'error', 'message' => '棚卸データCSVが見つかりません: ' . $csvFile];
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // 既存データをクリア
            $this->pdo->exec("DELETE FROM inventory_items");
            
            $handle = fopen($csvFile, 'r');
            $stmt = $this->pdo->prepare("
                INSERT INTO inventory_items (created_at, code, product_name) 
                VALUES (?, ?, ?)
            ");
            
            $count = 0;
            $errors = [];
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // 最低3列必要
                if (count($data) < 3) {
                    $errors[] = "行" . ($count + 1) . ": 列数が不足";
                    continue;
                }
                
                $createdAt = trim($data[0]);
                $code = trim($data[1]);
                $productName = trim($data[2]);
                
                if (empty($code) || empty($productName)) {
                    $errors[] = "行" . ($count + 1) . ": 必要データが空";
                    continue;
                }
                
                // 日付の変換
                if (empty($createdAt) || !strtotime($createdAt)) {
                    $createdAt = date('Y-m-d H:i:s');
                } else {
                    $createdAt = date('Y-m-d H:i:s', strtotime($createdAt));
                }
                
                $stmt->execute([$createdAt, $code, $productName]);
                $count++;
            }
            
            fclose($handle);
            $this->pdo->commit();
            
            return [
                'status' => 'success',
                'message' => "棚卸データ {$count}件を移行しました。",
                'count' => $count,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            return ['status' => 'error', 'message' => '棚卸データ移行エラー: ' . $e->getMessage()];
        }
    }
}

// スクリプトとして実行される場合
if (php_sapi_name() === 'cli') {
    echo "=== 棚卸システム データ移行スクリプト ===\n\n";
    
    $migration = new DataMigration();
    
    // マスターデータの移行
    echo "マスターデータを移行中...\n";
    $result = $migration->migrateMasterData(__DIR__ . '/migration_data/master_data.csv');
    echo $result['message'] . "\n";
    
    if (!empty($result['errors'])) {
        echo "警告:\n";
        foreach ($result['errors'] as $error) {
            echo "  - " . $error . "\n";
        }
    }
    
    echo "\n";
    
    // 棚卸データの移行
    echo "棚卸データを移行中...\n";
    $result = $migration->migrateInventoryData(__DIR__ . '/migration_data/inventory_data.csv');
    echo $result['message'] . "\n";
    
    if (!empty($result['errors'])) {
        echo "警告:\n";
        foreach ($result['errors'] as $error) {
            echo "  - " . $error . "\n";
        }
    }
    
    echo "\n移行完了！\n";
}
?>