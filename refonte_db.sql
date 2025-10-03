-- =================================================================
-- ETAPE 1 : NETTOYAGE ET CORRECTION DE LA BASE DE DONNÉES (SANS MODIFIER LES TABLES PROTÉGÉES)
-- =================================================================

-- -----------------------------------------------------------------
-- NOTE: Aucune vue n'est supprimée automatiquement ici pour éviter tout effet de bord.
-- Si besoin, on créera de nouvelles vues suffixées (_v2) au lieu de remplacer les existantes.

-- -----------------------------------------------------------------
-- IMPORTANT: Les tables suivantes sont PROTÉGÉES et ne doivent pas être modifiées:
--   orders, products, product_caracteristics, product_packs, product_video, users
-- Donc aucune suppression/altération n'est effectuée ici.

-- -----------------------------------------------------------------
-- Aucun TRUNCATE automatique n'est exécuté sans validation manuelle.


-- =================================================================
-- ETAPE 2 : AJOUT DE LA GESTION DE STOCK
-- =================================================================

-- -----------------------------------------------------------------
-- 1. Création de la table `product_stock`
-- Cette table centrale suivra la quantité actuelle de chaque produit.
-- -----------------------------------------------------------------
CREATE TABLE `product_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '0' COMMENT 'Quantité actuelle en stock',
  `low_stock_threshold` int NOT NULL DEFAULT '10' COMMENT 'Seuil pour l''alerte de stock bas',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_stock` (`product_id`),
  CONSTRAINT `product_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock actuel des produits';

-- -----------------------------------------------------------------
-- 2. Création de la table `stock_movements`
-- Cette table tracera chaque entrée et sortie de stock pour un historique complet.
-- -----------------------------------------------------------------
CREATE TABLE `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `order_id` int DEFAULT NULL COMMENT 'Lié à une commande si c''est une sortie',
  `purchase_id` int DEFAULT NULL COMMENT 'Lié à un achat si c''est une entrée',
  `movement_type` enum('in','out','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL COMMENT 'Quantité du mouvement (positive pour in, négative pour out)',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Raison du mouvement (ex: vente, achat, retour, perte)',
  `movement_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_movement` (`product_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_purchase_id` (`purchase_id`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_ibfk_3` FOREIGN KEY (`purchase_id`) REFERENCES `product_purchase_history` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des mouvements de stock';

-- =================================================================
-- ETAPE 3 : AMÉLIORATIONS ET COHÉRENCE
-- =================================================================

-- -----------------------------------------------------------------
-- 1. Snapshot des coûts par commande SANS modifier la table `orders`
-- Permet de figer le coût d'achat et les coûts liés au moment de la vente
CREATE TABLE IF NOT EXISTS `order_cost_snapshots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_purchase_price_at_sale` decimal(10,2) NOT NULL COMMENT 'Prix d\'achat unitaire au moment de la vente',
  `total_purchase_cost_at_sale` decimal(12,2) NOT NULL COMMENT 'Coût d\'achat total au moment de la vente',
  `delivery_cost_at_sale` decimal(10,2) DEFAULT NULL COMMENT 'Coût de livraison associé (si applicable)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_cost_snapshot` (`order_id`),
  KEY `idx_product_snapshot` (`product_id`),
  CONSTRAINT `order_cost_snapshots_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_cost_snapshots_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Snapshot des coûts pour chaque commande au moment de la vente';

-- -----------------------------------------------------------------
-- 2. Galerie produit SANS supprimer les colonnes existantes (compatibilité ascendante)

-- -----------------------------------------------------------------
-- 3. Ajout d'une table pour les images des produits
-- C'est une meilleure pratique que d'avoir des colonnes fixes (carousel1, carousel2...).
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 si c''est l''image principale',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_image` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Galerie d''images des produits';

-- Message final : Le script est prêt. Après exécution, la base de données sera nettoyée,
-- et les nouvelles tables pour la gestion de stock seront en place (sans modifier les tables protégées).
-- La prochaine étape sera de mettre à jour le code PHP pour utiliser ces nouvelles structures.
