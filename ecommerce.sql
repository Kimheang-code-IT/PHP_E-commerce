-- ecommerce.sql
-- ---------------------------------------------------------------------
-- Database: `ecommerce`
-- ---------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `ecommerce`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `ecommerce`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

DELIMITER $$
-- Stored Procedures
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_stock` (
  IN `p_prod_id` INT,
  IN `p_qty` INT
)
BEGIN
  INSERT INTO `stock_additions` (product_id,qty)
    VALUES (p_prod_id,p_qty);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_user` (
  IN `p_role` TINYINT,
  IN `p_user` VARCHAR(50),
  IN `p_email` VARCHAR(100),
  IN `p_pass` VARCHAR(255),
  OUT `p_newid` CHAR(12)
)
BEGIN
  INSERT INTO `users` (role_id,username,email,password)
    VALUES (p_role,p_user,p_email,p_pass);
  SET p_newid = LAST_INSERT_ID();
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_create_order` (
  IN `p_user_id` CHAR(12),
  IN `p_status` ENUM('pending','paid','shipped','completed','cancelled'),
  IN `p_items` JSON,
  OUT `p_order_id` INT
)
BEGIN
  DECLARE idx INT DEFAULT 0;
  DECLARE len INT;
  DECLARE prod INT;
  DECLARE qty INT;
  DECLARE price DECIMAL(10,2);

  SET len = JSON_LENGTH(p_items);

  INSERT INTO `orders` (user_id,status,total_usd)
    VALUES (p_user_id,p_status,0);
  SET p_order_id = LAST_INSERT_ID();

  WHILE idx < len DO
    SET prod  = JSON_EXTRACT(p_items, CONCAT('$[',idx,'].product_id'));
    SET qty   = JSON_EXTRACT(p_items, CONCAT('$[',idx,'].quantity'));
    SET price = JSON_EXTRACT(p_items, CONCAT('$[',idx,'].unit_price'));

    INSERT INTO `order_items`
      (order_id,product_id,quantity,unit_price)
      VALUES(p_order_id,prod,qty,price);

    SET idx = idx + 1;
  END WHILE;

  UPDATE `orders`
     SET total_usd = (
       SELECT COALESCE(SUM(quantity*unit_price),0)
         FROM `order_items`
        WHERE order_id = p_order_id
     )
   WHERE id = p_order_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_products_delete_by_barcode` (
  IN `p_barcode` CHAR(12)
)
BEGIN
  DELETE FROM `products` WHERE barcode = p_barcode;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_taxes_delete` (
  IN `p_id` INT
)
BEGIN
  DELETE FROM `taxes` WHERE `id` = p_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_taxes_get` (
  IN `p_id` INT
)
BEGIN
  SELECT `id`,`name`,`rate`,`is_active`
    FROM `taxes`
   WHERE `id` = p_id
   LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_taxes_insert` (
  IN `p_name` VARCHAR(100),
  IN `p_rate` DECIMAL(5,2),
  IN `p_is_active` TINYINT
)
BEGIN
  INSERT INTO `taxes` (`name`,`rate`,`is_active`)
    VALUES (p_name,p_rate,p_is_active);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_taxes_update` (
  IN `p_id` INT,
  IN `p_name` VARCHAR(100),
  IN `p_rate` DECIMAL(5,2),
  IN `p_is_active` TINYINT
)
BEGIN
  UPDATE `taxes`
     SET `name`      = p_name,
         `rate`      = p_rate,
         `is_active` = p_is_active
   WHERE `id` = p_id;
