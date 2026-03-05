-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 02, 2026 at 03:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `erd_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `note` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventories`
--

CREATE TABLE `inventories` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderdetails`
--

CREATE TABLE `orderdetails` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_name` varchar(255) NOT NULL,
  `customer_address` text NOT NULL,
  `note` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` varchar(20) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) NOT NULL,
  `max_users` int(11) NOT NULL,
  `used_count` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `inventories`
--
ALTER TABLE `inventories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orderdetails`
--
ALTER TABLE `orderdetails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `promotion_id` (`promotion_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventories`
--
ALTER TABLE `inventories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderdetails`
--
ALTER TABLE `orderdetails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventories`
--
ALTER TABLE `inventories`
  ADD CONSTRAINT `inventories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orderdetails`
--
ALTER TABLE `orderdetails`
  ADD CONSTRAINT `orderdetails_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orderdetails_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `create_at`, `updated_at`) VALUES
(1, 'Điện thoại', 'Smartphone các hãng', '2026-02-27 16:07:18', '2026-02-27 16:07:18'),
(2, 'Laptop', 'Máy tính xách tay', '2026-02-27 16:07:18', '2026-02-27 16:07:18'),
(3, 'Tai nghe & Loa', 'Thiết bị âm thanh', '2026-02-27 16:07:18', '2026-02-27 16:07:18'),
(4, 'Phụ kiện', 'Ốp lưng, sạc, cáp', '2026-02-27 16:07:18', '2026-02-27 16:07:18'),
(5, 'Máy tính bảng', 'Tablet, iPad', '2026-02-27 16:07:18', '2026-02-27 16:07:18');
--
-- Đang đổ dữ liệu cho bảng `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `name`, `email`, `phone`, `address`, `note`) VALUES
(1, 2, 'Nguyễn Thị Lan Anh', 'lananh@gmail.com', '0987654321', '45 Nguyễn Văn Trỗi, Pleiku', 'Khách VIP'),
(2, 3, 'Trần Minh Đức', 'minhduc@gmail.com', '0912345678', '78 Lê Lợi, Pleiku', NULL),
(3, 4, 'Lê Hoàng Vy', 'hoangvy@gmail.com', '0935123456', '12 Trần Phú, Pleiku', NULL),
(4, 5, 'Phạm Quang Huy', 'quanghuy@gmail.com', '0978123456', '99 Hùng Vương, Pleiku', 'Mua phụ kiện nhiều');
--
-- Đang đổ dữ liệu cho bảng `inventories`
--

INSERT INTO `inventories` (`id`, `product_id`, `quantity`, `last_updated`) VALUES
(1, 1, 38, '2026-02-27 16:07:19'),
(2, 2, 22, '2026-02-27 16:07:19'),
(3, 3, 55, '2026-02-27 16:07:19'),
(4, 4, 18, '2026-02-27 16:07:19'),
(5, 5, 14, '2026-02-27 16:07:19'),
(6, 6, 9, '2026-02-27 16:07:19'),
(7, 7, 72, '2026-02-27 16:07:19'),
(8, 8, 120, '2026-02-27 16:07:19'),
(9, 9, 200, '2026-02-27 16:07:19'),
(10, 10, 85, '2026-02-27 16:07:19'),
(11, 11, 25, '2026-02-27 16:07:19'),
(12, 12, 30, '2026-02-27 16:07:19');
--
-- Đang đổ dữ liệu cho bảng `orderdetails`
--

