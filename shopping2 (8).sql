-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mer. 08 oct. 2025 à 12:03
-- Version du serveur : 8.0.43-0ubuntu0.22.04.2
-- Version de PHP : 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `shopping2`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`admin`@`localhost` PROCEDURE `calculate_order_profit` (IN `order_id_param` INT)  BEGIN
    SELECT 
        o.id,
        o.total_price as revenue,
        (o.quantity * COALESCE(pcc.current_purchase_price, 0)) as product_cost,
        COALESCE(odc.delivery_cost, 0) as delivery_cost,
        (o.total_price - 
         (o.quantity * COALESCE(pcc.current_purchase_price, 0)) - 
         COALESCE(odc.delivery_cost, 0)) as net_profit
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
    LEFT JOIN order_delivery_costs odc ON o.id = odc.order_id
    WHERE o.id = order_id_param;
END$$

CREATE DEFINER=`admin`@`localhost` PROCEDURE `get_monthly_financial_summary` (IN `month_param` VARCHAR(7))  BEGIN
    SELECT 
        
        (SELECT COALESCE(SUM(total_revenue), 0) FROM monthly_financial_report WHERE month = month_param) as total_revenue,
        (SELECT COALESCE(SUM(total_product_costs), 0) FROM monthly_financial_report WHERE month = month_param) as product_costs,
        (SELECT COALESCE(SUM(total_delivery_costs), 0) FROM monthly_financial_report WHERE month = month_param) as delivery_costs,
        (SELECT COALESCE(SUM(gross_profit), 0) FROM monthly_financial_report WHERE month = month_param) as gross_profit,
        
        (SELECT COALESCE(SUM(total_salary), 0) FROM assistant_salaries WHERE month = month_param AND payment_status = 'paid') as salaries,
        
        (SELECT COALESCE(SUM(amount), 0) FROM operational_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = month_param) as operational_expenses,
        
        ((SELECT COALESCE(SUM(gross_profit), 0) FROM monthly_financial_report WHERE month = month_param) -
         (SELECT COALESCE(SUM(total_salary), 0) FROM assistant_salaries WHERE month = month_param AND payment_status = 'paid') -
         (SELECT COALESCE(SUM(amount), 0) FROM operational_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = month_param)) as net_profit;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `depense`
--

