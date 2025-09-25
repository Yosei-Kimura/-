<?php
/**
 * データベースセットアップスクリプト
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>データベースセットアップ</h2>\n";
echo "<pre>\n";

try {
    echo "1. データベース設定ファイル読み込み...\n";
    require_once 'php/config/database.php';
    
    echo "2. データベース接続...\n";
    $pdo = DatabaseConfig::getConnection();
    echo "  - データベース接続: ✅\n";
    
    echo "\n3. SQLファイル読み込み...\n";
    $sqlFile = 'sql/create_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQLファイルが見つかりません: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "  - SQLファイル読み込み: ✅\n";
    
    echo "\n4. テーブル作成実行...\n";
    
    // SQLを分割して実行
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
    );
    
    foreach ($statements as $statement) {
        if (preg_match('/CREATE TABLE/i', $statement)) {
            try {
                $pdo->exec($statement);
                $tableName = '';
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?\s*\(/i', $statement, $matches)) {
                    $tableName = $matches[1];
                }
                echo "  - テーブル作成: {$tableName} ✅\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "  - テーブル既存: {$tableName} ⚠️\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    
    echo "\n5. テーブル存在確認...\n";
    $tables = ['master_products', 'inventory_items'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        echo "  - {$table}: " . ($stmt->rowCount() > 0 ? "✅" : "❌") . "\n";
    }
    
    echo "\n6. テーブル構造確認...\n";
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll();
            echo "  - {$table} (カラム数: " . count($columns) . "): ✅\n";
        } catch (PDOException $e) {
            echo "  - {$table}: ❌ エラー: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n7. サンプルデータ挿入（テスト用）...\n";
    try {
        // 既存データ確認
        $stmt = $pdo->query("SELECT COUNT(*) FROM master_products");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $pdo->exec("
                INSERT INTO master_products (code1, code2, product_name) VALUES
                ('ま38', '6', 'テスト商品1'),
                ('ｶ8', '611', 'テスト商品2'),
                ('テ', '1', 'テスト商品3')
            ");
            echo "  - サンプルデータ挿入: ✅\n";
        } else {
            echo "  - マスターデータ既存: {$count}件 ⚠️\n";
        }
    } catch (PDOException $e) {
        echo "  - サンプルデータ挿入: ❌ エラー: " . $e->getMessage() . "\n";
    }
    
    echo "\nセットアップ完了！\n";
    
} catch (Exception $e) {
    echo "\n❌ セットアップエラー:\n";
    echo "メッセージ: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行番号: " . $e->getLine() . "\n";
}

echo "</pre>\n";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>データベースセットアップ</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; }
    </style>
</head>
<body></body>
</html>