END$$
DELIMITER ;

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `products_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sample categories
INSERT INTO `categories` (`id`,`name`,`description`,`products_count`,`created_at`,`updated_at`) VALUES
(1,'Electronics','Gadgets & devices',1,'2025-07-13 11:10:50','2025-07-13 11:10:50'),
(2,'Books','Printed & ebooks',1,'2025-07-13 11:10:50','2025-07-13 11:10:50'),
(3,'Clothing','Apparel',1,'2025-07-13 11:10:50','2025-07-13 11:10:50'),
(4,'Home','Home & kitchen',1,'2025-07-13 11:10:50','2025-07-13 11:10:50'),
(5,'Sports','Sporting goods',1,'2025-07-13 11:10:50','2025-07-13 11:10:50'),
(6,'Toys','Kids toys',1,'2025-07-13 11:10:50','2025-07-13 11:10:50'),
(7,'Beauty','Personal care',1,'2025-07-13 11:10:50','2025-07-13 11:10:50'),
(8,'Automotive','Car parts',2,'2025-07-13 11:10:50','2025-07-13 13:23:15'),
(9,'Grocery','Food & drink',0,'2025-07-13 11:10:50','2025-07-13 13:23:43'),
(10,'Garden','Outdoor & garden',0,'2025-07-13 11:10:50','2025-07-13 11:12:53');

-- --------------------------------------------------------
-- Table structure for table `roles`
-- --------------------------------------------------------
CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `roles` (`id`,`name`) VALUES
(1,'admin'),(2,'saler'),(3,'customer'),(4,'manager'),
(5,'support'),(6,'supplier'),(7,'viewer'),(8,'editor'),
(9,'analyst'),(10,'guest');

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` char(12) NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(6) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_roles` (`role_id`),
  CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sample users
INSERT INTO `users` (`id`,`role_id`,`username`,`email`,`password`,`created_at`,`updated_at`,`reset_token`,`reset_expires`) VALUES
('AD0000000001',1,'admin','admin@site.com','$2y$10$...',NOW(),NOW(),NULL,NULL),
('SAL0000000001',2,'saler','saler@example.com','$2y$10$...',NOW(),NOW(),'836259','2025-07-13 13:03:23'),
('US0000000001',3,'customer','customer@example.com','$2y$10$...',NOW(),NOW(),'836259','2025-07-13 13:03:23');

DELIMITER $$
-- trigger to generate user ID
CREATE TRIGGER `trg_users_before_insert`
BEFORE INSERT ON `users` FOR EACH ROW
BEGIN
  DECLARE _prefix CHAR(3);
  DECLARE _padLen INT;
  DECLARE _nextSeq INT;
  IF NEW.role_id = 1 THEN
    SET _prefix = 'AD';
  ELSEIF NEW.role_id = 2 THEN
    SET _prefix = 'SAL';
  ELSE
    SET _prefix = 'US';
  END IF;
  SET _padLen = 12-CHAR_LENGTH(_prefix);
  SELECT COALESCE(MAX(CAST(SUBSTRING(id,CHAR_LENGTH(_prefix)+1) AS UNSIGNED)),0)+1
    INTO _nextSeq
    FROM `users`
    WHERE id LIKE CONCAT(_prefix,'%') COLLATE utf8mb4_unicode_ci;
  SET NEW.id = CONCAT(_prefix,LPAD(_nextSeq,_padLen,'0'));
END$$
DELIMITER ;

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL,
  `barcode` char(12) NOT NULL,
  `image` varchar(255) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `price_usd` decimal(10,2) NOT NULL DEFAULT 0.00,
  `change_rate` decimal(10,2) NOT NULL DEFAULT 4100.00,
  `price_real` decimal(14,2) GENERATED ALWAYS AS (`price_usd`*`change_rate`) STORED,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `idx_products_category` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sample products
INSERT INTO `products` (`category_id`,`barcode`,`image`,`name`,`description`,`price_usd`,`stock_quantity`) VALUES
(1,'001071300001','elec.jpg','Smartphone','Flagship phone',699.99,10),
(2,'002071300001','book.jpg','Novel','Bestseller',14.99,20),
(3,'003071300001','jeans.jpg','Jeans','Denim jeans',49.99,30),
(4,'004071300001','blend.jpg','Blender','Kitchen blender',89.99,40),
(5,'005071300001','racket.jpg','Tennis Racket','Pro racket',129.99,50),
(6,'006071300001','figure.jpg','Action Figure','Collectible',24.99,15),
(7,'007071300001','lipstick.jpg','Lipstick','Matte finish',19.99,25),
(8,'008071300001','wax.jpg','Car Wax','Protective wax',15.99,35);

-- triggers for products
DELIMITER $$
CREATE TRIGGER `trg_products_generate_composite`
BEFORE INSERT ON `products` FOR EACH ROW
BEGIN
  DECLARE cat_code CHAR(3);
  DECLARE date_code CHAR(4);
  DECLARE next_seq INT;
  SET cat_code = LPAD(NEW.category_id,3,'0');
  SET date_code = DATE_FORMAT(NOW(),'%m%d');
  SELECT COALESCE(MAX(CAST(RIGHT(barcode,5) AS UNSIGNED)),0)+1
    INTO next_seq
    FROM `products`
    WHERE LEFT(barcode,7)=CONCAT(cat_code,date_code);
  SET NEW.barcode = CONCAT(cat_code,date_code,LPAD(next_seq,5,'0'));
END$$

CREATE TRIGGER `trg_products_after_insert`
AFTER INSERT ON `products` FOR EACH ROW
BEGIN
  UPDATE `categories`
    SET products_count = products_count + 1
    WHERE id = NEW.category_id;
END$$

CREATE TRIGGER `trg_products_after_delete`
AFTER DELETE ON `products` FOR EACH ROW
BEGIN
  UPDATE `categories`
    SET products_count = GREATEST(products_count-1,0)
    WHERE id = OLD.category_id;
END$$
DELIMITER ;

-- --------------------------------------------------------
-- Table structure for table `stock_additions`
-- --------------------------------------------------------
CREATE TABLE `stock_additions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL,
  `qty` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_stock_additions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sample stock additions
