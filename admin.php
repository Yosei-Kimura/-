<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マスターデータ管理</title>
    <style>
        :root { --accent-color: #4a90e2; --success-color: #28a745; --danger-color: #dc3545; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            margin: 0; padding: 20px; background-color: #f8f9fa;
        }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { text-align: center; color: #333; margin-bottom: 2rem; }
        .card {
            background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem; overflow: hidden;
        }
        .card-header {
            background: var(--accent-color); color: white; padding: 1rem; font-weight: bold; font-size: 1.1rem;
        }
        .card-body { padding: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; }
        textarea {
            width: 100%; min-height: 200px; padding: 1rem; border: 1px solid #ddd; border-radius: 4px;
            font-family: 'Courier New', monospace; font-size: 14px; resize: vertical;
        }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .btn {
            padding: 0.75rem 1.5rem; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;
            transition: background-color 0.2s; text-decoration: none; display: inline-block;
        }
        .btn-primary { background: var(--accent-color); color: white; }
        .btn-primary:hover { background: #357abd; }
        .btn-success { background: var(--success-color); color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: var(--danger-color); color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .alert {
            padding: 1rem; border-radius: 4px; margin-bottom: 1rem;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card {
            background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number { font-size: 2rem; font-weight: bold; color: var(--accent-color); }
        .stat-label { color: #666; margin-top: 0.5rem; }
        .sample-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .sample-table th, .sample-table td { padding: 0.75rem; border: 1px solid #ddd; text-align: left; }
        .sample-table th { background: #f8f9fa; font-weight: 600; }
        .error-list { background: #f8d7da; padding: 1rem; border-radius: 4px; margin-top: 1rem; }
        .error-list ul { margin: 0; padding-left: 1.5rem; }
        .nav-links { text-align: center; margin-top: 2rem; }
        .nav-links a { margin: 0 0.5rem; }
        .help-text { color: #666; font-size: 0.9rem; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>マスターデータ管理</h1>

        <?php
        // エラー表示を有効にする
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        try {
            require_once 'php/classes/MasterDataManager.php';
            $manager = new MasterDataManager();
            $message = '';
            $messageType = '';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">初期化エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $manager = null;
        }
        
        // フォーム処理
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $manager !== null) {
            if (isset($_POST['action'])) {
                try {
                    switch ($_POST['action']) {
                        case 'import':
                            $textData = $_POST['textData'] ?? '';
                            $replaceAll = isset($_POST['replaceAll']);
                            $result = $manager->importFromText($textData, $replaceAll);
                            $message = $result['message'];
                            $messageType = $result['status'];
                            if (!empty($result['errors'])) {
                                $message .= '<div class="error-list"><strong>警告:</strong><ul>';
                                foreach ($result['errors'] as $error) {
                                    $message .= '<li>' . htmlspecialchars($error) . '</li>';
                                }
                                $message .= '</ul></div>';
                            }
                            break;
                            
                        case 'clear':
                            $result = $manager->clearMasterData();
                            $message = $result['message'];
                            $messageType = $result['status'];
                            break;
                    }
                } catch (Exception $e) {
                    $message = 'エラーが発生しました: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
        
        // 統計情報の取得
        $totalCount = 0;
        $sampleData = [];
        if ($manager !== null) {
            try {
                $totalCount = $manager->getMasterDataCount();
                $sampleData = $manager->getMasterDataSample(5);
            } catch (Exception $e) {
                $message = '統計情報の取得に失敗しました: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
        ?>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- 統計情報 -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($totalCount) ?></div>
                <div class="stat-label">登録商品数</div>
            </div>
        </div>

        <!-- データインポート -->
        <div class="card">
            <div class="card-header">データインポート</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="import">
                    
                    <div class="form-group">
                        <label for="textData">Accessからコピーしたデータを貼り付け</label>
                        <textarea name="textData" id="textData" placeholder="コード1	コード2	商品名
ま38	6	サンプル商品1
ｶ8	611	サンプル商品2" required></textarea>
                        <div class="help-text">
                            Accessで該当する列を選択してコピー（Ctrl+C）し、上記エリアに貼り付けてください。<br>
                            タブ区切りまたはカンマ区切り（CSV）形式に対応しています。
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="replaceAll" name="replaceAll">
                        <label for="replaceAll">既存データを全て置換する（チェックしない場合は追加・更新）</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">データを更新</button>
                </form>
            </div>
        </div>

        <!-- データサンプル表示 -->
        <?php if (!empty($sampleData)): ?>
        <div class="card">
            <div class="card-header">最新データサンプル（最新5件）</div>
            <div class="card-body">
                <table class="sample-table">
                    <thead>
                        <tr>
                            <th>コード1</th>
                            <th>コード2</th>
                            <th>商品名</th>
                            <th>更新日時</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sampleData as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['code1']) ?></td>
                            <td><?= htmlspecialchars($item['code2']) ?></td>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= $item['updated_at'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- 危険操作 -->
        <div class="card">
            <div class="card-header" style="background: var(--danger-color);">危険操作</div>
            <div class="card-body">
                <p><strong>注意:</strong> 以下の操作は元に戻せません。</p>
                <form method="post" onsubmit="return confirm('本当にすべてのマスターデータを削除しますか？この操作は元に戻せません。');">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-danger">全データ削除</button>
                </form>
            </div>
        </div>

        <!-- ナビゲーション -->
        <div class="nav-links">
            <a href="index.php" class="btn btn-secondary">棚卸入力に戻る</a>
            <a href="unfound.php" class="btn btn-secondary">未発見リストを見る</a>
        </div>
    </div>
</body>
</html>