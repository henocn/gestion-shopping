<?php
namespace src;

use src\Connectdb;
use PDO;
use Exception;

class Product {
    private $pdo;

    public function __construct() {
        $this->pdo = Connectdb::getConnection();
    }

    /**
     * Obtenir tous les produits
     */
    public function getAllProducts() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM products WHERE status = 1");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir un produit par ID
     */
    public function getProductById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Obtenir les produits les plus vendus
     */
    public function getTopSellingProducts($limit = 10, $dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT p.*, 
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) as total_sold, 
                        COUNT(CASE WHEN o.newstat = 'deliver' THEN o.id END) as total_orders, 
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue
                    FROM products p
                    INNER JOIN orders o ON p.id = o.product_id
                    WHERE o.newstat IN ('deliver', 'processing')";
            
            $params = [];
            if ($dateFrom && $dateTo) {
                $sql .= " AND ((o.newstat = 'deliver' AND o.updated_at BETWEEN ? AND ?) OR (o.newstat != 'deliver' AND o.created_at BETWEEN ? AND ?))";
                $params[] = $dateFrom;
                $params[] = $dateTo;
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }
            
            $sql .= " GROUP BY p.id ORDER BY total_sold DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir les statistiques d'un produit
     */
    public function getProductStats($productId, $dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        COUNT(o.id) as total_orders,
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) as total_quantity_sold,
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
                        AVG(CASE WHEN o.newstat = 'deliver' THEN o.total_price END) as average_order_value,
                        COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                        COUNT(CASE WHEN o.newstat = 'processing' THEN 1 END) as processing_orders,
                        COUNT(CASE WHEN o.newstat = 'canceled' THEN 1 END) as canceled_orders,
                        COUNT(CASE WHEN o.newstat = 'unreachable' THEN 1 END) as unreachable_orders
                    FROM orders o
                    WHERE o.product_id = ?";
            
            $params = [$productId];
            if ($dateFrom && $dateTo) {
                $sql .= " AND ((o.newstat = 'deliver' AND o.updated_at BETWEEN ? AND ?) OR (o.newstat != 'deliver' AND o.created_at BETWEEN ? AND ?))";
                $params[] = $dateFrom;
                $params[] = $dateTo;
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Obtenir le stock restant estimé (basé sur les commandes)
     */
    public function getEstimatedStock($productId) {
        try {
            // Calculer les quantités vendues (livrées + en cours)
            $stmt = $this->pdo->prepare("
                SELECT SUM(quantity) as sold_quantity 
                FROM orders 
                WHERE product_id = ? AND newstat IN ('deliver', 'processing')
            ");
            $stmt->execute([$productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'sold_quantity' => $result['sold_quantity'] ?? 0,
                'note' => 'Stock estimé basé sur les ventes'
            ];
        } catch (Exception $e) {
            return ['sold_quantity' => 0, 'note' => 'Erreur de calcul'];
        }
    }
}
