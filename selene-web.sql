-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 16, 2026 at 09:38 AM
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
-- Database: `selene-web`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `address_id` int(11) NOT NULL COMMENT 'Unique address identifier',
  `customer_id` int(11) NOT NULL COMMENT 'Related customer identifier',
  `city` varchar(100) NOT NULL COMMENT 'City name',
  `street` varchar(255) NOT NULL COMMENT 'Street address',
  `postal_code` varchar(20) NOT NULL COMMENT 'Postal code'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Admin unique ID',
  `username` varchar(50) CHARACTER SET armscii8 COLLATE armscii8_general_ci NOT NULL COMMENT 'Admin login name',
  `password` varchar(255) CHARACTER SET armscii8 COLLATE armscii8_general_ci NOT NULL COMMENT 'Hashed password',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Account creation time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin_id`, `username`, `password`, `created_at`) VALUES
(2, 'Amjad_Alobaid', 'ao123', '2026-04-16 17:28:10'),
(5, 'Layan_Alzahrani', 'lz123', '2026-04-16 17:28:10'),
(3, 'Reem_Altheeb', 'rt123', '2026-04-16 17:28:10'),
(4, 'Sarah_Alsubaie', 'ss123', '2026-04-16 17:28:10'),
(1, 'Shahad_Algharyafe', 'sg123', '2026-04-16 17:21:06'),
(7, 'Shahad_Alqarni', 'sq123', '2026-04-16 17:28:10'),
(6, 'Wesam_Sanbo', 'ws123', '2026-04-16 17:28:10');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL COMMENT 'Unique cart identifier',
  `customer_id` int(11) NOT NULL COMMENT 'Customer who owns the cart',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Cart creation date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL COMMENT 'Unique cart item identifier',
  `cart_id` int(11) NOT NULL COMMENT 'Related shopping cart',
  `product_id` int(11) NOT NULL COMMENT 'Added product identifier',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Product quantity in cart'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL COMMENT 'Unique message ID',
  `name` varchar(100) NOT NULL COMMENT 'Sender full name',
  `email` varchar(100) NOT NULL COMMENT 'Sender email address',
  `customer_id` int(11) NOT NULL,
  `message` text NOT NULL COMMENT 'Message content from user',
  `status` varchar(20) NOT NULL DEFAULT 'New' COMMENT 'Message status (New / Read / Replied)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Time message was sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL COMMENT 'Unique customer identifier',
  `name` varchar(100) NOT NULL COMMENT 'Customer full name',
  `email` varchar(100) NOT NULL COMMENT 'Customer email address',
  `password` varchar(255) NOT NULL COMMENT 'Encrypted customer password',
  `phone` varchar(20) NOT NULL COMMENT 'Customer phone number',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Customer registration date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL COMMENT 'Unique feedback identifier',
  `customer_id` int(11) NOT NULL COMMENT 'Customer who submitted feedback',
  `product_id` int(11) NOT NULL COMMENT 'Reviewed product identifier',
  `rating` int(11) NOT NULL COMMENT 'Product rating value',
  `comment` text DEFAULT NULL COMMENT 'Customer review comment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Feedback creation date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL COMMENT 'Unique order identifier',
  `customer_id` int(11) NOT NULL COMMENT 'Customer who placed the order',
  `address_id` int(11) NOT NULL COMMENT 'Delivery address reference',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Date of order placement',
  `total_price` decimal(10,0) NOT NULL COMMENT 'Total order amount',
  `status` varchar(50) NOT NULL DEFAULT 'Pending' COMMENT 'Current order status',
  `city` varchar(100) NOT NULL COMMENT 'Delivery city',
  `street` varchar(255) NOT NULL COMMENT 'Delivery street address',
  `postal_code` varchar(20) NOT NULL COMMENT 'Delivery postal code'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL COMMENT 'Unique order item identifier',
  `order_id` int(11) NOT NULL COMMENT 'Related order identifier',
  `product_id` int(11) NOT NULL COMMENT 'Ordered product identifier',
  `quantity` int(11) NOT NULL COMMENT 'Ordered quantity',
  `price` decimal(10,0) NOT NULL COMMENT 'Product price at purchase time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL COMMENT 'Unique payment identifier',
  `order_id` int(11) NOT NULL COMMENT 'Related order identifier',
  `payment_method` varchar(50) NOT NULL COMMENT 'Payment method used',
  `payment_status` varchar(50) NOT NULL COMMENT 'Payment status',
  `amount` decimal(10,2) NOT NULL COMMENT 'Paid amount',
  `tarnsaction_id` varchar(255) DEFAULT NULL COMMENT 'Payment transaction identifier',
  `currency` varchar(10) NOT NULL DEFAULT 'SAR' COMMENT 'Payment currency',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Payment date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL COMMENT 'Product unique ID',
  `name` varchar(100) NOT NULL COMMENT 'Product name',
  `description` text DEFAULT NULL COMMENT 'Product details',
  `price` decimal(10,2) NOT NULL COMMENT 'Product price',
  `stock` int(11) NOT NULL COMMENT 'Available quantity',
  `image` varchar(255) DEFAULT NULL COMMENT 'Product image',
  `color` varchar(30) DEFAULT NULL COMMENT 'Product color',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Added time',
  `category` enum('Ring','Necklace','Earrings','Bracelet') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `name`, `description`, `price`, `stock`, `image`, `color`, `created_at`, `category`) VALUES
(1, 'MISSOMA', 'Pearl Baya Detachable Drop Earrings in 18kt Recycled Gold.', 1150.00, 100, 'Product1.webp', 'Gold', '2026-04-16 18:15:45', 'Earrings'),
(2, 'Valentino Garavani', 'VLOGO Swarovski® Bracelet in Metal.', 2250.00, 112, 'Product2.webp', 'Gold', '2026-04-16 18:15:45', 'Necklace'),
(3, 'Valentino Garavani', 'Valentino Garavani VLogo Signature Earrings in 18kt Gold-tone Metal & Swarovski®.', 1100.00, 500, 'Product3.webp', 'Gold', '2026-04-16 18:15:45', 'Earrings'),
(4, 'Marli', 'Cleo Diamond & Quartz Slim Ring in 18kt White Gold.', 1600.00, 120, 'Product4.webp', 'White Gold', '2026-04-16 18:15:45', 'Ring'),
(5, 'FERGUS JAMES', '13ct Pear Diamond Tennis Bracelet in 18kt White Gold .', 1780.00, 110, 'Product5.webp', 'White Gold', '2026-04-16 18:18:39', 'Bracelet'),
(6, 'Missoma', 'Lucy Williams T-bar Knot Necklace in 18kt Gold Vermeil & Sterling Silver.', 850.00, 200, 'Product6.webp', 'Gold / Silver', '2026-04-16 18:18:40', 'Necklace'),
(7, 'Marli', 'Cleo Diamond Slim Slip-on Bracelet in 18kt White Gold.', 2500.00, 130, 'Product7.webp', 'White Gold', '2026-04-16 18:18:40', 'Bracelet'),
(8, 'LUV AJ', 'Sierra Split Ring in 14kt Gold-plated Brass.', 370.00, 120, 'Product8.webp', 'Gold', '2026-04-16 18:18:40', 'Ring'),
(9, 'SHASHI', 'Bianca Pearl Drop Earrings in 14kt Gold Vermeil.', 445.00, 12, 'Product9.webp', 'Gold', '2026-04-16 18:18:40', 'Earrings'),
(10, 'By Alona', 'Halo Mother-of-Pearl Necklace in 18kt Gold-plated Brass.', 2560.00, 20, 'Product10.webp', 'Gold', '2026-04-16 18:18:40', 'Necklace'),
(11, 'Yvonne Leon', 'Wave Hoop Diamond Earrings in 9kt Gold.', 2250.00, 12, 'Product11.webp', 'Gold', '2026-04-16 18:18:40', 'Earrings');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `address_id` (`address_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique address identifier';

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique cart identifier';

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique cart item identifier';

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique message ID';

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique customer identifier';

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique feedback identifier';

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique order identifier';

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique order item identifier';

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique payment identifier';

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Product unique ID', AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`);

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Fix: allow guest contact messages (customer_id can be NULL)
ALTER TABLE `contact_messages`
  MODIFY `customer_id` int(11) DEFAULT NULL COMMENT 'Logged-in customer ID (NULL if guest)';
