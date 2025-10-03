-- ============================================================================
-- SYSTÈME DE GESTION FINANCIÈRE AVANCÉ
-- Date de création: 3 Octobre 2025
-- Compatible avec la base existante shopping2
-- ============================================================================

-- Table pour suivre l'historique des prix d'achat des produits
CREATE TABLE IF NOT EXISTS product_purchase_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    purchase_price DECIMAL(10, 2) NOT NULL COMMENT 'Prix d''achat unitaire',
    quantity INT NOT NULL COMMENT 'Quantité achetée',
    total_cost DECIMAL(10, 2) NOT NULL COMMENT 'Coût total de l''achat',
    supplier VARCHAR(255) DEFAULT NULL COMMENT 'Fournisseur',
    purchase_date DATETIME NOT NULL COMMENT 'Date d''achat',
    notes TEXT DEFAULT NULL COMMENT 'Notes sur l''achat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_purchase (product_id, purchase_date),
    INDEX idx_purchase_date (purchase_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des achats de produits';

-- Table pour les salaires des assistantes
CREATE TABLE IF NOT EXISTS assistant_salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ID de l''assistante',
    month VARCHAR(7) NOT NULL COMMENT 'Mois (format YYYY-MM)',
    base_salary DECIMAL(10, 2) DEFAULT 0 COMMENT 'Salaire de base',
    commission_rate DECIMAL(5, 2) DEFAULT 0 COMMENT 'Taux de commission (%)',
    commission_amount DECIMAL(10, 2) DEFAULT 0 COMMENT 'Montant des commissions',
    bonus DECIMAL(10, 2) DEFAULT 0 COMMENT 'Prime',
    deductions DECIMAL(10, 2) DEFAULT 0 COMMENT 'Déductions',
    total_salary DECIMAL(10, 2) NOT NULL COMMENT 'Salaire total',
    payment_date DATE DEFAULT NULL COMMENT 'Date de paiement',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_salary (user_id, month),
    INDEX idx_month (month),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Salaires des assistantes';

-- Table pour les dépenses opérationnelles
CREATE TABLE IF NOT EXISTS operational_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_type ENUM('publicite', 'hebergement', 'domaine', 'marketing', 'logistique', 'emballage', 'livraison', 'fournitures', 'telecommunications', 'autres') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Montant de la dépense',
    description VARCHAR(255) NOT NULL COMMENT 'Description de la dépense',
    expense_date DATE NOT NULL COMMENT 'Date de la dépense',
    category VARCHAR(100) DEFAULT NULL COMMENT 'Catégorie personnalisée',
    vendor VARCHAR(255) DEFAULT NULL COMMENT 'Fournisseur/Vendeur',
    payment_method ENUM('cash', 'bank_transfer', 'mobile_money', 'credit_card', 'other') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'paid',
    receipt_number VARCHAR(100) DEFAULT NULL COMMENT 'Numéro de reçu',
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL COMMENT 'Créé par (user_id)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expense_type (expense_type),
    INDEX idx_expense_date (expense_date),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dépenses opérationnelles';

-- Table pour les coûts de livraison par commande
CREATE TABLE IF NOT EXISTS order_delivery_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    delivery_cost DECIMAL(10, 2) NOT NULL COMMENT 'Coût de livraison',
    delivery_partner VARCHAR(255) DEFAULT NULL COMMENT 'Partenaire de livraison',
    delivery_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_delivery (order_id),
    INDEX idx_delivery_date (delivery_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Coûts de livraison par commande';

-- Table pour suivre le prix d'achat actuel des produits (vue rapide)
CREATE TABLE IF NOT EXISTS product_current_costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    current_purchase_price DECIMAL(10, 2) NOT NULL COMMENT 'Prix d''achat actuel',
    effective_date DATE NOT NULL COMMENT 'Date d''effet',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_cost (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Prix d''achat actuels des produits';

-- Table pour les objectifs mensuels
CREATE TABLE IF NOT EXISTS monthly_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month VARCHAR(7) NOT NULL COMMENT 'Mois (format YYYY-MM)',
    user_id INT DEFAULT NULL COMMENT 'ID assistante (NULL = objectif global)',
    target_revenue DECIMAL(10, 2) NOT NULL COMMENT 'Objectif de CA',
    target_orders INT DEFAULT 0 COMMENT 'Objectif nombre de commandes',
    target_profit DECIMAL(10, 2) DEFAULT 0 COMMENT 'Objectif de profit',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_target (month, user_id),
    INDEX idx_month (month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Objectifs mensuels';

-- Table pour les budgets mensuels
CREATE TABLE IF NOT EXISTS monthly_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month VARCHAR(7) NOT NULL COMMENT 'Mois (format YYYY-MM)',
    budget_category ENUM('publicite', 'salaires', 'hebergement', 'logistique', 'autres') NOT NULL,
    allocated_amount DECIMAL(10, 2) NOT NULL COMMENT 'Montant alloué',
    spent_amount DECIMAL(10, 2) DEFAULT 0 COMMENT 'Montant dépensé (calculé)',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_budget (month, budget_category),
    INDEX idx_month (month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Budgets mensuels par catégorie';

-- Table pour les campagnes publicitaires
CREATE TABLE IF NOT EXISTS advertising_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(255) NOT NULL,
    platform ENUM('facebook', 'instagram', 'google', 'tiktok', 'whatsapp', 'autres') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    budget DECIMAL(10, 2) NOT NULL COMMENT 'Budget alloué',
    spent DECIMAL(10, 2) DEFAULT 0 COMMENT 'Montant dépensé',
    impressions INT DEFAULT 0 COMMENT 'Nombre d''impressions',
    clicks INT DEFAULT 0 COMMENT 'Nombre de clics',
    conversions INT DEFAULT 0 COMMENT 'Nombre de conversions/ventes',
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_platform (platform),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Campagnes publicitaires';

-- Table pour lier les commandes aux campagnes (tracking)
CREATE TABLE IF NOT EXISTS order_campaign_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    campaign_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES advertising_campaigns(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_campaign (order_id),
    INDEX idx_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracking commandes par campagne';

-- Vue pour calculer la marge bénéficiaire par produit
CREATE OR REPLACE VIEW product_profitability AS
SELECT 
    p.id,
    p.name,
    p.price as selling_price,
    COALESCE(pcc.current_purchase_price, 0) as purchase_price,
    (p.price - COALESCE(pcc.current_purchase_price, 0)) as unit_profit,
    CASE 
        WHEN p.price > 0 THEN 
            ROUND(((p.price - COALESCE(pcc.current_purchase_price, 0)) / p.price) * 100, 2)
        ELSE 0 
    END as profit_margin_percent
FROM products p
LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id;

-- Vue pour le rapport financier mensuel global
CREATE OR REPLACE VIEW monthly_financial_report AS
SELECT 
    DATE_FORMAT(o.created_at, '%Y-%m') as month,
    -- Revenus
    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
    -- Coûts d'achat estimés (basé sur prix actuel des produits)
    SUM(CASE 
        WHEN o.newstat = 'deliver' THEN 
            o.quantity * COALESCE(pcc.current_purchase_price, 0)
        ELSE 0 
    END) as total_product_costs,
    -- Coûts de livraison
    COALESCE(SUM(CASE WHEN o.newstat = 'deliver' THEN odc.delivery_cost ELSE 0 END), 0) as total_delivery_costs,
    -- Profit brut (avant dépenses opérationnelles et salaires)
    (SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) - 
     SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity * COALESCE(pcc.current_purchase_price, 0) ELSE 0 END) -
     COALESCE(SUM(CASE WHEN o.newstat = 'deliver' THEN odc.delivery_cost ELSE 0 END), 0)) as gross_profit
FROM orders o
LEFT JOIN products p ON o.product_id = p.id
LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
LEFT JOIN order_delivery_costs odc ON o.id = odc.order_id
GROUP BY DATE_FORMAT(o.created_at, '%Y-%m');

-- Vue pour la rentabilité par assistante
CREATE OR REPLACE VIEW assistant_profitability AS
SELECT 
    u.id as assistant_id,
    u.name as assistant_name,
    DATE_FORMAT(o.created_at, '%Y-%m') as month,
    -- Ventes
    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
    -- Coûts produits
    SUM(CASE 
        WHEN o.newstat = 'deliver' THEN 
            o.quantity * COALESCE(pcc.current_purchase_price, 0)
        ELSE 0 
    END) as product_costs,
    -- Profit brut avant salaire
    (SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) - 
     SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity * COALESCE(pcc.current_purchase_price, 0) ELSE 0 END)) as gross_profit,
    -- Salaire
    COALESCE(ast.total_salary, 0) as salary_cost,
    -- Profit net (après salaire)
    (SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) - 
     SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity * COALESCE(pcc.current_purchase_price, 0) ELSE 0 END) -
     COALESCE(ast.total_salary, 0)) as net_profit
