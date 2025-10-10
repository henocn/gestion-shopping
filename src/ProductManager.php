<?php

namespace Src;

use PDO;
use Exception;

/**
 * Classe pour la gestion des produits et du stock.
 * Basée strictement sur les tables: products, product_stock, product_current_costs
 */
class ProductManager
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les produits avec un stock bas.
     */
    public function getLowStockAlerts($limit = 10)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    p.name,
                    ps.quantity,
                    ps.low_stock_threshold
                FROM product_stock ps
                JOIN products p ON ps.product_id = p.id
                WHERE ps.quantity <= ps.low_stock_threshold
                ORDER BY ps.quantity ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getLowStockAlerts: " . $e->getMessage());
            return [];
        }
    }



    /**
     * Récuperation de la liste des produits vendu (newstat = deliver) avec leur taux de benefice et le benefice total
     * depuis la table orders
     */
    public function getSoldProducts()
    {
        try {
            $stmt = $this->pdo->prepare("
            SELECT 
                p.id,
                p.name,
                SUM(o.unit_price * o.quantity) AS cost_price,
                SUM(o.total_price) AS total_selling_price,
                SUM(o.quantity) AS total_sold,
                ((SUM(o.total_price) - SUM(o.unit_price * o.quantity))/SUM(o.quantity)) AS avg_profit_per_unit,
                (SUM(o.total_price) - SUM(o.unit_price * o.quantity)) AS total_profit,
                ROUND(((SUM(o.total_price) - SUM(o.unit_price * o.quantity)) / SUM(o.unit_price * o.quantity)) * 100, 2) AS rendement
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.newstat = 'deliver'
            GROUP BY p.id, p.name
            ORDER BY total_sold DESC
        ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getSoldProducts: ' . $e->getMessage());
            return [];
        }
    }
}
