<?php

namespace Src;

use PDO;
use Exception;

/**
 * Classe centralisée pour la gestion financière.
 * Gère les dépenses, les coûts et calcule la rentabilité.
 * Basée sur les tables: depense, orders, product_current_costs, products
 */
class FinanceManager
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // --- Gestion des Dépenses ---

    /**
     * Enregistre une dépense liée à un produit (ex: frais de livraison).
     */
    public function recordProductExpense($productId, $amount, $description)
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO depense (type, product_id, cout, descrption) VALUES ('products', ?, ?, ?)"
            );
            return $stmt->execute([$productId, $amount, $description]);
        } catch (Exception $e) {
            error_log("Erreur recordProductExpense: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les dépenses pour une période donnée.
     */
    public function getProductExpenses($product_id)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    d.id,
                    d.type,
                    d.product_id,
                    d.cout,
                    d.date,
                    d.descrption,
                    p.name as product_name
                FROM depense d
                LEFT JOIN products p ON d.product_id = p.id
                WHERE d.product_id = ?
                ORDER BY d.date DESC
            ");
            $stmt->execute([$product_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getExpenses: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un résumé des dépenses par type pour une période donnée.
     */
    public function getExpensesSummary($dateFrom, $dateTo)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    type, 
                    COUNT(*) as transaction_count, 
                    SUM(cout) as total_amount
                FROM depense
                WHERE date BETWEEN ? AND ?
                GROUP BY type
                ORDER BY total_amount DESC
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getExpensesSummary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère le total des dépenses pour une période.
     */
    public function getTotalExpenses($dateFrom, $dateTo)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(cout), 0) as total
                FROM depense
                WHERE date BETWEEN ? AND ?
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            return (float) $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Erreur getTotalExpenses: " . $e->getMessage());
            return 0.0;
        }
    }

    // --- Calcul de Rentabilité ---

    /**
     * Calcule la rentabilité détaillée d'un produit pour une période.
     */
    public function calculateProductProfitability($productId, $dateFrom, $dateTo)
    {
        try {
            // 1. Infos produit
            $stmt = $this->pdo->prepare("SELECT name, price FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) return null;

            // 2. Revenus (ventes livrées uniquement)
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(SUM(total_price), 0) as total_revenue, 
                    COALESCE(SUM(quantity), 0) as units_sold
                FROM orders 
                WHERE product_id = ? 
                AND newstat = 'deliver' 
                AND updated_at BETWEEN ? AND ?
            ");
            $stmt->execute([$productId, $dateFrom, $dateTo]);
            $revenue = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Coût d'achat unitaire
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(current_purchase_price, 0) as price 
                FROM product_current_costs 
                WHERE product_id = ?
            ");
            $stmt->execute([$productId]);
            $purchasePrice = (float) $stmt->fetchColumn();

            // 4. Dépenses directes (type 'products')
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(cout), 0) as total 
                FROM depense 
                WHERE type = 'products' 
                AND product_id = ? 
                AND date BETWEEN ? AND ?
            ");
            $stmt->execute([$productId, $dateFrom, $dateTo]);
            $productExpenses = (float) $stmt->fetchColumn();

            // Calculs
            $totalRevenue = (float) ($revenue['total_revenue']);
            $unitsSold = (int) ($revenue['units_sold']);
            $totalPurchaseCost = $purchasePrice * $unitsSold;
            $totalCosts = $totalPurchaseCost + $productExpenses;
            $netProfit = $totalRevenue - $totalCosts;
            $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

            return [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'selling_price' => (float) $product['price'],
                'purchase_price' => $purchasePrice,
                'total_revenue' => $totalRevenue,
                'units_sold' => $unitsSold,
                'total_purchase_cost' => $totalPurchaseCost,
                'product_expenses' => $productExpenses,
                'total_costs' => $totalCosts,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin,
            ];
        } catch (Exception $e) {
            error_log("Erreur calculateProductProfitability: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcule la rentabilité globale pour une période.
     * Utilise la procédure stockée get_monthly_financial_summary.
     */
    public function getGlobalProfitability($month, $year)
    {
        try {
            // La procédure stockée prend un seul paramètre month_param (YYYY-MM)
            $monthParam = sprintf('%04d-%02d', (int)$year, (int)$month);
            $stmt = $this->pdo->prepare("CALL get_monthly_financial_summary(?)");
            $stmt->execute([$monthParam]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'total_revenue' => 0,
                    'product_costs' => 0,
                    'delivery_costs' => 0,
                    'net_profit' => 0,
                    'profit_margin' => 0
                ];
            }

            // Calculer la marge si absente
            if (!isset($result['profit_margin'])) {
                $totalCosts = (float)($result['product_costs'] ?? 0) + (float)($result['delivery_costs'] ?? 0);
                $net = (float)($result['gross_profit'] ?? 0) - (float)($result['salaries'] ?? 0) - (float)($result['operational_expenses'] ?? 0);
                $revenue = (float)($result['total_revenue'] ?? 0);
                $result['net_profit'] = $net;
                $result['profit_margin'] = $revenue > 0 ? ($net / $revenue) * 100 : 0;
            }
            return $result;
        } catch (Exception $e) {
            error_log("Erreur getGlobalProfitability: " . $e->getMessage());
            return [
                'total_revenue' => 0,
                'product_costs' => 0,
                'delivery_costs' => 0,
                'net_profit' => 0,
                'profit_margin' => 0
            ];
        }
    }

    /**
     * Récupère les produits les plus rentables pour une période.
     */
    public function getTopProfitableProducts($limit, $dateFrom, $dateTo)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id,
                    p.name,
                    SUM(o.quantity) as units_sold,
                    SUM(o.total_price) as total_revenue,
                    (SUM(o.total_price) - (SUM(o.quantity) * COALESCE(pcc.current_purchase_price, 0))) as estimated_profit
                FROM orders o
                JOIN products p ON o.product_id = p.id
                LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
                WHERE o.newstat = 'deliver'
                AND o.updated_at BETWEEN ? AND ?
                GROUP BY p.id, p.name
                ORDER BY estimated_profit DESC
                LIMIT ?
            ");
            $stmt->execute([$dateFrom, $dateTo, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getTopProfitableProducts: " . $e->getMessage());
            return [];
        }
    }
}