FROM users u
LEFT JOIN orders o ON u.id = o.manager_id
LEFT JOIN products p ON o.product_id = p.id
LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
LEFT JOIN assistant_salaries ast ON u.id = ast.user_id AND DATE_FORMAT(o.created_at, '%Y-%m') = ast.month
WHERE u.role = 0 AND u.is_active = 1
GROUP BY u.id, u.name, DATE_FORMAT(o.created_at, '%Y-%m');

-- Insertion de données d'exemple pour les prix d'achat actuels
-- (À ajuster selon vos produits réels)
INSERT INTO product_current_costs (product_id, current_purchase_price, effective_date)
SELECT id, ROUND(price * 0.60, 2), CURDATE()
FROM products
ON DUPLICATE KEY UPDATE current_purchase_price = VALUES(current_purchase_price);

-- ============================================================================
-- PROCÉDURES STOCKÉES UTILES
-- ============================================================================

-- Procédure pour calculer le profit d'une commande
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS calculate_order_profit(IN order_id_param INT)
BEGIN
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

-- Procédure pour obtenir le rapport financier d'un mois
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS get_monthly_financial_summary(IN month_param VARCHAR(7))
BEGIN
    SELECT 
        -- Revenus
        (SELECT COALESCE(SUM(total_revenue), 0) FROM monthly_financial_report WHERE month = month_param) as total_revenue,
        (SELECT COALESCE(SUM(total_product_costs), 0) FROM monthly_financial_report WHERE month = month_param) as product_costs,
        (SELECT COALESCE(SUM(total_delivery_costs), 0) FROM monthly_financial_report WHERE month = month_param) as delivery_costs,
        (SELECT COALESCE(SUM(gross_profit), 0) FROM monthly_financial_report WHERE month = month_param) as gross_profit,
        -- Salaires
        (SELECT COALESCE(SUM(total_salary), 0) FROM assistant_salaries WHERE month = month_param AND payment_status = 'paid') as salaries,
        -- Dépenses opérationnelles
        (SELECT COALESCE(SUM(amount), 0) FROM operational_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = month_param) as operational_expenses,
        -- Profit net final
        ((SELECT COALESCE(SUM(gross_profit), 0) FROM monthly_financial_report WHERE month = month_param) -
         (SELECT COALESCE(SUM(total_salary), 0) FROM assistant_salaries WHERE month = month_param AND payment_status = 'paid') -
         (SELECT COALESCE(SUM(amount), 0) FROM operational_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = month_param)) as net_profit;