CREATE TABLE `depense` (
  `id` int NOT NULL,
  `type` enum('products','users','campagn','others') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `product_id` int DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `cout` int NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `descrption` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `pack_id` int DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` int NOT NULL,
  `total_price` int NOT NULL,
  `client_name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_phone` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_adress` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_note` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `manager_id` int NOT NULL DEFAULT '0',
  `manager_note` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `newstat` enum('new','deliver','processing','remind','unreachable','canceled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `orders`
--

INSERT INTO `orders` (`id`, `product_id`, `pack_id`, `quantity`, `unit_price`, `total_price`, `client_name`, `client_country`, `client_phone`, `client_adress`, `client_note`, `manager_id`, `manager_note`, `created_at`, `updated_at`, `newstat`) VALUES
(1, 5, 4, 5, 4500, 62497, 'N\'GASAMA Henoc', 'TD', '98942676', 'Lomé Togo', 'Je veux deux exemplaires', 9, 'Je dois faire tous pour', '2025-09-04 09:31:59', '2025-09-10 19:38:36', 'new'),
(2, 5, 5, 3, 35000, 30000, 'Doman', 'TD', '98942676', 'Conakry', 'merci de me livrer à 17H', 9, '', '2025-09-09 20:51:38', '2025-10-03 12:39:57', 'deliver'),
(3, 10, 0, 0, 18000, 0, 'Simon Tchamie', '0', '98942676', 'togo', '', 9, NULL, '2025-09-11 21:25:52', '2025-09-11 21:25:52', 'new'),
(4, 10, 0, 0, 167000, 0, 'Simon Tchamie', '0', '98942676', 'togo', '', 9, NULL, '2025-09-11 21:27:09', '2025-09-11 21:27:09', 'new'),
(5, 10, 0, 0, 354, 0, 'Simon Tchamie', '0', '98942676', 'togo', '', 9, NULL, '2025-09-11 23:04:59', '2025-09-11 23:04:59', 'new'),
(6, 10, 0, 0, 670, 0, 'Simon Tchamie', '0', '98942676', 'togo', '', 9, NULL, '2025-09-12 07:27:54', '2025-09-12 07:27:54', 'new'),
(7, 10, 0, 1, 9000, 1450, 'Simon Tchamie', '0', '98942676', 'togo', '', 9, '', '2025-09-12 07:30:22', '2025-10-03 00:21:22', 'deliver'),
(8, 10, 0, 0, 1000, 0, 'Simon Tchamie', '0', '98942676', 'togo', '', 9, NULL, '2025-09-12 08:02:35', '2025-09-12 08:02:35', 'new'),
(9, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:34:30', '2025-09-12 11:34:30', 'new'),
(10, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:34:34', '2025-09-12 11:34:34', 'new'),
(11, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:34:38', '2025-09-12 11:34:38', 'new'),
(12, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:35:55', '2025-09-12 11:35:55', 'new'),
(13, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:43:12', '2025-09-12 11:43:12', 'new'),
(14, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:44:56', '2025-09-12 11:44:56', 'new'),
(15, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:45:56', '2025-09-12 11:45:56', 'new'),
(16, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:53:16', '2025-09-12 11:53:16', 'new'),
(17, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:54:49', '2025-09-12 11:54:49', 'new'),
(18, 10, 5, 1, 104, 15000, 'Simon Tchamie', 'GN', '92467822', 'togo', '', 0, NULL, '2025-09-12 11:57:02', '2025-09-12 11:57:02', 'new'),
(19, 11, 2, 1, 5000, 8000, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-18 13:01:02', '2025-09-19 07:44:27', 'new'),
(20, 11, 3, 2, 5000, 15000, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-18 13:01:38', '2025-09-18 13:01:38', 'new'),
(21, 11, 4, 3, 5000, 20000, 'Simon Tchamie', 'TD', '92467822', 'togo', 'ok', 0, NULL, '2025-09-18 13:11:39', '2025-09-18 13:11:39', 'new'),
(22, 9, 0, 1, 2700, 0, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-19 23:27:51', '2025-09-19 23:27:51', 'new'),
(23, 9, 0, 1, 2700, 0, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-19 23:31:39', '2025-09-19 23:31:39', 'new'),
(24, 9, 0, 1, 2700, 2700, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-19 23:48:23', '2025-09-19 23:48:23', 'new'),
(25, 9, 0, 1, 2700, 2700, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-19 23:49:21', '2025-09-19 23:49:21', 'new'),
(26, 9, 0, 1, 2700, 2700, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-20 08:00:01', '2025-09-20 08:00:01', 'new'),
(27, 9, 0, 1, 2700, 2700, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-20 08:00:44', '2025-09-20 08:00:44', 'new'),
(28, 10, 5, 9, 104, 10, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-20 08:01:09', '2025-09-20 08:01:09', 'new'),
(29, 10, 6, 13, 104, 250, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-20 08:01:19', '2025-09-20 08:01:19', 'new'),
(30, 1, 0, 1, 300000, 300000, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-20 08:38:45', '2025-09-20 08:38:45', 'new'),
(31, 1, 0, 1, 300000, 300000, 'Simon Tchamie', 'TD', '92467822', 'togo', '1000', 0, NULL, '2025-09-20 08:39:18', '2025-09-20 08:39:18', 'new'),
(32, 11, 2, 1, 5000, 8000, 'Simon Tchamie', 'TD', '92467822', 'togo', 'teste', 0, NULL, '2025-09-20 16:08:00', '2025-09-20 16:08:00', 'new'),
(33, 11, 2, 1, 5000, 8000, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-20 18:05:47', '2025-09-20 18:05:47', 'new'),
(34, 11, 2, 1, 5000, 8000, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-20 18:06:51', '2025-09-20 18:06:51', 'new'),
(35, 11, 2, 1, 5000, 8000, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-20 23:11:21', '2025-10-03 12:38:51', 'deliver'),
(36, 9, 0, 1, 2700, 2700, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-21 01:12:01', '2025-10-03 00:21:14', 'deliver'),
(37, 9, 0, 1, 2700, 2700, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-21 01:12:10', '2025-10-03 00:21:06', 'deliver'),
(38, 9, 0, 1, 2700, 2700, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-21 07:43:49', '2025-10-03 00:20:59', 'deliver'),
(39, 10, 5, 9, 104, 10, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-21 08:24:08', '2025-10-03 00:20:51', 'deliver'),
(40, 10, 0, 2, 104, 208, 'Simon Tchamie', 'TD', '92467822', 'togo', 'jghghjgjh', 0, '', '2025-09-21 08:25:44', '2025-09-28 19:04:42', 'deliver'),
(41, 10, 5, 9, 104, 10, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, NULL, '2025-09-21 08:52:45', '2025-09-28 08:52:45', 'deliver'),
(42, 10, 5, 9, 104, 10, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-21 09:12:23', '2025-09-29 16:41:49', 'deliver'),
(43, 10, 5, 10, 104, 100, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-22 13:05:01', '2025-10-03 00:20:41', 'deliver'),
(44, 9, 0, 1, 2700, 2700, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-22 13:10:16', '2025-10-03 00:20:29', 'canceled'),
(45, 11, 2, 1, 5000, 8000, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-28 19:21:22', '2025-10-03 00:19:46', 'deliver'),
(46, 11, 2, 2, 5000, 16000, 'Simon Tchamie', 'TD', '92467822', 'togo', '', 0, '', '2025-09-28 19:21:45', '2025-09-28 19:22:45', 'remind'),
(47, 1, 0, 1, 300000, 300000, 'AWIZOBA TCHAMIÈ', 'TD', '98942676', 'DONGOYO', '', 8, NULL, '2025-10-04 18:39:09', '2025-10-04 18:39:09', 'new');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price` int NOT NULL,
  `image` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `carousel1` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `carousel2` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `carousel3` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `carousel4` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `carousel5` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` int NOT NULL DEFAULT '1',
  `country` enum('TD','GN') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `manager_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `image`, `carousel1`, `carousel2`, `carousel3`, `carousel4`, `carousel5`, `description`, `status`, `country`, `manager_id`) VALUES
