<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>API直接アクセステスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-link { display: block; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; text-decoration: none; color: #333; }
        .test-link:hover { background: #e9ecef; }
        iframe { width: 100%; height: 400px; border: 1px solid #ccc; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>API直接アクセステスト</h1>
        
        <p>以下のリンクをクリックして、APIの実際のレスポンスを確認してください:</p>
        
        <a href="php/api/unfound?offset=0&limit=5" class="test-link" target="_blank">
            📋 未発見リストAPI: /php/api/unfound?offset=0&limit=5
        </a>
        
        <a href="php/api/suggestions?prefix=ま" class="test-link" target="_blank">
            🔍 検索候補API: /php/api/suggestions?prefix=ま
        </a>
        
        <div style="margin-top: 30px;">
            <h3>未発見リストAPIレスポンス:</h3>
            <iframe src="php/api/unfound?offset=0&limit=5"></iframe>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>検索候補APIレスポンス:</h3>
            <iframe src="php/api/suggestions?prefix=ま"></iframe>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>PHPファイル存在確認:</h3>
            <?php
            $files = [
                'php/api/index.php' => 'APIエンドポイント',
                'php/classes/InventoryAPI.php' => 'API処理クラス',
                'php/config/database.php' => 'データベース設定'
            ];
            
            echo '<ul>';
            foreach ($files as $file => $description) {
                $exists = file_exists($file);
                echo '<li>' . $description . ' (' . $file . '): ';
                echo $exists ? '✅ 存在' : '❌ 存在しない';
                echo '</li>';
            }
            echo '</ul>';
            ?>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>.htaccessの確認:</h3>
            <?php
            if (file_exists('.htaccess')) {
                echo '<p>✅ .htaccess ファイルが存在します</p>';
                echo '<pre style="background: #f8f9fa; padding: 10px;">';
                echo htmlspecialchars(file_get_contents('.htaccess'));
                echo '</pre>';
            } else {
                echo '<p>❌ .htaccess ファイルが見つかりません</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>