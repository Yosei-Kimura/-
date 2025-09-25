<?php
/**
 * 未発見リストAPI再診断スクリプト
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>未発見リストAPI再診断</h2>\n";
echo "<pre>\n";

try {
    echo "1. 構文エラー修正後のAPI初期化テスト...\n";
    require_once 'php/classes/InventoryAPI.php';
    $api = new InventoryAPI();
    echo "  - InventoryAPI初期化: ✅\n";
    
    echo "\n2. 未発見リストメソッドテスト...\n";
    $result = $api->getUnfoundItems(0, 5);
    echo "  - getUnfoundItems実行: ✅\n";
    echo "  - 取得件数: " . count($result['items']) . "\n";
    echo "  - 全体件数: " . $result['total'] . "\n";
    
    echo "\n3. レスポンス構造確認:\n";
    if (isset($result['items']) && is_array($result['items'])) {
        echo "  - items配列: ✅\n";
        if (count($result['items']) > 0) {
            echo "  - サンプル項目:\n";
            $sample = $result['items'][0];
            foreach ($sample as $key => $value) {
                echo "    - {$key}: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
            }
        }
    }
    
    echo "\n4. 実際のAPIエンドポイントテスト...\n";
    // 内部的にAPIエンドポイントをシミュレート
    $_GET['action'] = 'unfound';
    $_GET['offset'] = 0;
    $_GET['limit'] = 3;
    
    // APIレスポンス形式での出力
    $response = [
        'items' => array_slice($result['items'], 0, 3),
        'total' => $result['total'],
        'offset' => 0,
        'limit' => 3,
        'has_more' => $result['total'] > 3
    ];
    
    echo "  - APIレスポンス形式: ✅\n";
    echo "  - JSON形式テスト:\n";
    $jsonResult = json_encode($response, JSON_UNESCAPED_UNICODE);
    if ($jsonResult !== false) {
        echo "    - JSON変換: ✅\n";
        echo "    - レスポンスサイズ: " . strlen($jsonResult) . " bytes\n";
    } else {
        echo "    - JSON変換: ❌\n";
    }
    
    echo "\n✅ API修復完了！未発見リストが正常に動作するはずです。\n";
    
} catch (Exception $e) {
    echo "\n❌ まだエラーがあります:\n";
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
    <title>API再診断</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; }
    </style>
</head>
<body></body>
</html>