(1, 'PC Dell', 300000, '1756807429_download.jpg', '', '', '', '', '', '<p>Description du PC</p>', 1, 'TD', 8),
(4, 'Iphone 14', 700000, '1756809833_download.jpg', '1756809833_image1.jpeg', '1756809833_image2.jpeg', '1756809833_image3.jpeg', '', '', '<p>Une description pour le <strong>roduit</strong></p>', 1, '', 0),
(5, 'Dell vostro 15', 400000, '1756921196_dell image.jpg', '1756921196_carousel 1.jpg', '1756921196_carousel 2.jpg', '1756921196_carousel 3.jpg', '1756921196_carousel 4.jpg', '1756921196_carousel 5.jpg', '<h1><strong>Puissance et fiabilité avec Dell</strong></h1><p>Découvrez la performance au service de votre quotidien avec le <strong>PC Dell</strong>. Conçu pour allier <strong>vitesse, élégance et robustesse</strong>, ce PC répond parfaitement aux besoins des professionnels comme des particuliers.</p><p>Grâce à ses <strong>processeurs de dernière génération</strong>, une <strong>mémoire vive performante</strong> et un <strong>espace de stockage généreux</strong>, il vous garantit une fluidité exceptionnelle, même lors des tâches les plus exigeantes.</p><p>Le design moderne et épuré de Dell apporte une touche de style à votre espace de travail, tandis que la qualité de fabrication Dell assure une <strong>durabilité à toute épreuve</strong>. Que ce soit pour le télétravail, les études, le divertissement ou le gaming léger, le PC Dell est votre allié au quotidien.</p><p><strong>Pourquoi choisir Dell ?</strong></p><ul><li>Performance rapide et fiable</li><li>Conception robuste et durable</li><li>Idéal pour le travail, l’étude et le multimédia</li><li>Service et garantie Dell reconnus mondialement</li></ul><p><strong>Offrez-vous l’efficacité et la tranquillité d’esprit avec un PC Dell – la technologie au service de vos ambitions.</strong></p>', 1, '', 0),
(9, 'Ventolines', 2700, '1757333014_medoc3.jpeg', '', '', '', '', '', '<p>OK</p>', 1, 'TD', 9),
(10, 'Les pilules', 104, '1757334152_medoc1.jpeg', '1757360039_s.png', '1757360039_medoc4.jpeg', '1757360039_medoc3.jpeg', '1757360039_medoc2.jpeg', '1757360039_medoc1.jpeg', '<p><strong>J\'ai tester</strong></p>', 1, 'GN', 9),
(11, 'Metanfétamine', 5000, '1757350447_medoc1.jpeg', '1757350447_medoc2.jpeg', '1757350447_medoc4.jpeg', '1757350447_medoc1.jpeg', '1757350447_medoc3.jpeg', '', '<h1><strong>Notre produit Principal</strong></h1><p><i>Un service de qualité produit par nos soins vous procurant :</i></p><ul><li><i>finesse</i></li><li><i>confort</i></li><li><i>sureté</i></li></ul><h2><i><strong>Pourquoi choisir notre produit:</strong></i></h2><figure class=\"table\"><table><tbody><tr><td>Satisfaction</td><td>garantie</td><td>recommandation</td></tr><tr><td>100%</td><td>2ans</td><td><p>assurer</p><p>&nbsp;</p></td></tr></tbody></table></figure><p>&nbsp;</p><blockquote><p>N\'oublier pas de revenir nous voir</p></blockquote><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>', 1, 'GN', 9),
(12, 'PS5', 134, '1759173094_1757350447_medoc1.jpeg', '', '', '', '', '', '<p><span style=\"background-color: rgb(239, 239, 239);\">Apell&nbsp;</span></p><p><span style=\"background-color: rgb(239, 239, 239);\"><br></span></p><p><span style=\"background-color: rgb(239, 239, 239);\"><br></span></p><p><img src=\"data:image/jpeg;base64,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\" data-filename=\"1757350447_medoc1.jpeg\" style=\"width: 50%; float: right;\" class=\"note-float-right\"><span style=\"background-color: rgb(239, 239, 239);\"><br></span></p>', 1, 'TD', 9);

