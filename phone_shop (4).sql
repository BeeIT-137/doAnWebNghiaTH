-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 10, 2025 at 03:57 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `phone_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `color` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_cart_user` (`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Điện thoại', 'dien-thoai', 'fa-solid fa-mobile-screen'),
(2, 'Phụ kiện', 'phu-kien', 'fa-solid fa-headphones'),
(3, 'iPhone', 'iphone', 'fa-brands fa-apple'),
(4, 'Samsung', 'samsung', 'fa-solid fa-mobile-screen'),
(5, 'Xiaomi', 'xiaomi', 'fa-solid fa-mobile'),
(6, 'OPPO', 'oppo', 'fa-solid fa-mobile-screen'),
(7, 'Vivo', 'vivo', 'fa-solid fa-mobile-screen'),
(8, 'Realme', 'realme', 'fa-solid fa-mobile-screen'),
(9, 'Tecno', 'tecno', 'fa-solid fa-mobile-screen');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `customer_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `customer_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `customer_note` text COLLATE utf8mb4_unicode_ci,
  `total` decimal(15,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `color` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `old_price` decimal(15,2) DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `specs` text COLLATE utf8mb4_unicode_ci,
  `stock` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_price` (`price`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `price`, `old_price`, `image`, `description`, `specs`, `stock`, `created_at`) VALUES
(1, 1, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 29990000.00, 34990000.00, 'uploads/products/p_693975458cef66.02079018.jpg', 'Flagship cao cấp nhất của Apple với khung Titan.', '{\"chip\": \"A17 Pro\", \"ram\": \"8GB\"}', 80, '2025-12-10 13:24:38'),
(2, 1, 'iPhone 14 Plus', 'iphone-14-plus', 21990000.00, 24990000.00, 'uploads/products/p_6939762c9b1be2.57758182.jpg', 'Màn hình lớn, pin trâu, hiệu năng ổn định.', '{\"chip\": \"A15 Bionic\", \"ram\": \"6GB\"}', 28, '2025-12-10 13:24:38'),
(3, 1, 'Samsung Galaxy S24 Ultra', 'samsung-s24-ultra', 26990000.00, 30990000.00, 'uploads/products/p_6939795a1624e0.66113668.jpg', 'Quyền năng Galaxy AI, bút S-Pen thần thánh.', '{\"chip\": \"Snapdragon 8 Gen 3\", \"ram\": \"12GB\"}', 60, '2025-12-10 13:24:38'),
(4, 1, 'Samsung Galaxy Z Flip5', 'samsung-z-flip5', 19990000.00, 25990000.00, 'uploads/products/p_69397aa66025f0.18264726.webp', 'Điện thoại gập nhỏ gọn, màn hình phụ lớn.', '{\"chip\": \"Snapdragon 8 Gen 2\", \"ram\": \"8GB\"}', 40, '2025-12-10 13:24:38'),
(5, 1, 'Xiaomi 14', 'xiaomi-14', 19990000.00, 21990000.00, 'uploads/products/p_69397c2215ca69.00994726.webp', 'Siêu phẩm nhiếp ảnh Leica.', '{\"chip\": \"Snapdragon 8 Gen 3\", \"ram\": \"12GB\"}', 50, '2025-12-10 13:24:38'),
(6, 1, 'OPPO Reno10 5G', 'oppo-reno10', 9990000.00, 10990000.00, 'uploads/products/p_69397d4f1543e0.41675650.jpg', 'Chuyên gia chân dung, thiết kế mỏng nhẹ.', '{\"chip\": \"Dimensity 7050\", \"ram\": \"8GB\"}', 50, '2025-12-10 13:24:38'),
(7, 3, 'iPad Pro M4 11 inch', 'ipad-pro-m4-11', 28990000.00, 30990000.00, 'uploads/products/p_69397e8b2c5971.99858664.jpg', 'Sức mạnh chip M4, màn hình OLED Tandem.', '{\"chip\": \"Apple M4\", \"ram\": \"8GB\"}', 20, '2025-12-10 13:24:38'),
(8, 4, 'Samsung Galaxy Tab S9', 'samsung-tab-s9', 16990000.00, 19990000.00, 'uploads/products/p_69397facdc57c9.59301028.webp', 'Máy tính bảng chống nước, kèm bút S-Pen.', '{\"chip\": \"Snapdragon 8 Gen 2\", \"ram\": \"8GB\"}', 23, '2025-12-10 13:24:38'),
(11, 2, 'Sạc dự phòng Anker 325', 'sac-du-phong-anker', 600000.00, 1200000.00, 'uploads/products/p_69398083a22db5.81864311.webp', 'Dung lượng lớn, sạc nhanh PowerIQ.', '{\"capacity\": \"20000mAh\"}', 110, '2025-12-10 13:24:38'),
(12, 2, 'Củ sạc Apple 20W Type-C', 'cu-sac-apple-20w', 550000.00, 690000.00, 'uploads/products/p_6939847b44a034.24142218.webp', 'Sạc nhanh chính hãng Apple cho iPhone/iPad.', '{\"cong\": \"Type-C\"}', 100, '2025-12-10 13:24:38'),
(13, 2, 'Cáp sạc Baseus C to Lightning', 'cap-baseus-c-l', 150000.00, 250000.00, 'uploads/products/p_693983d98629f7.46557764.webp', 'Dây dù siêu bền, hỗ trợ sạc nhanh PD.', '{\"material\": \"Dây dù\"}', 230, '2025-12-10 13:24:38'),
(15, 2, 'Ốp lưng MagSafe Clear Case', 'op-lung-magsafe', 1290000.00, 1590000.00, 'uploads/products/p_693984c88dd545.29825079.webp', 'Ốp lưng trong suốt hỗ trợ sạc không dây.', '{\"material\": \"Nhựa cứng\"}', 60, '2025-12-10 13:24:38');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE IF NOT EXISTS `product_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_category` (`product_id`,`category_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `product_id`, `category_id`) VALUES
(18, 1, 1),
(19, 1, 3),
(22, 2, 1),
(23, 2, 3),
(24, 3, 1),
(25, 3, 4),
(26, 4, 1),
(27, 4, 4),
(28, 5, 1),
(29, 5, 5),
(30, 6, 1),
(31, 6, 6),
(6, 7, 3),
(9, 8, 4),
(39, 11, 2),
(38, 12, 2),
(37, 13, 2),
(36, 15, 2);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `color` varchar(100) NOT NULL,
  `storage` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` int NOT NULL,
  `old_price` int DEFAULT NULL,
  `stock` int DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_variant` (`product_id`,`color`,`storage`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_color_storage` (`color`,`storage`),
  KEY `idx_stock` (`stock`)
) ENGINE=InnoDB AUTO_INCREMENT=238 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `color`, `storage`, `image`, `price`, `old_price`, `stock`) VALUES
(167, 7, 'Bạc', '256GB Wifi', 'uploads/products/p_69397e8b2cdde2.52327706.webp', 28990000, 30990000, 10),
(168, 7, 'Bạc', '512GB Wifi', 'uploads/products/p_69397e8b2d3ae8.32669162.webp', 34990000, 36990000, 5),
(169, 7, 'Đen Không Gian', '256GB 5G', 'uploads/products/p_69397e8b2d8fe9.77856318.webp', 34990000, 37990000, 5),
(176, 8, 'Kem', '128GB Wifi', 'uploads/products/p_69397f97ed7352.91618391.webp', 16990000, 19990000, 10),
(177, 8, 'Kem', '256GB Wifi', 'uploads/products/p_69397f97edc403.53915084.webp', 18990000, 21990000, 8),
(178, 8, 'Đen', '128GB 5G', 'uploads/products/p_69397f97ee0c58.12674115.webp', 20990000, 23990000, 5),
(202, 1, 'Titan Tự nhiên', '256GB', 'uploads/products/p_693975458daa98.58081795.webp', 29990000, 34990000, 20),
(203, 1, 'Titan Tự nhiên', '512GB', 'uploads/products/p_693975458e0896.17402232.webp', 35990000, 40990000, 15),
(204, 1, 'Titan Tự nhiên', '1TB', 'uploads/products/p_693975458e54c2.66288636.webp', 41990000, 46990000, 10),
(205, 1, 'Titan Xanh', '256GB', 'uploads/products/p_693975458e8f72.64980383.jpg', 29990000, 34990000, 20),
(206, 1, 'Titan Xanh', '512GB', 'uploads/products/p_693975458ec619.29924802.jpg', 35990000, 40990000, 15),
(210, 2, 'Tím', '128GB', 'uploads/products/p_6939762c9ba704.59817927.webp', 21990000, 24990000, 10),
(211, 2, 'Tím', '256GB', 'uploads/products/p_6939762c9bf070.31099660.webp', 24990000, 27990000, 8),
(212, 2, 'Đen', '128GB', 'uploads/products/p_6939762c9c39d9.01588225.webp', 21990000, 24990000, 10),
(213, 3, 'Xám Titan', '256GB', 'uploads/products/p_6939795a172a34.93761091.webp', 26990000, 30990000, 20),
(214, 3, 'Xám Titan', '512GB', 'uploads/products/p_6939795a177af9.41314255.webp', 30990000, 34990000, 15),
(215, 3, 'Xám Titan', '1TB', 'uploads/products/p_6939795a17c673.43014664.webp', 36990000, 41990000, 5),
(216, 3, 'Vàng Titan', '256GB', 'uploads/products/p_6939795a1826d7.85830949.webp', 26990000, 30990000, 20),
(217, 4, 'Xanh Mint', '256GB', 'uploads/products/p_69397aa66116a7.28285277.webp', 19990000, 25990000, 15),
(218, 4, 'Xanh Mint', '512GB', 'uploads/products/p_69397aa6617af0.21319898.webp', 23990000, 29990000, 10),
(219, 4, 'Tím Lavender', '256GB', 'uploads/products/p_69397aa661c928.37217157.webp', 19990000, 25990000, 15),
(220, 5, 'Đen', '256GB', 'uploads/products/p_69397c221672a1.88720872.webp', 19990000, 21990000, 20),
(221, 5, 'Đen', '512GB', 'uploads/products/p_69397c2216cf21.56997812.webp', 22490000, 24490000, 10),
(222, 5, 'Xanh Lá', '256GB', 'uploads/products/p_69397c221713b6.50790115.webp', 19990000, 21990000, 20),
(223, 6, 'Xanh Băng Tuyết', '128GB', 'uploads/products/p_69397d4f15bb51.65610821.webp', 9990000, 10990000, 20),
(224, 6, 'Xanh Băng Tuyết', '256GB', 'uploads/products/p_69397d4f1611b8.23820561.webp', 10990000, 11990000, 15),
(225, 6, 'Xám', '256GB', 'uploads/products/p_69397d4f1659f2.99604225.webp', 10990000, 11990000, 15),
(229, 15, 'Trong suốt', 'iPhone 15 Pro', 'uploads/products/p_693984c88e5697.17670093.webp', 1290000, 1590000, 30),
(230, 15, 'Trong suốt', 'iPhone 15 Pro Max', 'uploads/products/p_693984c88eaa62.11151809.webp', 1290000, 1590000, 30),
(231, 13, 'Đen', '1 mét', 'uploads/products/p_693983d986b3e5.34983615.jpg', 150000, 250000, 100),
(232, 13, 'Đen', '2 mét', 'uploads/products/p_693983d986f180.26502519.jpg', 190000, 290000, 80),
(233, 13, 'Xanh', '1 mét', 'uploads/products/p_693983d9873a27.02672354.jpg', 150000, 250000, 50),
(234, 12, 'Trắng', '20W', 'uploads/products/p_6939847b451d16.20320792.webp', 550000, 690000, 100),
(235, 11, 'Đen', '10000mAh', 'uploads/products/p_69398083a29af9.20549929.png', 600000, 800000, 50),
(236, 11, 'Đen', '20000mAh', 'uploads/products/p_69398083a2dbc2.05516788.png', 990000, 1200000, 30),
(237, 11, 'Trắng', '20000mAh', 'uploads/products/p_69398083a30e59.36896371.jpg', 990000, 1200000, 30);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` tinyint NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `phone`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$tETo9Y2z2Q9x2pQyS2qJNO4kBzGxFJDBm1PR8W4rZk7H.YI2kVyEa', '0123456789', 1, '2025-12-02 10:46:01'),
(2, 'Caubedatai', 'panhdung1374@gmail.com', '$2y$10$PO9sht7eqxfMm2uNK0fGYOH1gqLZ8l8VU.4frZ/rDfH.DMaRuazo6', '0969445148', 1, '2025-12-02 11:29:27');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
