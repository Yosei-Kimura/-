<?php
/**
 * マスターデータ構造確認スクリプト
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>マスターデータ構造確認</h2>\n";
echo "<pre>\n";

try {
    require_once 'php/config/database.php';
    $pdo = DatabaseConfig::getConnection();
    
    echo "=== 「き」で始まる商品コードのサンプル ===\n";
    $stmt = $pdo->prepare("
        SELECT code1, code2, CONCAT(code1, '-', code2) as full_code, product_name
        FROM master_products 
        WHERE code1 = 'き'
        LIMIT 10
    ");
    $stmt->execute();
    $kiProducts = $stmt->fetchAll();
    
    echo "「き」で始まる商品数: " . count($kiProducts) . "件\n";
    foreach ($kiProducts as $product) {
        echo "  - {$product['full_code']} : {$product['product_name']}\n";
    }
    
    echo "\n=== 「き」で始まる未発見商品 ===\n";
    $stmt = $pdo->prepare("
        SELECT CONCAT(m.code1, '-', m.code2) as code, m.product_name as name
        FROM master_products m 
        WHERE m.code1 = 'き'
        AND NOT EXISTS (
            SELECT 1 FROM inventory_items i 
            WHERE i.code = CONCAT(m.code1, '-', m.code2)
        )
        LIMIT 10
    ");
    $stmt->execute();
    $unfoundKiProducts = $stmt->fetchAll();
    
    echo "「き」で始まる未発見商品数: " . count($unfoundKiProducts) . "件\n";
    foreach ($unfoundKiProducts as $product) {
        echo "  - {$product['code']} : {$product['name']}\n";
    }
    
    echo "\n=== か行全体のサンプル ===\n";
    $stmt = $pdo->prepare("
        SELECT code1, COUNT(*) as count
        FROM master_products 
        WHERE code1 >= 'か' AND code1 < 'ご゙'
        GROUP BY code1
        ORDER BY code1
        LIMIT 10
    ");
    $stmt->execute();
    $kaProducts = $stmt->fetchAll();
    
    foreach ($kaProducts as $product) {
        echo "  - {$product['code1']}: {$product['count']}件\n";
    }
    
} catch (Exception $e) {
    echo "エラーが発生しました:\n";
    echo "メッセージ: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行番号: " . $e->getLine() . "\n";
}

echo "</pre>\n";
?>