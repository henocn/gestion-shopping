<?php
namespace src;

use src\Connectdb;
use PDO;
use Exception;

class Assistant {
    private $pdo;

    public function __construct() {
        $this->pdo = Connectdb::getConnection();
    }

    /**
     * Obtenir le tableau de bord d'une assistante
     */
    public function getAssistantDashboard($assistantId, $period = 'month') {
        try {
            // Définir les dates selon la période
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
                default:
                    $dateFrom = date('Y-m-01 00:00:00');
                    $dateTo = date('Y-m-t 23:59:59');
                    break;
            }

            // Statistiques principales
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(o.id) as total_orders,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) as total_quantity,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                    COUNT(CASE WHEN o.newstat = 'processing' THEN 1 END) as processing_orders,
                    COUNT(CASE WHEN o.newstat = 'new' THEN 1 END) as new_orders,
                    COUNT(CASE WHEN o.newstat = 'canceled' THEN 1 END) as canceled_orders,
                    COUNT(CASE WHEN o.newstat = 'unreachable' THEN 1 END) as unreachable_orders,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as delivered_revenue,
                    AVG(CASE WHEN o.newstat = 'deliver' THEN o.total_price END) as avg_order_value,
                    SUM(o.total_price) as all_orders_revenue
                FROM orders o
                WHERE o.manager_id = ? AND (
                    (o.newstat = 'deliver' AND o.updated_at BETWEEN ? AND ?) OR
                    (o.newstat != 'deliver' AND o.created_at BETWEEN ? AND ?)
                )
            ");
            $stmt->execute([$assistantId, $dateFrom, $dateTo, $dateFrom, $dateTo]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Produits les plus vendus par cette assistante
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.name,
                    p.id,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) as quantity_sold,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as revenue,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN o.id END) as orders_count
                FROM orders o
                INNER JOIN products p ON o.product_id = p.id
                WHERE o.manager_id = ? AND (
                    (o.newstat = 'deliver' AND o.updated_at BETWEEN ? AND ?) OR
                    (o.newstat != 'deliver' AND o.created_at BETWEEN ? AND ?)
                )
                GROUP BY p.id, p.name
                ORDER BY quantity_sold DESC
                LIMIT 10
            ");
            $stmt->execute([$assistantId, $dateFrom, $dateTo, $dateFrom, $dateTo]);
            $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Évolution quotidienne
            $stmt = $this->pdo->prepare("
                SELECT 
                    COALESCE(
                        CASE WHEN o.newstat = 'deliver' THEN DATE(o.updated_at) ELSE DATE(o.created_at) END
                    ) as date,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN o.id END) as orders,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as revenue
                FROM orders o
                WHERE o.manager_id = ? AND (
                    (o.newstat = 'deliver' AND o.updated_at BETWEEN ? AND ?) OR
                    (o.newstat != 'deliver' AND o.created_at BETWEEN ? AND ?)
                )
                GROUP BY date
                ORDER BY date ASC
            ");
            $stmt->execute([$assistantId, $dateFrom, $dateTo, $dateFrom, $dateTo]);
            $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'stats' => $stats,
                'top_products' => $topProducts,
                'daily_evolution' => $dailyStats,
                'period' => $period,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Obtenir les commandes récentes d'une assistante
     */
    public function getRecentOrders($assistantId, $limit = 20) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    o.*,
                    p.name as product_name,
                    p.price as unit_price_product
                FROM orders o
                INNER JOIN products p ON o.product_id = p.id
                WHERE o.manager_id = ?
                ORDER BY o.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$assistantId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Comparer les performances des assistantes
     */
    public function compareAssistants($period = 'month', $dateFrom = null, $dateTo = null) {
        try {
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
                    default:
                        $dateFrom = date('Y-m-01 00:00:00');
                        $dateTo = date('Y-m-t 23:59:59');
                        break;
                }
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id,
                    u.name,
                    u.country,
                    COUNT(o.id) as total_orders,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) as total_quantity,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as delivered_revenue,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                    ROUND((COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) * 100.0 / NULLIF(COUNT(o.id), 0)), 2) as conversion_rate,
                    AVG(CASE WHEN o.newstat = 'deliver' THEN o.total_price END) as avg_order_value
                FROM users u
                LEFT JOIN orders o ON u.id = o.manager_id AND (
                    (o.newstat = 'deliver' AND o.updated_at BETWEEN ? AND ?) OR
                    (o.newstat != 'deliver' AND o.created_at BETWEEN ? AND ?)
                )
                WHERE u.role = 0 AND u.is_active = 1
                GROUP BY u.id, u.name, u.country
                ORDER BY total_revenue DESC
            ");
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir le classement des assistantes
     */
    public function getAssistantRanking($period = 'month', $orderBy = 'revenue') {
        try {
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
                default:
                    $dateFrom = date('Y-m-01 00:00:00');
                    $dateTo = date('Y-m-t 23:59:59');
                    break;
            }

            $orderByColumn = 'total_revenue';
            switch ($orderBy) {
                case 'orders':
                    $orderByColumn = 'total_orders';
                    break;
                case 'quantity':
                    $orderByColumn = 'total_quantity';
                    break;
                case 'conversion':
                    $orderByColumn = 'conversion_rate';
                    break;
            }

            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id,
                    u.name,
                    COUNT(o.id) as total_orders,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) as total_quantity,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                    ROUND((COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) * 100.0 / NULLIF(COUNT(o.id), 0)), 2) as conversion_rate
                FROM users u
                LEFT JOIN orders o ON u.id = o.manager_id AND (
                    (o.newstat = 'deliver' AND o.updated_at BETWEEN ? AND ?) OR
                    (o.newstat != 'deliver' AND o.created_at BETWEEN ? AND ?)
                )
                WHERE u.role = 0 AND u.is_active = 1
                GROUP BY u.id, u.name
                ORDER BY {$orderByColumn} DESC
            ");
            $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ajouter le rang
            foreach ($results as $index => &$result) {
                $result['rank'] = $index + 1;
            }

            return $results;
        } catch (Exception $e) {
            return [];
        }
    }
}
