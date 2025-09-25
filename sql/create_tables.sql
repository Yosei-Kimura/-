-- 棚卸システム用データベーステーブル作成SQL

-- マスターデータテーブル
CREATE TABLE IF NOT EXISTS master_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code1 VARCHAR(20) NOT NULL COMMENT 'コード1',
    code2 VARCHAR(20) NOT NULL COMMENT 'コード2', 
    product_name VARCHAR(255) NOT NULL COMMENT '商品名',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_code (code1, code2),
    INDEX idx_code1 (code1),
    INDEX idx_product_name (product_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='マスター商品データ';

-- 棚卸データテーブル
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL COMMENT '商品コード（code1-code2形式）',
    product_name VARCHAR(255) NOT NULL COMMENT '商品名',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '棚卸日時',
    INDEX idx_code (code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='棚卸データ';

-- 初期データ挿入用（テスト用）
-- INSERT INTO master_products (code1, code2, product_name) VALUES
-- ('ま38', '6', 'テスト商品1'),
-- ('ｶ8', '611', 'テスト商品2');