
-- SneakZone Database Setup
CREATE DATABASE IF NOT EXISTS my_database_user;

USE my_database_user;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'customer') NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    fullname VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    photo VARCHAR(255),
    type ENUM('sneaker', 'apparel', 'accessory') NOT NULL,
    file_link VARCHAR(255),
    upload_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seller_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order details table
CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample data with properly hashed passwords
-- All test accounts use password: 'password'
-- Admin user
INSERT INTO users (username, email, password, role, fullname) VALUES
('admin', 'admin@sneakzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator');

-- Sample seller
INSERT INTO users (username, email, password, role, fullname) VALUES
('seller1', 'seller1@sneakzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'John Seller');

-- Sample customer
INSERT INTO users (username, email, password, role, fullname) VALUES
('customer1', 'customer1@sneakzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Jane Customer');

-- Sample products
INSERT INTO products (name, description, price, photo, type, file_link, seller_id) VALUES
('Air Force 1 White', 'Classic Nike Air Force 1 in pristine white. A timeless sneaker that goes with everything.', 120.00, 'img/air-force-white.jpg', 'sneaker', '', 2),
('Jordan 1 Retro High', 'Iconic Air Jordan 1 in the classic Chicago colorway. A must-have for any sneaker collection.', 170.00, 'img/jordan-1-chicago.jpg', 'sneaker', '', 2),
('Yeezy Boost 350', 'Adidas Yeezy Boost 350 V2 in Zebra colorway. Comfort meets style in this premium sneaker.', 220.00, 'img/yeezy-350-zebra.jpg', 'sneaker', '', 2),
('Nike Dunk Low', 'Nike Dunk Low in Panda colorway. Clean black and white design for everyday wear.', 110.00, 'img/dunk-low-panda.jpg', 'sneaker', '', 2);

-- Sample cart items
INSERT INTO cart (user_id, product_id, quantity) VALUES
(3, 1, 1),
(3, 2, 1);

-- Sample wishlist items
INSERT INTO wishlist (user_id, product_id) VALUES
(3, 3),
(3, 4);