INSERT INTO `orderdetails` (`id`, `order_id`, `product_id`, `quantity`, `price_at_purchase`) VALUES
(1, 1, 1, 1, 34990000.00),
(2, 1, 7, 1, 8490000.00),
(3, 2, 2, 1, 32990000.00),
(4, 2, 5, 1, 35990000.00),
(5, 3, 7, 1, 8490000.00),
(6, 4, 8, 1, 2990000.00),
(7, 4, 10, 1, 890000.00),
(8, 5, 5, 1, 35990000.00),
(9, 6, 3, 1, 18990000.00),
(10, 6, 4, 1, 28990000.00),
(11, 7, 6, 1, 39990000.00),
(12, 8, 9, 1, 450000.00),
(13, 9, 11, 1, 22990000.00),
(14, 10, 12, 1, 11990000.00);
--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_id`, `promotion_id`, `total_price`, `status`, `created_at`, `customer_name`, `customer_address`, `note`) VALUES
(1, 2, 1, 1, 31491000.00, 'pending', '2026-02-20 03:00:00', 'Lan Anh', '45 Nguyễn Văn Trỗi', 'Giao nhanh'),
(2, 3, 2, 2, 26991750.00, 'processing', '2026-02-21 07:30:00', 'Minh Đức', '78 Lê Lợi', NULL),
(3, 4, 3, NULL, 8490000.00, 'shipped', '2026-02-22 02:15:00', 'Hoàng Vy', '12 Trần Phú', 'Gói cẩn thận'),
(4, 5, 4, 3, 2840000.00, 'delivered', '2026-02-23 11:45:00', 'Quang Huy', '99 Hùng Vương', NULL),
(5, 2, 1, NULL, 35990000.00, 'cancelled', '2026-02-24 04:20:00', 'Lan Anh', '45 Nguyễn Văn Trỗi', 'Hủy do đổi ý'),
(6, 3, 2, 1, 32390200.00, 'pending', '2026-02-25 01:00:00', 'Minh Đức', '78 Lê Lợi', NULL),
(7, 4, 3, 4, 7199250.00, 'processing', '2026-02-26 09:30:00', 'Hoàng Vy', '12 Trần Phú', NULL),
(8, 5, 4, NULL, 450000.00, 'shipped', '2026-02-27 06:00:00', 'Quang Huy', '99 Hùng Vương', 'Mua quà tặng'),
(9, 2, 1, 5, 22990000.00, 'pending', '2026-02-27 08:00:00', 'Lan Anh', '45 Nguyễn Văn Trỗi', NULL),
(10, 3, 2, NULL, 11990000.00, 'delivered', '2026-02-27 09:30:00', 'Minh Đức', '78 Lê Lợi', NULL);
--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `price`, `image`, `description`, `stock`, `created_at`, `updated_at`) VALUES
(1, 1, 'iPhone 16 Pro Max 256GB', 34990000.00, 'iphone16pm.jpg', 'Chip A18 Pro, camera 48MP', 38, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(2, 1, 'Samsung Galaxy S25 Ultra', 32990000.00, 's25ultra.jpg', 'Camera 200MP, AI mạnh', 22, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(3, 1, 'Xiaomi 15 Pro', 18990000.00, 'xiaomi15.jpg', 'Snapdragon 8 Gen 4', 55, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(4, 2, 'MacBook Air M3 13\"', 28990000.00, 'macbookair.jpg', 'Chip M3, 16GB RAM', 18, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(5, 2, 'ASUS ROG Strix G16', 35990000.00, 'rog_g16.jpg', 'RTX 4070, i9', 14, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(6, 2, 'Dell XPS 14', 39990000.00, 'dellxps14.jpg', 'Intel Ultra 7, OLED', 9, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(7, 3, 'Sony WH-1000XM5', 8490000.00, 'sony_xm5.jpg', 'Chống ồn đỉnh cao', 72, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(8, 3, 'JBL Flip 6', 2990000.00, 'jbl_flip6.jpg', 'Loa chống nước', 120, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(9, 4, 'Ốp lưng iPhone 16', 450000.00, 'oplung_iphone.jpg', 'Chống sốc', 200, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(10, 4, 'Sạc nhanh 65W GaN', 890000.00, 'sac_gan.jpg', 'Sạc PD nhanh', 85, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(11, 5, 'iPad Air M2', 16990000.00, 'ipad_air_m2.jpg', 'Chip M2, màn hình 11\"', 25, '2026-02-27 16:07:19', '2026-02-27 16:07:19'),
(12, 5, 'Samsung Galaxy Tab S9', 14990000.00, 'tabs9.jpg', 'Màn hình AMOLED 11\"', 30, '2026-02-27 16:07:19', '2026-02-27 16:07:19');
--
-- Đang đổ dữ liệu cho bảng `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `type`, `value`, `min_order_amount`, `max_uses`, `used_count`, `start_date`, `end_date`, `active`) VALUES
(1, 'NEW10', 'percentage', 10.00, 2000000.00, 300, 45, '2025-01-01', '2025-12-31', 1),
(2, 'SUMMER30', 'percentage', 30.00, 5000000.00, 80, 65, '2025-06-01', '2025-08-31', 1),
(3, 'FREESHIP', 'fixed', 50000.00, 1000000.00, NULL, 150, '2025-01-01', '2025-12-31', 1),
(4, 'BLACK20', 'percentage', 20.00, 3000000.00, 100, 100, '2025-11-20', '2025-11-30', 0),
(5, 'VIP50K', 'fixed', 50000.00, 0.00, 500, 320, '2025-02-01', '2026-02-01', 1),
(6, 'TEST5K', 'fixed', 5000.00, 0.00, 2000, 1200, '2025-01-01', '2026-01-01', 1);
--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `email`) VALUES
(1, 'admin', '$2y$10$hashadmin123', 'admin', 'admin@shop.vn'),
(2, 'lananh', '$2y$10$hashlananh', 'user', 'lananh@gmail.com'),
(3, 'minhduc', '$2y$10$hashminhduc', 'user', 'minhduc@gmail.com'),
(4, 'hoangvy', '$2y$10$hashhoangvy', 'user', 'hoangvy@gmail.com'),
(5, 'quanghuy', '$2y$10$hashquanghuy', 'user', 'quanghuy@gmail.com');