END$$
DELIMITER ;

-- ============================================================================
-- INDEX SUPPLÉMENTAIRES POUR OPTIMISATION
-- ============================================================================

-- Index sur la table orders pour améliorer les performances
-- (Vérifier avant de créer pour éviter les doublons)
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() AND table_name = 'orders' 
               AND index_name = 'idx_orders_status_date');
SET @sqlstmt := IF(@exist > 0, 'SELECT "Index already exists"', 
                'CREATE INDEX idx_orders_status_date ON orders(newstat, created_at)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = DATABASE() AND table_name = 'orders' 
               AND index_name = 'idx_orders_manager_date');
SET @sqlstmt := IF(@exist > 0, 'SELECT "Index already exists"', 
                'CREATE INDEX idx_orders_manager_date ON orders(manager_id, created_at)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- ============================================================================
-- COMMENTAIRES ET NOTES
-- ============================================================================

/*
UTILISATION DU SYSTÈME:

1. PRIX D'ACHAT DES PRODUITS:
   - Ajouter les prix d'achat dans `product_current_costs`
   - Historique complet dans `product_purchase_history`

2. SALAIRES:
   - Enregistrer les salaires mensuels dans `assistant_salaries`
   - Possibilité d'inclure commissions et primes

3. DÉPENSES:
   - Toutes les dépenses dans `operational_expenses`
   - Catégories: publicité, hébergement, etc.

4. CAMPAGNES:
   - Suivi des campagnes pub dans `advertising_campaigns`
   - Tracking ROI par campagne

5. RAPPORTS:
   - Utilisez les vues pour rapports rapides
   - Procédures stockées pour analyses détaillées

6. COÛTS DE LIVRAISON:
   - Enregistrer dans `order_delivery_costs` par commande

REQUÊTES UTILES:
- Profit total du mois: CALL get_monthly_financial_summary('2025-09');
- Rentabilité produit: SELECT * FROM product_profitability;
- Rentabilité assistante: SELECT * FROM assistant_profitability;
*/
