-- ================================================================
--  RETAIL CLOTHES SHOP — FULL DATABASE SETUP
--  Database : clothes_retail_db
--  Run this file in phpMyAdmin or MySQL CLI to set up everything.
--  All table and column names are unique to avoid conflicts.
-- ================================================================

CREATE DATABASE IF NOT EXISTS clothes_retail_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE clothes_retail_db;

-- ----------------------------------------------------------------
-- 1. ADMIN / STAFF USERS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shop_users (
    user_id      INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(60)  UNIQUE NOT NULL,
    user_pass    VARCHAR(255) NOT NULL,
    user_role    ENUM('admin','staff') DEFAULT 'staff',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin: admin / admin123
INSERT INTO shop_users (username, user_pass, user_role)
VALUES ('admin', MD5('admin123'), 'admin')
ON DUPLICATE KEY UPDATE user_id = user_id;

-- ----------------------------------------------------------------
-- 2. PRODUCTS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shop_products (
    prod_id          INT AUTO_INCREMENT PRIMARY KEY,
    prod_name        VARCHAR(120) NOT NULL,
    prod_category    VARCHAR(60)  DEFAULT '',
    prod_brand       VARCHAR(60)  DEFAULT '',
    prod_size        VARCHAR(25)  DEFAULT '',
    prod_color       VARCHAR(35)  DEFAULT '',
    prod_image       VARCHAR(255) DEFAULT NULL,
    publish_image    VARCHAR(255) DEFAULT NULL,
    price            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock            INT           NOT NULL DEFAULT 0,
    prod_status      TINYINT(1)    NOT NULL DEFAULT 1,  -- 1=active, 0=deleted
    publish_status   TINYINT(1)    NOT NULL DEFAULT 0,  -- 1=published
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------
-- 3. CUSTOMERS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shop_customers (
    cust_id      INT AUTO_INCREMENT PRIMARY KEY,
    cust_name    VARCHAR(110) NOT NULL,
    cust_phone   VARCHAR(25)  DEFAULT '',
    cust_email   VARCHAR(110) DEFAULT '',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------
-- 4. SALES (BILL HEADERS)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shop_sales (
    sale_id      INT AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT  DEFAULT NULL,
    sale_date    DATETIME DEFAULT CURRENT_TIMESTAMP,
    sale_total   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (customer_id)
        REFERENCES shop_customers(cust_id)
        ON DELETE SET NULL
);

-- ----------------------------------------------------------------
-- 5. SALE LINE ITEMS
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shop_sale_items (
    item_id      INT AUTO_INCREMENT PRIMARY KEY,
    sale_id      INT NOT NULL,
    product_id   INT NOT NULL,
    qty          INT           NOT NULL DEFAULT 1,
    unit_price   DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id)
        REFERENCES shop_sales(sale_id)
        ON DELETE CASCADE,
    FOREIGN KEY (product_id)
        REFERENCES shop_products(prod_id)
);

-- ----------------------------------------------------------------
-- 6. EXPENSE CATEGORIES
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exp_categories (
    cat_id      INT AUTO_INCREMENT PRIMARY KEY,
    cat_name    VARCHAR(100) NOT NULL,
    cat_icon    VARCHAR(10)  DEFAULT '💰',
    budget      DECIMAL(10,2) DEFAULT 0.00,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO exp_categories (cat_name, cat_icon, budget) VALUES
('Rent',          '🏠', 15000.00),
('Electricity',   '💡',  3000.00),
('Salaries',      '👷', 20000.00),
('Inventory',     '📦', 50000.00),
('Marketing',     '📢',  5000.00),
('Maintenance',   '🔧',  2000.00),
('Packaging',     '🛍️',  3000.00),
('Transport',     '🚚',  4000.00),
('Miscellaneous', '🗂️',  2000.00)
ON DUPLICATE KEY UPDATE cat_id = cat_id;

-- ----------------------------------------------------------------
-- 7. EXPENSE ENTRIES
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exp_entries (
    exp_id       INT AUTO_INCREMENT PRIMARY KEY,
    category_id  INT NOT NULL,
    exp_title    VARCHAR(160) NOT NULL,
    exp_amount   DECIMAL(10,2) NOT NULL,
    exp_note     TEXT DEFAULT NULL,
    exp_date     DATE NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id)
        REFERENCES exp_categories(cat_id)
        ON DELETE CASCADE
);

-- ================================================================
--  QUICK REFERENCE
-- ================================================================
-- Login URL : /clothes_retail_db/auth/login.php   (or your folder)
-- Admin creds: admin / admin123
--
-- Tables created:
--   shop_users       — staff & admin accounts
--   shop_products    — product catalogue (prod_id, prod_name …)
--   shop_customers   — customer records  (cust_id, cust_name …)
--   shop_sales       — bill headers      (sale_id, sale_total …)
--   shop_sale_items  — bill line items   (item_id, qty …)
--   exp_categories   — expense buckets   (cat_id, cat_name, budget …)
--   exp_entries      — expense records   (exp_id, exp_amount, exp_date …)
-- ================================================================
