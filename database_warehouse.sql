-- ============================================
-- DATABASE SISTEM MANAJEMEN GUDANG
-- ============================================

-- Buat Database
CREATE DATABASE IF NOT EXISTS warehouse_management;
USE warehouse_management;


CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'INACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS activation_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS reset_password_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_category (category),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO users (name, email, password, status) VALUES
('Admin Utama', 'admin@warehouse.com', 'admin123', 'ACTIVE'),
('John Doe', 'john@warehouse.com', 'password123', 'INACTIVE');

-- Insert sample products
INSERT INTO products (user_id, code, name, category, stock, price, description) VALUES
(1, 'PRD001', 'Laptop Dell XPS 13', 'Elektronik', 15, 15000000.00, 'Laptop premium untuk profesional'),
(1, 'PRD002', 'Mouse Logitech MX Master 3', 'Aksesoris', 50, 1200000.00, 'Mouse ergonomis wireless'),
(1, 'PRD003', 'Keyboard Mechanical Keychron K2', 'Aksesoris', 30, 1500000.00, 'Keyboard mechanical wireless'),
(1, 'PRD004', 'Monitor LG UltraWide 34"', 'Elektronik', 10, 8000000.00, 'Monitor ultrawide untuk produktivitas'),
(1, 'PRD005', 'Webcam Logitech C920', 'Aksesoris', 25, 1000000.00, 'Webcam HD untuk meeting');

-- Insert sample activation token (expires in 24 hours)
INSERT INTO activation_tokens (user_id, token, expires_at) VALUES
(2, 'sample_activation_token_123', DATE_ADD(NOW(), INTERVAL 24 HOUR));

-- ============================================
-- VIEWS (Untuk mempermudah query)
-- ============================================

-- View untuk melihat produk dengan informasi user
CREATE OR REPLACE VIEW v_products_detail AS
SELECT 
    p.id,
    p.code,
    p.name,
    p.category,
    p.stock,
    p.price,
    p.description,
    p.created_at,
    p.updated_at,
    u.name as created_by_name,
    u.email as created_by_email
FROM products p
JOIN users u ON p.user_id = u.id;

-- View untuk statistik produk per kategori
CREATE OR REPLACE VIEW v_product_stats AS
SELECT 
    category,
    COUNT(*) as total_products,
    SUM(stock) as total_stock,
    AVG(price) as avg_price,
    MIN(price) as min_price,
    MAX(price) as max_price
FROM products
GROUP BY category;

-- View untuk melihat user dengan jumlah produknya
CREATE OR REPLACE VIEW v_users_with_products AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.status,
    u.created_at,
    COUNT(p.id) as total_products
FROM users u
LEFT JOIN products p ON u.id = p.user_id
GROUP BY u.id, u.name, u.email, u.status, u.created_at;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- Procedure untuk aktivasi user
DELIMITER //
CREATE PROCEDURE sp_activate_user(
    IN p_token VARCHAR(255)
)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_expired BOOLEAN;
    
    -- Cek token
    SELECT user_id, (expires_at < NOW()) INTO v_user_id, v_expired
    FROM activation_tokens
    WHERE token = p_token
    LIMIT 1;
    
    IF v_user_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Token tidak valid';
    ELSEIF v_expired THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Token sudah expired';
    ELSE
        -- Update status user
        UPDATE users SET status = 'ACTIVE' WHERE id = v_user_id;
        
        -- Hapus token
        DELETE FROM activation_tokens WHERE token = p_token;
        
        SELECT 'Aktivasi berhasil' as message;
    END IF;
END //
DELIMITER ;

-- Procedure untuk reset password
DELIMITER //
CREATE PROCEDURE sp_reset_password(
    IN p_token VARCHAR(255),
    IN p_new_password VARCHAR(255)
)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_expired BOOLEAN;
    
    -- Cek token
    SELECT user_id, (expires_at < NOW()) INTO v_user_id, v_expired
    FROM reset_password_tokens
    WHERE token = p_token
    LIMIT 1;
    
    IF v_user_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Token tidak valid';
    ELSEIF v_expired THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Token sudah expired';
    ELSE
        -- Update password
        UPDATE users SET password = p_new_password WHERE id = v_user_id;
        
        -- Hapus token
        DELETE FROM reset_password_tokens WHERE token = p_token;
        
        SELECT 'Password berhasil direset' as message;
    END IF;
END //
DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger untuk log aktivitas saat produk ditambah
DELIMITER //
CREATE TRIGGER trg_product_insert
AFTER INSERT ON products
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, description)
    VALUES (NEW.user_id, 'CREATE_PRODUCT', CONCAT('Menambahkan produk: ', NEW.name));
END //
DELIMITER ;

-- Trigger untuk log aktivitas saat produk diupdate
DELIMITER //
CREATE TRIGGER trg_product_update
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, description)
    VALUES (NEW.user_id, 'UPDATE_PRODUCT', CONCAT('Mengupdate produk: ', NEW.name));
END //
DELIMITER ;

-- Trigger untuk log aktivitas saat produk dihapus
DELIMITER //
CREATE TRIGGER trg_product_delete
AFTER DELETE ON products
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, description)
    VALUES (OLD.user_id, 'DELETE_PRODUCT', CONCAT('Menghapus produk: ', OLD.name));
END //
DELIMITER ;


CREATE INDEX idx_products_stock ON products(stock);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_created_at ON products(created_at);
CREATE INDEX idx_users_created_at ON users(created_at);

