<?php
namespace src;

use src\Connectdb;
use PDO;
use Exception;

class Stat {
    private $pdo;

    public function __construct() {
        $this->pdo = Connectdb::getConnection();
    }

    /**
     * Statistiques des ventes par assistante dans une période donnée
     */
    public function getHelperSalesStats($helperId = null, $period = 'day', $dateFrom = null, $dateTo = null) {
        try {
            // Définir les dates selon la période
            if (!$dateFrom || !$dateTo) {
                switch ($period) {
                    case 'day':
                        $dateFrom = date('Y-m-d 00:00:00');
                        $dateTo = date('Y-m-d 23:59:59');
                        break;
                    case 'week':
                        $dateFrom = date('Y-m-d 00:00:00', strtotime('monday this week'));
                        $dateTo = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                        break;
                    case 'month':
                        $dateFrom = date('Y-m-01 00:00:00');
                        $dateTo = date('Y-m-t 23:59:59');
                        break;
                }
            }

            $sql = "SELECT 
                        u.id as helper_id,
                        u.name as helper_name,
                        COUNT(o.id) as total_orders,
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) as total_quantity,
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as delivered_revenue,
                        COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                        COUNT(CASE WHEN o.newstat = 'processing' THEN 1 END) as processing_orders,
                        COUNT(CASE WHEN o.newstat = 'canceled' THEN 1 END) as canceled_orders,
                        COUNT(CASE WHEN o.newstat = 'unreachable' THEN 1 END) as unreachable_orders,
                        AVG(CASE WHEN o.newstat = 'deliver' THEN o.total_price END) as average_order_value,
                        SUM(o.total_price) as all_orders_revenue
                    FROM users u
                    LEFT JOIN orders o ON u.id = o.manager_id 
                        AND ((o.newstat = 'deliver' AND o.updated_at BETWEEN ? AND ?) 
                             OR (o.newstat != 'deliver' AND o.created_at BETWEEN ? AND ?))
                    WHERE u.role = 0 AND u.is_active = 1";
            
            $params = [$dateFrom, $dateTo, $dateFrom, $dateTo];
            
            if ($helperId) {
                $sql .= " AND u.id = ?";
                $params[] = $helperId;
            }
            
            $sql .= " GROUP BY u.id, u.name ORDER BY total_revenue DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Statistiques globales des ventes
     */
    public function getGlobalSalesStats($dateFrom = null, $dateTo = null) {
        try {
            if (!$dateFrom || !$dateTo) {
                $dateFrom = date('Y-m-01 00:00:00');
                $dateTo = date('Y-m-t 23:59:59');
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(id) as total_orders,
                    SUM(CASE WHEN newstat = 'deliver' THEN quantity ELSE 0 END) as total_quantity_sold,
                    SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END) as total_revenue,
                    AVG(CASE WHEN newstat = 'deliver' THEN total_price END) as average_order_value,
                    COUNT(CASE WHEN newstat = 'deliver' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN newstat = 'processing' THEN 1 END) as processing_orders,
                    COUNT(CASE WHEN newstat = 'canceled' THEN 1 END) as canceled_orders,
                    COUNT(CASE WHEN newstat = 'unreachable' THEN 1 END) as unreachable_orders,
                    COUNT(CASE WHEN newstat = 'new' THEN 1 END) as new_orders,
                    COUNT(CASE WHEN newstat = 'remind' THEN 1 END) as remind_orders,
                    SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END) as delivered_revenue,
                    SUM(CASE WHEN newstat = 'processing' THEN total_price ELSE 0 END) as processing_revenue,
                    SUM(total_price) as total_all_orders_revenue
                FROM orders 
                WHERE (
                    (newstat = 'deliver' AND updated_at BETWEEN ? AND ?) OR
                    (newstat != 'deliver' AND created_at BETWEEN ? AND ?)
                )
            ");
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Évolution des ventes par jour/semaine/mois
     */
    public function getSalesEvolution($period = 'day', $limit = 30) {
        try {
            $dateFormat = '';
            $dateFrom = '';
            
            switch ($period) {
                case 'day':
                    $dateFormat = '%Y-%m-%d';
                    $dateFrom = date('Y-m-d 00:00:00', strtotime('-30 days'));
                    break;
                case 'week':
                    $dateFormat = '%Y-%u';
                    $dateFrom = date('Y-m-d 00:00:00', strtotime('-12 weeks'));
                    break;
                case 'month':
                    $dateFormat = '%Y-%m';
                    $dateFrom = date('Y-m-01 00:00:00', strtotime('-11 months'));
                    break;
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE_FORMAT(CASE WHEN newstat = 'deliver' THEN updated_at ELSE created_at END, ?) as period,
                    COUNT(CASE WHEN newstat = 'deliver' THEN 1 END) as total_orders,
                    SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN newstat = 'deliver' THEN quantity ELSE 0 END) as total_quantity,
                    COUNT(id) as all_orders
                FROM orders 
                WHERE (CASE WHEN newstat = 'deliver' THEN updated_at ELSE created_at END) >= ?
                GROUP BY DATE_FORMAT(CASE WHEN newstat = 'deliver' THEN updated_at ELSE created_at END, ?)
                ORDER BY period DESC
                LIMIT ?
            ");
            $stmt->execute([$dateFormat, $dateFrom, $dateFormat, (int)$limit]);
            return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Statistiques par statut de commande
     */
    public function getOrderStatusStats($dateFrom = null, $dateTo = null) {
        try {
            if (!$dateFrom || !$dateTo) {
                $dateFrom = date('Y-m-01 00:00:00');
                $dateTo = date('Y-m-t 23:59:59');
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    newstat as status,
                    COUNT(id) as count,
                    SUM(total_price) as revenue,
                    AVG(total_price) as avg_revenue
                FROM orders 
                WHERE (
                    (newstat = 'deliver' AND updated_at BETWEEN ? AND ?) OR
                    (newstat != 'deliver' AND created_at BETWEEN ? AND ?)
                )
                GROUP BY newstat
                ORDER BY count DESC
            ");
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Top des pays par ventes
     */
    public function getTopCountries($limit = 10, $dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        client_country,
                        COUNT(id) as total_orders,
                        SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END) as total_revenue,
                        AVG(CASE WHEN newstat = 'deliver' THEN total_price END) as avg_order_value
                    FROM orders 
                    WHERE 1=1";
            
            $params = [];
            if ($dateFrom && $dateTo) {
                $sql .= " AND ((newstat = 'deliver' AND updated_at BETWEEN ? AND ?) OR (newstat != 'deliver' AND created_at BETWEEN ? AND ?))";
                $params[] = $dateFrom;
                $params[] = $dateTo;
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }
            
            $sql .= " GROUP BY client_country ORDER BY total_revenue DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Calcul des bénéfices (nécessite une table des coûts ou estimation)
     */
    public function getProfitAnalysis($dateFrom = null, $dateTo = null) {
        try {
            if (!$dateFrom || !$dateTo) {
                $dateFrom = date('Y-m-01 00:00:00');
                $dateTo = date('Y-m-t 23:59:59');
            }

            // Revenus et commandes livrées
            $stmt = $this->pdo->prepare("
                SELECT 
                    SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END) as total_revenue,
                    COUNT(CASE WHEN newstat = 'deliver' THEN 1 END) as delivered_orders
                FROM orders 
                WHERE (
                    (newstat = 'deliver' AND updated_at BETWEEN ? AND ?) OR
                    (newstat != 'deliver' AND created_at BETWEEN ? AND ?)
                )
            ");
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_revenue' => 0, 'delivered_orders' => 0];

            // Coûts d'achat et livraison à partir des snapshots
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(SUM(ocs.total_purchase_cost_at_sale), 0) as product_costs,
                    COALESCE(SUM(ocs.delivery_cost_at_sale), 0) as delivery_costs
                FROM order_cost_snapshots ocs
                JOIN orders o ON ocs.order_id = o.id
                WHERE o.newstat = 'deliver'
                  AND o.updated_at BETWEEN ? AND ?
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $costs = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['product_costs' => 0, 'delivery_costs' => 0];

            // Dépenses opérationnelles
            $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM operational_expenses WHERE expense_date BETWEEN ? AND ?");
            $stmt->execute([$dateFrom, $dateTo]);
            $operational = (float) ($stmt->fetchColumn() ?: 0);

            $totalRevenue = (float) $result['total_revenue'];
            $productCosts = (float) $costs['product_costs'];
            $deliveryCosts = (float) $costs['delivery_costs'];
            $grossProfit = $totalRevenue - $productCosts - $deliveryCosts;
            $netProfit = $grossProfit - $operational;

            return [
                'total_revenue' => $totalRevenue,
                'delivered_orders' => (int) $result['delivered_orders'],
                'product_costs' => $productCosts,
                'delivery_costs' => $deliveryCosts,
                'operational_expenses' => $operational,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'profit_margin' => $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0
            ];
        } catch (Exception $e) {
            return [];
        }
    }
}