INSERT INTO `stock_additions` (`product_id`,`qty`) VALUES
(1,10),(2,20),(3,30),(4,40),(5,50),(6,15),(7,25),(8,35);

-- trigger to update stock_quantity
DELIMITER $$
CREATE TRIGGER `trg_stock_after_insert`
AFTER INSERT ON `stock_additions` FOR EACH ROW
BEGIN
  UPDATE `products`
    SET stock_quantity = stock_quantity + NEW.qty
    WHERE id = NEW.product_id;
END$$
DELIMITER ;

-- --------------------------------------------------------
-- Table structure for table `orders`
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` char(12) NOT NULL,
  `status` enum('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `total_usd` decimal(12,2) NOT NULL DEFAULT 0.00,
  `change_rate` decimal(10,2) NOT NULL DEFAULT 4100.00,
  `price_real` decimal(14,2) GENERATED ALWAYS AS (`total_usd`*`change_rate`) STORED,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `order_items`
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NOT NULL,
  `amount_usd` decimal(12,2) NOT NULL,
  `change_rate` decimal(10,2) NOT NULL DEFAULT 4100.00,
  `amount_real` decimal(14,2) GENERATED ALWAYS AS (`amount_usd`*`change_rate`) STORED,
  `method` enum('cash','card','online') NOT NULL,
  `paid_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Views
-- --------------------------------------------------------
DROP VIEW IF EXISTS `vw_monthly_sales`;
CREATE VIEW `vw_monthly_sales` AS
 SELECT YEAR(o.created_at) AS sales_year,
        MONTH(o.created_at) AS sales_month,
        SUM(o.total_usd)   AS total_sales_usd,
        SUM(o.price_real)  AS total_sales_real
   FROM orders o
  GROUP BY YEAR(o.created_at), MONTH(o.created_at);

DROP VIEW IF EXISTS `vw_product_overview`;
CREATE VIEW `vw_product_overview` AS
 SELECT p.id          AS ID,
        p.barcode     AS Barcode,
        c.name        AS Category,
        p.image       AS Image,
        p.name        AS Name,
        p.description AS Description,
        p.price_usd   AS `Price $`,
        ROUND(p.price_usd*p.change_rate,0) AS `Price ៛`,
        p.stock_quantity    AS `Stock In`,
        COALESCE(sa.total_add,0) AS `Stock Add`,
        p.stock_quantity + COALESCE(sa.total_add,0) AS `Total Stock`,
        CASE WHEN p.stock_quantity+COALESCE(sa.total_add,0)>0 THEN 'Yes' ELSE 'No' END AS Avail
   FROM products p
   JOIN categories c ON p.category_id=c.id
   LEFT JOIN (
     SELECT product_id, SUM(qty) AS total_add
       FROM stock_additions
      GROUP BY product_id
   ) sa ON sa.product_id=p.id;

-- ---------------------------------------------------------------------
-- ecommerce – seed_data.sql
-- Insert 10 dummy rows into every table
-- ---------------------------------------------------------------------

USE `ecommerce`;

START TRANSACTION;

—── 1) Categories ─────────────────────────────────────────────────────
INSERT INTO `categories` (name, description) VALUES
 ('Cat A','Desc A'),('Cat B','Desc B'),('Cat C','Desc C'),
 ('Cat D','Desc D'),('Cat E','Desc E'),('Cat F','Desc F'),
 ('Cat G','Desc G'),('Cat H','Desc H'),('Cat I','Desc I'),
 ('Cat J','Desc J');

