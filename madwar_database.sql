-- MADWAR Burger & More Database
-- Run this script in phpMyAdmin or MySQL.
-- NOTE: This creates the database and tables, but does not create a real admin password.

CREATE DATABASE IF NOT EXISTS madwar_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE madwar_db;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'staff', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS menu_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL UNIQUE,
    stock_quantity INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_item
        FOREIGN KEY (item_id) REFERENCES menu_items(item_id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    pickup_time DATETIME NOT NULL,
    payment_method ENUM('Cash on Pickup', 'GCash')
        NOT NULL DEFAULT 'Cash on Pickup',
    order_status ENUM(
        'Pending', 'Confirmed', 'Ready', 'Completed', 'Cancelled'
    ) NOT NULL DEFAULT 'Pending',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2)
        GENERATED ALWAYS AS (quantity * unit_price) STORED,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_item
        FOREIGN KEY (item_id) REFERENCES menu_items(item_id)
);

CREATE TABLE IF NOT EXISTS order_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(30) NOT NULL,
    changed_by INT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_history_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_status_history_user
        FOREIGN KEY (changed_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- Sample MADWAR menu items
INSERT INTO menu_items (item_name, category, description, price, is_available)
SELECT 'Classic Beef Burger', 'Burgers',
       'Juicy beef patty with lettuce, tomato, and house sauce', 85.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM menu_items WHERE item_name = 'Classic Beef Burger'
);

INSERT INTO menu_items (item_name, category, description, price, is_available)
SELECT 'Cheese Burger', 'Burgers', 'Classic burger with melted cheese', 99.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM menu_items WHERE item_name = 'Cheese Burger'
);

INSERT INTO menu_items (item_name, category, description, price, is_available)
SELECT 'Egg Burger', 'Burgers', 'Burger topped with egg and signature sauce', 105.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM menu_items WHERE item_name = 'Egg Burger'
);

INSERT INTO menu_items (item_name, category, description, price, is_available)
SELECT 'Burger Overload', 'Burgers', 'Loaded burger for a bigger appetite', 145.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM menu_items WHERE item_name = 'Burger Overload'
);

INSERT INTO menu_items (item_name, category, description, price, is_available)
SELECT 'Crispy Chicken', 'Meals', 'Crispy and savory chicken', 129.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM menu_items WHERE item_name = 'Crispy Chicken'
);

INSERT INTO menu_items (item_name, category, description, price, is_available)
SELECT 'French Fries', 'Sides', 'Golden crispy fries', 55.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM menu_items WHERE item_name = 'French Fries'
);

INSERT INTO menu_items (item_name, category, description, price, is_available)
SELECT 'Classic Milk Tea', 'Drinks', 'Creamy and refreshing milk tea', 65.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM menu_items WHERE item_name = 'Classic Milk Tea'
);

INSERT INTO menu_items (item_name, category, description, price, is_available)
SELECT 'Chocolate Shake', 'Drinks', 'Cold and creamy chocolate shake', 79.00, TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM menu_items WHERE item_name = 'Chocolate Shake'
);

-- Create stock records for menu items that do not have one yet.
INSERT INTO inventory (item_id, stock_quantity)
SELECT m.item_id,
       CASE
           WHEN m.item_name = 'French Fries' THEN 30
           WHEN m.item_name = 'Classic Beef Burger' THEN 25
           WHEN m.item_name = 'Cheese Burger' THEN 20
           ELSE 15
       END
FROM menu_items AS m
LEFT JOIN inventory AS i ON i.item_id = m.item_id
WHERE i.item_id IS NULL;

-- SECURITY: Create accounts in PHP using password_hash().
-- Do not store plain-text passwords in the users.password column.
-- Your PHP backend must enforce role permissions for every protected action.
