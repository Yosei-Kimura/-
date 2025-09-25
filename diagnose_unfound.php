<?php
/**
 * 未発見リストAPI診断スクリプト
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>未発見リストAPI診断</h2>\n";
echo "<pre>\n";

try {
    echo "1. ファイル存在確認...\n";
    $files = [
        'php/api/index.php',
        'php/classes/InventoryAPI.php', 
        'php/classes/SimpleLogger.php',
        'php/config/database.php'
    ];
    
    foreach ($files as $file) {
        echo "  - {$file}: " . (file_exists($file) ? "✅" : "❌") . "\n";
    }
    
    echo "\n2. データベース接続テスト...\n";
    require_once 'php/config/database.php';
    try {
        $pdo = DatabaseConfig::getConnection();
        echo "  - データベース接続: ✅\n";
        
        // テーブル存在確認
        echo "\n3. テーブル存在確認...\n";
        $tables = ['master_products', 'inventory_items'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            echo "  - テーブル {$table}: " . ($stmt->rowCount() > 0 ? "✅" : "❌") . "\n";
        }
        
        // マスターデータ件数確認
        echo "\n4. マスターデータ件数...\n";
        $stmt = $pdo->query("SELECT COUNT(*) FROM master_products");
        $masterCount = $stmt->fetchColumn();
        echo "  - master_products レコード数: {$masterCount}\n";
        
        // 棚卸データ件数確認
        $stmt = $pdo->query("SELECT COUNT(*) FROM inventory_items");
        $inventoryCount = $stmt->fetchColumn();
        echo "  - inventory_items レコード数: {$inventoryCount}\n";
        
    } catch (Exception $e) {
        echo "  - データベース接続: ❌\n";
        echo "  - エラー: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    echo "\n5. API初期化テスト...\n";
    require_once 'php/classes/InventoryAPI.php';
    $api = new InventoryAPI();
    echo "  - InventoryAPI初期化: ✅\n";
    
    echo "\n6. 未発見リストメソッドテスト...\n";
    $result = $api->getUnfoundItems(0, 5);
    echo "  - getUnfoundItems実行: ✅\n";
    echo "  - 取得件数: " . count($result['items']) . "\n";
    echo "  - 全体件数: " . $result['total'] . "\n";
    
    echo "\n7. レスポンス内容:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "\n❌ エラーが発生しました:\n";
    echo "メッセージ: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行番号: " . $e->getLine() . "\n";
    echo "\nスタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
?>