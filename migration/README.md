# データ移行手順

## 1. スプレッドシートからCSVエクスポート

### マスターデータの準備
1. Google スプレッドシートの「マスターデータ」シートを開く
2. A列（コード1）、B列（コード2）、C列（商品名）をすべて選択
3. ファイル > ダウンロード > CSV形式でダウンロード
4. `master_data.csv` として保存

### 棚卸データの準備
1. Google スプレッドシートの「棚卸データ」シートを開く
2. A列（日時）、B列（コード）、C列（商品名）をすべて選択
3. ファイル > ダウンロード > CSV形式でダウンロード
4. `inventory_data.csv` として保存

## 2. データベースの準備

1. ロリポップのデータベース管理画面でデータベースを作成
2. `../sql/create_tables.sql` を実行してテーブルを作成

```sql
-- データベース作成例（ロリポップ管理画面で実行）
-- 実際のデータベース名、ユーザー名、パスワードは管理画面で確認
```

## 3. 設定ファイルの更新

`../php/config/database.php` を実際のデータベース情報に更新:

```php
const DB_HOST = 'mysql-5-7.lolipop.jp'; // 実際のホスト名
const DB_NAME = 'LAA1234567-zaiko'; // 実際のDB名
const DB_USER = 'LAA1234567'; // 実際のユーザー名
const DB_PASS = 'your-password'; // 実際のパスワード
```

## 4. CSVファイルの配置

1. このフォルダに `migration_data` フォルダを作成
2. エクスポートしたCSVファイルを配置:
   - `migration_data/master_data.csv`
   - `migration_data/inventory_data.csv`

## 5. 移行の実行

### コマンドラインから実行:
```bash
cd migration
php migration.php
```

### ブラウザから実行:
`migration_web.php` にアクセスして、Web画面から移行を実行

## 6. 確認

移行後、`../admin.php` で以下を確認:
- マスターデータ件数が正しいか
- サンプルデータが正しく表示されるか

## エラーが発生した場合

1. データベース接続情報を確認
2. CSVファイルの形式を確認（UTF-8エンコード推奨）
3. 権限エラーの場合、ファイルの読み取り権限を確認
4. ログファイル（error.log）を確認