-- --------------------------------------------------------

--
-- Structure de la table `product_caracteristics`
--

CREATE TABLE `product_caracteristics` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `product_caracteristics`
--

INSERT INTO `product_caracteristics` (`id`, `product_id`, `title`, `image`, `description`) VALUES
(1, 5, 'Disque dur', '1756921196_disque dur.jpg', 'Un espace de stockage généreux pour conserver tous vos fichiers, photos et vidéos en toute sécurité. Rapide et fiable, il vous assure un accès instantané à vos données au quotidien.'),
(3, 11, 'Efficacité', '1757350447_medoc1.jpeg', 'Vous ne serai pas deçu'),
(4, 11, 'Confort', '1757350447_medoc3.jpeg', 'Garentie a 100%'),
(5, 11, 'Recomandation', '1757350447_medoc3.jpeg', 'Par le soins de nos plus grand expert'),
(6, 10, 'Efficacité', '1757403472_medoc3.jpeg', '104');

-- --------------------------------------------------------

--
-- Structure de la table `product_current_costs`
--

CREATE TABLE `product_current_costs` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `current_purchase_price` decimal(10,2) NOT NULL COMMENT 'Prix d''achat actuel',
  `effective_date` date NOT NULL COMMENT 'Date d''effet',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Prix d''achat actuels des produits';

--
-- Déchargement des données de la table `product_current_costs`
--

INSERT INTO `product_current_costs` (`id`, `product_id`, `current_purchase_price`, `effective_date`, `last_updated`) VALUES
(1, 1, '180000.00', '2025-10-03', '2025-10-03 00:44:33'),
(2, 4, '420000.00', '2025-10-03', '2025-10-03 00:44:33'),
(3, 5, '240000.00', '2025-10-03', '2025-10-03 00:44:33'),
(4, 9, '1500.00', '2025-09-05', '2025-10-03 00:51:13'),
(5, 10, '62.40', '2025-10-03', '2025-10-03 00:44:33'),
(6, 11, '3000.00', '2025-10-03', '2025-10-03 00:44:33'),
(7, 12, '80.40', '2025-10-03', '2025-10-03 00:44:33');

-- --------------------------------------------------------

--
-- Structure de la table `product_packs`
--

