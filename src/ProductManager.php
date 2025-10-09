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
     * Récupère un produit par son ID avec son stock et son coût.
     */
    public function find($id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.*,
                    ps.quantity,
                    ps.low_stock_threshold,
                    pcc.current_purchase_price,
                    pcc.last_updated
                FROM products p
                LEFT JOIN product_stock ps ON p.id = ps.product_id
                LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur find product: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère tous les produits avec leurs informations de stock.
     */
    public function findAll()
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    p.id,
                    p.name,
                    p.price,
                    p.image,
                    ps.quantity,
                    ps.low_stock_threshold,
                    pcc.current_purchase_price
                FROM products p
                LEFT JOIN product_stock ps ON p.id = ps.product_id
                LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
                ORDER BY p.name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur findAll products: " . $e->getMessage());
            return [];
        }
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
     * Met à jour le stock d'un produit.
     */
    public function updateStock($productId, $newQuantity)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE product_stock 
                SET quantity = ?, last_updated = NOW() 
                WHERE product_id = ?
            ");
            return $stmt->execute([$newQuantity, $productId]);
        } catch (Exception $e) {
            error_log("Erreur updateStock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le niveau de stock actuel pour un produit.
     */
    public function getStockLevel($productId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT quantity FROM product_stock WHERE product_id = ?");
            $stmt->execute([$productId]);
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Erreur getStockLevel: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Met à jour le coût d'achat d'un produit.
     */
    public function updatePurchasePrice($productId, $newPrice)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO product_current_costs (product_id, current_purchase_price, effective_date, last_updated)
                VALUES (?, ?, CURDATE(), NOW())
                ON DUPLICATE KEY UPDATE 
                    current_purchase_price = VALUES(current_purchase_price),
                    last_updated = NOW(),
                    effective_date = VALUES(effective_date)
            ");
            return $stmt->execute([$productId, $newPrice]);
        } catch (Exception $e) {
            error_log("Erreur updatePurchasePrice: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le coût d'achat d'un produit.
     */
    public function getPurchasePrice($productId)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT current_purchase_price FROM product_current_costs WHERE product_id = ?");
            $stmt->execute([$productId]);
            return (float) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Erreur getPurchasePrice: " . $e->getMessage());
            return 0.0;
        }
    }
}
