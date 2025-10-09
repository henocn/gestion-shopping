<?php

namespace Src;

use PDO;
use Exception;

/**
 * Classe centralisée pour les statistiques et rapports.
 * Gère toutes les analyses de performance (ventes, produits, assistantes).
 * Basée sur les tables: orders, products, users
 */
class AnalyticsManager
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- Statistiques Globales de Ventes ---

    /**
     * Récupère les statistiques globales de ventes pour une période.
     */
    public function getGlobalSalesStats($dateFrom = null, $dateTo = null)
    {
        try {
            $sql = "
            SELECT 
                COUNT(id) AS total_orders,
                COALESCE(SUM(CASE WHEN newstat = 'deliver' THEN quantity ELSE 0 END), 0) AS total_quantity_sold,
                COALESCE(SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END), 0) AS total_revenue,
                COALESCE(AVG(CASE WHEN newstat = 'deliver' THEN total_price END), 0) AS average_order_value,
                (SELECT COALESCE(SUM(cout), 0) FROM depense) AS total_expenses,
                COUNT(CASE WHEN newstat = 'deliver' THEN 1 END) AS delivered_orders,
                COUNT(CASE WHEN newstat = 'canceled' THEN 1 END) AS cancelled_orders,
                COUNT(CASE WHEN newstat = 'processing' THEN 1 END) AS inprogress_orders
            FROM orders
        ";

            $params = [];

            if ($dateFrom && $dateTo) {
                $sql .= " WHERE created_at BETWEEN ? AND ?";
                $params = [$dateFrom, $dateTo];
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result;
        } catch (Exception $e) {
            error_log("Erreur Stats: " . $e->getMessage());
        }
    }

    

    /**
     * Récupère les statistiques par statut de commande pour une période.
     */
    public function getOrderStatusStats($dateFrom, $dateTo)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    newstat as status, 
                    COUNT(id) as count,
                    SUM(total_price) as total_amount
                FROM orders 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY newstat
                ORDER BY count DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getOrderStatusStats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère l'évolution des ventes pour les N derniers jours.
     */
    public function getSalesEvolution($days = 30)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE(created_at) as period,
                    COALESCE(SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END), 0) as total_revenue,
                    COUNT(CASE WHEN newstat = 'deliver' THEN 1 END) as total_orders
                FROM orders 
                WHERE created_at >= CURDATE() - INTERVAL ? DAY
                GROUP BY period
                ORDER BY period ASC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getSalesEvolution: " . $e->getMessage());
            return [];
        }
    }

    // --- Statistiques sur les Produits ---

    /**
     * Récupère les produits les plus vendus sur une période.
     */
    public function getTopSellingProducts($limit, $dateFrom, $dateTo)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    p.name,
                    COALESCE(SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END), 0) as total_sold, 
                    COALESCE(SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END), 0) as total_revenue
                FROM products p
                LEFT JOIN orders o ON p.id = o.product_id AND o.created_at BETWEEN ? AND ?
                GROUP BY p.id, p.name
                HAVING total_sold > 0
                ORDER BY total_sold DESC
                LIMIT ?
            ");
            $stmt->execute([$dateFrom, $dateTo, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getTopSellingProducts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques détaillées d'un produit.
     */
    public function getProductStats($productId, $dateFrom, $dateTo)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(id) as total_orders,
                    SUM(quantity) as total_quantity,
                    SUM(CASE WHEN newstat = 'deliver' THEN quantity ELSE 0 END) as delivered_quantity,
                    SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END) as total_revenue,
                    AVG(CASE WHEN newstat = 'deliver' THEN total_price END) as average_order_value
                FROM orders
                WHERE product_id = ?
                AND created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$productId, $dateFrom, $dateTo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getProductStats: " . $e->getMessage());
            return null;
        }
    }

    // --- Statistiques sur les Assistantes ---

    /**
     * Récupère le classement des assistantes sur une période.
     */
    public function getAssistantsRanking($dateFrom, $dateTo)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    COUNT(o.id) as total_orders,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN o.newstat = 'canceled' THEN 1 END) as cancelled_orders,
                    COALESCE(SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END), 0) as total_revenue,
                    ROUND(COUNT(CASE WHEN newstat = 'deliver' THEN 1 END) * 100.0 / NULLIF(COUNT(o.id), 0), 2) as conversion_rate
                FROM users u
                LEFT JOIN orders o ON u.id = o.manager_id AND o.created_at BETWEEN ? AND ?
                WHERE u.role = 0 AND u.is_active = 1
                GROUP BY u.id, u.name, u.email
                ORDER BY total_revenue DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getAssistantsRanking: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques détaillées d'une assistante.
     */
    public function getAssistantStats($assistantId, $dateFrom, $dateTo)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(o.id) as total_orders,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN o.newstat = 'canceled' THEN 1 END) as cancelled_orders,
                    COUNT(CASE WHEN o.newstat = 'processing' THEN 1 END) as inprogress_orders,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) as total_quantity_sold,
                    ROUND(COUNT(CASE WHEN newstat = 'deliver' THEN 1 END) * 100.0 / NULLIF(COUNT(o.id), 0), 2) as conversion_rate
                FROM orders o
                WHERE o.manager_id = ?
                AND o.created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$assistantId, $dateFrom, $dateTo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getAssistantStats: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère la liste des assistantes actives.
     */
    public function getActiveAssistants()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT id, name, email
                FROM users
                WHERE role = 0 AND is_active = 1
                ORDER BY name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getActiveAssistants: " . $e->getMessage());
            return [];
        }
    }
}
