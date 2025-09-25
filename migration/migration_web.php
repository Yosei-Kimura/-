<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>データ移行</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; }
        h1 { color: #333; text-align: center; }
        .step { background: #f8f9fa; padding: 1rem; margin: 1rem 0; border-radius: 4px; border-left: 4px solid #007bff; }
        .btn { padding: 0.75rem 1.5rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 0.5rem; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .result { padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .file-input { margin: 1rem 0; }
        .progress { display: none; text-align: center; margin: 1rem 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>データ移行ツール</h1>
        
        <?php
        require_once '../php/config/database.php';
        require_once 'migration.php';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $migration = new DataMigration();
            
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'migrate_master':
                        if (isset($_FILES['master_csv']) && $_FILES['master_csv']['error'] === UPLOAD_ERR_OK) {
                            $result = $migration->migrateMasterData($_FILES['master_csv']['tmp_name']);
                            echo '<div class="result ' . ($result['status'] === 'success' ? 'success' : 'error') . '">';
                            echo $result['message'];
                            if (!empty($result['errors'])) {
                                echo '<ul>';
                                foreach ($result['errors'] as $error) {
                                    echo '<li>' . htmlspecialchars($error) . '</li>';
                                }
                                echo '</ul>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="result error">CSVファイルがアップロードされませんでした。</div>';
                        }
                        break;
                        
                    case 'migrate_inventory':
                        if (isset($_FILES['inventory_csv']) && $_FILES['inventory_csv']['error'] === UPLOAD_ERR_OK) {
                            $result = $migration->migrateInventoryData($_FILES['inventory_csv']['tmp_name']);
                            echo '<div class="result ' . ($result['status'] === 'success' ? 'success' : 'error') . '">';
                            echo $result['message'];
                            if (!empty($result['errors'])) {
                                echo '<ul>';
                                foreach ($result['errors'] as $error) {
                                    echo '<li>' . htmlspecialchars($error) . '</li>';
                                }
                                echo '</ul>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="result error">CSVファイルがアップロードされませんでした。</div>';
                        }
                        break;
                }
            }
        }
        ?>
        
        <div class="step">
            <h3>ステップ1: マスターデータの移行</h3>
            <p>スプレッドシートの「マスターデータ」シートをCSV形式でエクスポートし、アップロードしてください。</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="migrate_master">
                <div class="file-input">
                    <label>マスターデータCSV:</label>
                    <input type="file" name="master_csv" accept=".csv" required>
                </div>
                <button type="submit" class="btn" onclick="this.innerHTML='移行中...'; this.disabled=true;">マスターデータを移行</button>
            </form>
        </div>
        
        <div class="step">
            <h3>ステップ2: 棚卸データの移行</h3>
            <p>スプレッドシートの「棚卸データ」シートをCSV形式でエクスポートし、アップロードしてください。</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="migrate_inventory">
                <div class="file-input">
                    <label>棚卸データCSV:</label>
                    <input type="file" name="inventory_csv" accept=".csv" required>
                </div>
                <button type="submit" class="btn" onclick="this.innerHTML='移行中...'; this.disabled=true;">棚卸データを移行</button>
            </form>
        </div>
        
        <div class="step">
            <h3>移行完了後</h3>
            <p>移行が完了したら、以下のリンクで動作確認を行ってください:</p>
            <a href="../admin.php" class="btn">管理画面で確認</a>
            <a href="../index.php" class="btn">棚卸入力画面</a>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="../index.php">← 棚卸システムに戻る</a>
        </div>
    </div>
</body>
</html>