# 棚卸システム - レンタルサーバー移行版

GASからロリポップサーバーに移行した棚卸管理システムです。

## 📁 ファイル構成

```
├── index.php              # メイン入力画面
├── unfound.php             # 未発見リスト画面
├── admin.php               # マスターデータ管理画面（⭐Accessデータ貼り付け）
├── .htaccess              # Apache設定
├── php/
│   ├── config/
│   │   └── database.php   # DB接続設定
│   ├── classes/
│   │   ├── InventoryAPI.php      # 棚卸API
│   │   └── MasterDataManager.php # マスター管理
│   └── api/
│       └── index.php      # APIエンドポイント
├── sql/
│   └── create_tables.sql  # テーブル作成SQL
└── migration/
    ├── migration.php      # 移行スクリプト
    ├── migration_web.php  # Web移行ツール
    └── README.md          # 移行手順
```

## 🚀 デプロイ手順

### 1. データベース設定

1. ロリポップの「データベース」から新しいデータベースを作成
2. `php/config/database.php` を実際の接続情報に更新:

```php
const DB_HOST = 'mysql-5-7.lolipop.jp'; // 実際のホスト
const DB_NAME = 'LAA1234567-zaiko';      // 実際のDB名
const DB_USER = 'LAA1234567';            // 実際のユーザー名
const DB_PASS = 'your-password';         // 実際のパスワード
```

### 2. テーブル作成

ロリポップの phpMyAdmin で `sql/create_tables.sql` を実行

### 3. ファイルアップロード

SFTP設定（`.vscode/sftp.json`）を使用してファイルをアップロード

### 4. 初期データ移行

- 方法A: `migration/migration_web.php` にアクセスしてWeb画面で移行
- 方法B: SSH接続して `php migration/migration.php` を実行

### 5. 権限設定

```bash
chmod 755 *.php
chmod 755 php/
chmod 644 .htaccess
```

## 🌟 主な機能

### 棚卸入力機能
- **スペース商品**: ひらがなコード入力（例：ま38-6）
- **委託商品**: カタカナコード入力（例：ｶ8-611）
- **リアルタイム変換**: 全角→半角自動変換
- **入力候補**: 未発見商品のオートサジェスト

### 未発見リスト機能
- **ページネーション**: 50件ずつ表示
- **発見ボタン**: ワンクリックで棚卸済み登録
- **リアルタイム更新**: 発見済み商品の即座な表示切り替え

### マスターデータ管理機能 ⭐
- **Access連携**: `admin.php` でコピー&ペーストでデータ更新
- **一括処理**: 全置換・追加更新の選択可能
- **データ検証**: エラーチェック機能付き

#### Accessからのデータ貼り付け手順：
1. **`admin.php`** にアクセス
2. Accessで該当する列（コード1、コード2、商品名）を選択してコピー（Ctrl+C）
3. テキストエリアに貼り付け（Ctrl+V）
4. 「データを更新」ボタンをクリック

### API機能
- `POST /api/items` - 商品追加
- `GET /api/unfound` - 未発見リスト取得
- `GET /api/suggestions` - 検索候補取得
- `GET /api/export` - Access用エクスポート

## 🔧 メンテナンス

### ログ確認
```bash
tail -f error.log
```

### データベースバックアップ
```bash
mysqldump -u [username] -p [database_name] > backup.sql
```

### パフォーマンス監視
- API レスポンス時間の確認
- データベースクエリの最適化
- キャッシュの効果測定

## 🛡️ セキュリティ

- SQLインジェクション対策（PDO プリペアドステートメント）
- XSS対策（htmlspecialchars）
- ファイルアップロード制限
- ディレクトリリスティング無効化

## 📱 ブラウザ対応

- Chrome, Firefox, Safari, Edge
- モバイル対応（レスポンシブデザイン）
- PWA対応準備済み

## ⚡ パフォーマンス

- データベースインデックス最適化
- GZIP圧縮有効化
- 静的ファイルキャッシュ
- APIレスポンス最適化

## 🆘 トラブルシューティング

### データベース接続エラー
1. 接続情報の確認
2. ホスト名・ポート番号の確認
3. ユーザー権限の確認

### API エラー
1. `.htaccess` の RewriteRule 確認
2. PHP エラーログの確認
3. CORS設定の確認

### ファイルアップロードエラー
1. ファイル権限の確認
2. upload_max_filesize の確認
3. ディスク容量の確認

## 📞 サポート

問題が発生した場合は、以下の情報を含めてお問い合わせください：
- エラーメッセージ
- 操作手順
- ブラウザ・OS情報
- エラーログ# -
