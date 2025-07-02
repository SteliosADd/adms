-- Enhanced Features Database Schema
-- Product Reviews and Ratings System
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `review_text` text,
  `review_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coupon and Discount System
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `description` varchar(255),
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT 0,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coupon Usage Tracking
CREATE TABLE IF NOT EXISTS `coupon_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory Management
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `stock_quantity` int(11) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS `low_stock_threshold` int(11) NOT NULL DEFAULT 10,
ADD COLUMN IF NOT EXISTS `brand` varchar(100),
ADD COLUMN IF NOT EXISTS `sku` varchar(100) UNIQUE,
ADD COLUMN IF NOT EXISTS `weight` decimal(8,2),
ADD COLUMN IF NOT EXISTS `dimensions` varchar(100),
ADD COLUMN IF NOT EXISTS `featured` tinyint(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `views` int(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `sales_count` int(11) DEFAULT 0;

-- Order Tracking Enhancement
ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `tracking_number` varchar(100),
ADD COLUMN IF NOT EXISTS `shipping_method` varchar(100),
ADD COLUMN IF NOT EXISTS `estimated_delivery` date,
ADD COLUMN IF NOT EXISTS `shipped_date` datetime,
ADD COLUMN IF NOT EXISTS `delivered_date` datetime,
ADD COLUMN IF NOT EXISTS `coupon_code` varchar(50),
ADD COLUMN IF NOT EXISTS `discount_amount` decimal(10,2) DEFAULT 0;

-- Product Categories Enhancement
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255),
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product Attributes for Advanced Filtering
CREATE TABLE IF NOT EXISTS `product_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications System
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','product','system','promotion') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample categories
INSERT IGNORE INTO `categories` (`name`, `description`) VALUES
('Sneakers', 'Athletic and casual sneakers'),
('Apparel', 'Clothing and accessories'),
('Accessories', 'Bags, hats, and other accessories');

-- Insert sample coupons
INSERT IGNORE INTO `coupons` (`code`, `description`, `discount_type`, `discount_value`, `minimum_amount`, `usage_limit`, `start_date`, `end_date`) VALUES
('WELCOME10', 'Welcome discount for new customers', 'percentage', 10.00, 50.00, 100, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('SAVE20', 'Save $20 on orders over $100', 'fixed', 20.00, 100.00, 50, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY)),
('SNEAKER15', '15% off all sneakers', 'percentage', 15.00, 0.00, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY));

-- Update existing products with sample inventory data
UPDATE `products` SET 
  `stock_quantity` = FLOOR(RAND() * 100) + 10,
  `low_stock_threshold` = 10,
  `brand` = CASE 
    WHEN `name` LIKE '%Nike%' THEN 'Nike'
    WHEN `name` LIKE '%Adidas%' THEN 'Adidas'
    WHEN `name` LIKE '%Jordan%' THEN 'Jordan'
    ELSE 'SneakZone'
  END,
  `sku` = CONCAT('SKU', LPAD(`id`, 6, '0')),
  `weight` = ROUND(RAND() * 2 + 0.5, 2),
  `featured` = CASE WHEN RAND() > 0.7 THEN 1 ELSE 0 END,
  `views` = FLOOR(RAND() * 1000),
  `sales_count` = FLOOR(RAND() * 50)
WHERE `stock_quantity` IS NULL OR `stock_quantity` = 0;