CREATE TABLE `product_packs` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int DEFAULT NULL,
  `price_reduction` int DEFAULT NULL,
  `price_normal` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `product_packs`
--

INSERT INTO `product_packs` (`id`, `product_id`, `titre`, `image`, `quantity`, `price_reduction`, `price_normal`) VALUES
(2, 11, 'essentiel', '1757350447_medoc3.jpeg', 1, 8000, 10000),
(3, 11, 'medium', '1757350447_medoc4.jpeg', 2, 15000, 20000),
(4, 11, 'premium', '1757350447_medoc2.jpeg', 3, 20000, 30000),
(5, 10, 'essentiels', '1757375139_medoc1.jpeg', 9, 10, 90),
(6, 10, 'medium', '1757403472_medoc3.jpeg', 13, 250, 100);

-- --------------------------------------------------------

--
-- Structure de la table `product_stock`
--

CREATE TABLE `product_stock` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '0' COMMENT 'Quantité actuelle en stock',
  `low_stock_threshold` int NOT NULL DEFAULT '10' COMMENT 'Seuil pour l''alerte de stock bas',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock actuel des produits';

-- --------------------------------------------------------

--
-- Structure de la table `product_video`
--

CREATE TABLE `product_video` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `video_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `texte` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `product_video`
--

INSERT INTO `product_video` (`id`, `product_id`, `video_url`, `texte`) VALUES
(2, 11, '1757350447_pharma.mp4', 'Nous vous tromper pas ce produit est fais pour vous!'),
(4, 10, '1757602052_WhatsApp Video 2025-08-31 at 11.54.51.mp4', 'plus que sa');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` int NOT NULL DEFAULT '0',
  `country` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '228',
  `is_active` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `email`, `name`, `password`, `role`, `country`, `is_active`) VALUES
(1, 'tchamie@gmail.com', 'AWIZOBA Tchamiè', '$2y$12$tOzGoyI3IHho9Xqiyi908OjXxFXCl9v9/Kpb.hI0dGSC07OMpq2Bu', 1, 'TD', 1),
(7, 'admin@gmail.com', 'ADMIN Admin', '$2y$12$0mE6HnV5.hnS1IHdpz2u0uR5ECQUx7iXDD/PiIes5gpDPYYnDcWPu', 1, 'GN', 1),
(8, 'ngasamah@gmail.com', 'N\'GASAMA Henoc', '$2y$12$KNaOU5l4NcZzOchNM0togeV09lW7oQSvajUADQiF9.OHaPRbd5ZIC', 1, 'TD', 1),
(9, 'assistante@gmail.com', 'ASSI Assistante', '$2y$12$tOzGoyI3IHho9Xqiyi908OjXxFXCl9v9/Kpb.hI0dGSC07OMpq2Bu', 0, 'GN', 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_status_date` (`newstat`,`created_at`),
  ADD KEY `idx_orders_manager_date` (`manager_id`,`created_at`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `product_caracteristics`
--
ALTER TABLE `product_caracteristics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `product_current_costs`
--
ALTER TABLE `product_current_costs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_cost` (`product_id`);

--
-- Index pour la table `product_packs`
--
ALTER TABLE `product_packs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `product_stock`
--
ALTER TABLE `product_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_stock` (`product_id`);

--
-- Index pour la table `product_video`
--
ALTER TABLE `product_video`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `product_caracteristics`
--
ALTER TABLE `product_caracteristics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `product_current_costs`
--
ALTER TABLE `product_current_costs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `product_packs`
--
ALTER TABLE `product_packs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `product_stock`
--
ALTER TABLE `product_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `product_video`
--
ALTER TABLE `product_video`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `product_caracteristics`
--
ALTER TABLE `product_caracteristics`
  ADD CONSTRAINT `product_caracteristics_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `product_current_costs`
--
ALTER TABLE `product_current_costs`
  ADD CONSTRAINT `product_current_costs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `product_packs`
--
ALTER TABLE `product_packs`
  ADD CONSTRAINT `product_packs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `product_stock`
--
ALTER TABLE `product_stock`
  ADD CONSTRAINT `product_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `product_video`
--
ALTER TABLE `product_video`
  ADD CONSTRAINT `product_mentions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
