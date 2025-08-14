START TRANSACTION;

-- Nonaktifkan foreign key checks sementara
SET FOREIGN_KEY_CHECKS = 0;

-- Cek apakah tabel products ada
SELECT COUNT(*) INTO @products_exists 
FROM information_schema.tables 
WHERE table_schema = 'topsis_ahp_shop' 
AND table_name = 'products';

-- Jika tabel ada, tambahkan kolom yang diperlukan
SET @sql = IF(@products_exists = 1,
    'ALTER TABLE products 
     ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL,
        image VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Aktifkan kembali foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;