—── 2) Roles (you already have 10, but here’s a quick reset) ─────────
INSERT INTO `roles` (name) VALUES
 ('admin'),('saler'),('customer'),('manager'),
 ('support'),('supplier'),('viewer'),('editor'),
 ('analyst'),('guest');

—── 3) Users ─────────────────────────────────────────────────────────
-- we’ll create 10 customers; password is just the literal 'passX' hashed
INSERT INTO `users` (role_id,username,email,password)
VALUES
 (3,'user1','user1@example.com',PASSWORD('pass1')),
 (3,'user2','user2@example.com',PASSWORD('pass2')),
 (3,'user3','user3@example.com',PASSWORD('pass3')),
 (3,'user4','user4@example.com',PASSWORD('pass4')),
 (3,'user5','user5@example.com',PASSWORD('pass5')),
 (3,'user6','user6@example.com',PASSWORD('pass6')),
 (3,'user7','user7@example.com',PASSWORD('pass7')),
 (3,'user8','user8@example.com',PASSWORD('pass8')),
 (3,'user9','user9@example.com',PASSWORD('pass9')),
 (3,'user10','user10@example.com',PASSWORD('pass10'));

—── 4) Products ──────────────────────────────────────────────────────
INSERT INTO `products` (category_id, image, name, description, price_usd, stock_quantity)
VALUES
 (1,'prod1.jpg','Product 1','Desc 1',10.00,100),
 (2,'prod2.jpg','Product 2','Desc 2',20.00, 90),
 (3,'prod3.jpg','Product 3','Desc 3',30.00, 80),
 (4,'prod4.jpg','Product 4','Desc 4',40.00, 70),
 (5,'prod5.jpg','Product 5','Desc 5',50.00, 60),
 (6,'prod6.jpg','Product 6','Desc 6',60.00, 50),
 (7,'prod7.jpg','Product 7','Desc 7',70.00, 40),
 (8,'prod8.jpg','Product 8','Desc 8',80.00, 30),
 (9,'prod9.jpg','Product 9','Desc 9',90.00, 20),
 (10,'prod10.jpg','Product 10','Desc 10',100.00,10);

—── 5) Stock Additions ───────────────────────────────────────────────
-- give each product + 10 units
INSERT INTO `stock_additions` (product_id, qty)
VALUES
 (1,10),(2,10),(3,10),(4,10),(5,10),
 (6,10),(7,10),(8,10),(9,10),(10,10);

—── 6) Orders ────────────────────────────────────────────────────────
-- 10 orders by user1–user10, status alternating
INSERT INTO `orders` (user_id, status, total_usd)
VALUES
 ('US0000000001','pending',100.00),
 ('US0000000002','paid',200.00),
 ('US0000000003','shipped',300.00),
 ('US0000000004','completed',400.00),
 ('US0000000005','cancelled',500.00),
 ('US0000000006','pending',600.00),
 ('US0000000007','paid',700.00),
 ('US0000000008','shipped',800.00),
 ('US0000000009','completed',900.00),
 ('US0000000010','cancelled',1000.00);

—── 7) Order Items ───────────────────────────────────────────────────
-- each order gets exactly one line item: order N → product N
INSERT INTO `order_items` (order_id, product_id, quantity, unit_price)
VALUES
 (1,1,1,10.00),(2,2,2,20.00),(3,3,3,30.00),
 (4,4,4,40.00),(5,5,5,50.00),(6,6,6,60.00),
 (7,7,7,70.00),(8,8,8,80.00),(9,9,9,90.00),
 (10,10,10,100.00);

—── 8) Payments ─────────────────────────────────────────────────────
-- match the above orders, paid for 'paid' and 'completed' only
INSERT INTO `payments` (order_id, amount_usd, method)
VALUES
 (2,200.00,'card'),
 (3,300.00,'online'),
 (4,400.00,'cash'),
 (7,700.00,'card'),
 (9,900.00,'online');

—── 9) Taxes ─────────────────────────────────────────────────────────
INSERT INTO `taxes` (name, rate, is_active)
VALUES
 ('VAT',10.00,1),('Service',5.00,1),('Import',15.00,1),
 ('Luxury',20.00,1),('Eco',3.00,1),('City',2.50,1),
 ('State',8.25,1),('Federal',1.50,1),('Special',12.00,1),('Other',0.50,1);

COMMIT;

