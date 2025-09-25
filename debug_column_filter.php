<?php
/**
 * 段フィルターデバッグスクリプト
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>段フィルターデバッグ</h2>\n";
echo "<pre>\n";

try {
    require_once 'php/classes/InventoryAPI.php';
    $api = new InventoryAPI();
    
    echo "=== テスト1: か行フィルターのみ ===\n";
    $result1 = $api->getUnfoundItems(0, 10, 'か', null);
    echo "結果: " . count($result1['items']) . "件 / 全" . $result1['total'] . "件\n";
    foreach ($result1['items'] as $item) {
        echo "  - " . $item['code'] . " : " . $item['name'] . "\n";
    }
    
    echo "\n=== テスト2: か行い段（き）フィルター ===\n";
    $result2 = $api->getUnfoundItems(0, 10, 'か', 'い');
    echo "結果: " . count($result2['items']) . "件 / 全" . $result2['total'] . "件\n";
    foreach ($result2['items'] as $item) {
        echo "  - " . $item['code'] . " : " . $item['name'] . "\n";
    }
    
    echo "\n=== テスト3: さ行う段（す）フィルター ===\n";
    $result3 = $api->getUnfoundItems(0, 10, 'さ', 'う');
    echo "結果: " . count($result3['items']) . "件 / 全" . $result3['total'] . "件\n";
    foreach ($result3['items'] as $item) {
        echo "  - " . $item['code'] . " : " . $item['name'] . "\n";
    }
    
    echo "\n=== デバッグ情報 ===\n";
    echo "か行い段で検索する文字: ";
    
    // バックエンドの文字マッピングをテスト
    $characterMap = [
        'か' => ['あ' => 'か', 'い' => 'き', 'う' => 'く', 'え' => 'け', 'お' => 'こ']
    ];
    echo $characterMap['か']['い'] . "\n";
    
} catch (Exception $e) {
    echo "エラーが発生しました:\n";
    echo "メッセージ: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行番号: " . $e->getLine() . "\n";
    echo "\nスタックトレース:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
?>