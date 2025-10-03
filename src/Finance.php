<?php
namespace src;

use src\Connectdb;
use PDO;
use Exception;

class Finance {
    private $pdo;

    public function __construct() {
        $this->pdo = Connectdb::getConnection();
    }

    /**
     * ============================================================================
     * GESTION DES PRIX D'ACHAT DES PRODUITS
     * ============================================================================
     */

    /**
     * Enregistrer un achat de produits
     */
    public function recordProductPurchase($productId, $purchasePrice, $quantity, $supplier = null, $purchaseDate = null, $notes = null) {
        try {
            if (!$purchaseDate) {
                $purchaseDate = date('Y-m-d H:i:s');
            }

            $totalCost = $purchasePrice * $quantity;

            $stmt = $this->pdo->prepare("
                INSERT INTO product_purchase_history 
                (product_id, purchase_price, quantity, total_cost, supplier, purchase_date, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$productId, $purchasePrice, $quantity, $totalCost, $supplier, $purchaseDate, $notes]);

            // Mettre à jour le prix d'achat actuel
            $this->updateCurrentProductCost($productId, $purchasePrice, $purchaseDate);

            // Mettre à jour le stock (entrée)
            $purchaseId = (int) $this->pdo->lastInsertId();
            $this->updateStock($productId, (int)$quantity, 'in', 'achat', null, $purchaseId);

            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Erreur recordProductPurchase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour le prix d'achat actuel d'un produit
     */
    public function updateCurrentProductCost($productId, $purchasePrice, $effectiveDate = null) {
        try {
            if (!$effectiveDate) {
                $effectiveDate = date('Y-m-d');
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO product_current_costs (product_id, current_purchase_price, effective_date)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    current_purchase_price = VALUES(current_purchase_price),
                    effective_date = VALUES(effective_date)
            ");
            return $stmt->execute([$productId, $purchasePrice, $effectiveDate]);
        } catch (Exception $e) {
            error_log("Erreur updateCurrentProductCost: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir l'historique des achats d'un produit
     */
    public function getProductPurchaseHistory($productId, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pph.*, p.name as product_name
                FROM product_purchase_history pph
                JOIN products p ON pph.product_id = p.id
                WHERE pph.product_id = ?
                ORDER BY pph.purchase_date DESC
                LIMIT ?
            ");
            $stmt->execute([$productId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir la rentabilité des produits
     */
    public function getProductProfitability($limit = null) {
        try {
            $sql = "SELECT * FROM product_profitability ORDER BY profit_margin_percent DESC";
            if ($limit) {
                $sql .= " LIMIT ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$limit]);
            } else {
                $stmt = $this->pdo->query($sql);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * ============================================================================
     * GESTION DES SALAIRES
     * ============================================================================
     */

    /**
     * Créer ou mettre à jour un salaire
     */
    public function recordAssistantSalary($userId, $month, $baseSalary, $commissionRate = 0, $bonus = 0, $deductions = 0, $notes = null) {
        try {
            // Calculer les commissions basées sur les ventes du mois
            $salesData = $this->getAssistantMonthlySales($userId, $month);
            $commissionAmount = ($salesData['total_revenue'] * $commissionRate) / 100;

            $totalSalary = $baseSalary + $commissionAmount + $bonus - $deductions;

            $stmt = $this->pdo->prepare("
                INSERT INTO assistant_salaries 
                (user_id, month, base_salary, commission_rate, commission_amount, bonus, deductions, total_salary, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    base_salary = VALUES(base_salary),
                    commission_rate = VALUES(commission_rate),
                    commission_amount = VALUES(commission_amount),
                    bonus = VALUES(bonus),
                    deductions = VALUES(deductions),
                    total_salary = VALUES(total_salary),
                    notes = VALUES(notes)
            ");
            return $stmt->execute([$userId, $month, $baseSalary, $commissionRate, $commissionAmount, $bonus, $deductions, $totalSalary, $notes]);
        } catch (Exception $e) {
            error_log("Erreur recordAssistantSalary: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les ventes mensuelles d'une assistante
     */
    private function getAssistantMonthlySales($userId, $month) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN newstat = 'deliver' THEN 1 END) as total_orders,
                    SUM(CASE WHEN newstat = 'deliver' THEN total_price ELSE 0 END) as total_revenue
                FROM orders
                WHERE manager_id = ? 
                  AND DATE_FORMAT(CASE WHEN newstat = 'deliver' THEN updated_at ELSE created_at END, '%Y-%m') = ?
            ");
            $stmt->execute([$userId, $month]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['total_orders' => 0, 'total_revenue' => 0];
        }
    }

    /**
     * Marquer un salaire comme payé
     */
    public function markSalaryAsPaid($salaryId, $paymentDate = null) {
        try {
            if (!$paymentDate) {
                $paymentDate = date('Y-m-d');
            }

            $stmt = $this->pdo->prepare("
                UPDATE assistant_salaries 
                SET payment_status = 'paid', payment_date = ?
                WHERE id = ?
            ");
            return $stmt->execute([$paymentDate, $salaryId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtenir les salaires d'un mois
     */
    public function getMonthlySalaries($month) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.name as assistant_name, u.email
                FROM assistant_salaries s
                JOIN users u ON s.user_id = u.id
                WHERE s.month = ?
                ORDER BY u.name
            ");
            $stmt->execute([$month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * ============================================================================
     * GESTION DES DÉPENSES OPÉRATIONNELLES
     * ============================================================================
     */

    /**
     * Enregistrer une dépense
     */
    public function recordExpense($expenseType, $amount, $description, $expenseDate, $category = null, $vendor = null, $paymentMethod = 'cash', $paymentStatus = 'paid', $receiptNumber = null, $notes = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO operational_expenses 
                (expense_type, amount, description, expense_date, category, vendor, payment_method, payment_status, receipt_number, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $expenseType,
                $amount,
                $description,
                $expenseDate,
                $category,
                $vendor,
                $paymentMethod,
                $paymentStatus,
                $receiptNumber,
                $notes
            ]);
        } catch (Exception $e) {
            error_log("Erreur recordExpense: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les dépenses d'une période
     */
    public function getExpenses($dateFrom = null, $dateTo = null, $expenseType = null) {
        try {
            $sql = "SELECT * FROM operational_expenses WHERE 1=1";
            $params = [];

            if ($dateFrom && $dateTo) {
                $sql .= " AND expense_date BETWEEN ? AND ?";
                $params[] = $dateFrom;
                $params[] = $dateTo;
            }

            if ($expenseType) {
                $sql .= " AND expense_type = ?";
                $params[] = $expenseType;
            }

            $sql .= " ORDER BY expense_date DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir le total des dépenses par catégorie pour un mois
     */
    public function getMonthlyExpensesByCategory($month) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    expense_type,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM operational_expenses
                WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?
                GROUP BY expense_type
                ORDER BY total_amount DESC
            ");
            $stmt->execute([$month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * ============================================================================
     * COÛTS DE LIVRAISON
     * ============================================================================
     */

    /**
     * Enregistrer le coût de livraison d'une commande
     */
    public function recordDeliveryCost($orderId, $deliveryCost, $deliveryPartner = null, $deliveryDate = null, $notes = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO order_delivery_costs 
                (order_id, delivery_cost, delivery_partner, delivery_date, notes)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    delivery_cost = VALUES(delivery_cost),
                    delivery_partner = VALUES(delivery_partner),
                    delivery_date = VALUES(delivery_date),
                    notes = VALUES(notes)
            ");
            return $stmt->execute([$orderId, $deliveryCost, $deliveryPartner, $deliveryDate, $notes]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * ============================================================================
     * CAMPAGNES PUBLICITAIRES
     * ============================================================================
     */

    /**
     * Créer une campagne publicitaire
     */
    public function createCampaign($campaignName, $platform, $startDate, $budget, $endDate = null, $notes = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO advertising_campaigns 
                (campaign_name, platform, start_date, end_date, budget, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$campaignName, $platform, $startDate, $endDate, $budget, $notes]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Mettre à jour les statistiques d'une campagne
     */
    public function updateCampaignStats($campaignId, $spent = null, $impressions = null, $clicks = null, $conversions = null) {
        try {
            $updates = [];
            $params = [];

            if ($spent !== null) {
                $updates[] = "spent = ?";
                $params[] = $spent;
            }
            if ($impressions !== null) {
                $updates[] = "impressions = ?";
                $params[] = $impressions;
            }
            if ($clicks !== null) {
                $updates[] = "clicks = ?";
                $params[] = $clicks;
            }
            if ($conversions !== null) {
                $updates[] = "conversions = ?";
                $params[] = $conversions;
            }

            if (empty($updates)) {
                return false;
            }

            $params[] = $campaignId;
            $sql = "UPDATE advertising_campaigns SET " . implode(", ", $updates) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Lier une commande à une campagne
     */
    public function linkOrderToCampaign($orderId, $campaignId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO order_campaign_tracking (order_id, campaign_id)
                VALUES (?, ?)
            ");
            return $stmt->execute([$orderId, $campaignId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtenir les campagnes actives
     */
    public function getActiveCampaigns() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    c.*,
                    COUNT(oct.order_id) as tracked_orders,
                    CASE WHEN c.clicks > 0 THEN ROUND((c.conversions / c.clicks) * 100, 2) ELSE 0 END as conversion_rate,
                    CASE WHEN c.spent > 0 THEN ROUND(c.budget - c.spent, 2) ELSE c.budget END as remaining_budget
                FROM advertising_campaigns c
                LEFT JOIN order_campaign_tracking oct ON c.id = oct.campaign_id
                WHERE c.status = 'active'
                GROUP BY c.id
                ORDER BY c.start_date DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * ============================================================================
     * RAPPORTS FINANCIERS
     * ============================================================================
     */

    /**
     * Obtenir le rapport financier mensuel complet
     */
    public function getMonthlyFinancialSummary($month) {
        try {
            // Calculer via requêtes cohérentes avec updated_at pour livrées
            $stmt = $this->pdo->prepare("
                SELECT 
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity * COALESCE(pcc.current_purchase_price, 0) ELSE 0 END) as product_costs,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN COALESCE(odc.delivery_cost, 0) ELSE 0 END) as delivery_costs,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders
                FROM orders o
                LEFT JOIN products p ON o.product_id = p.id
                LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
                LEFT JOIN order_delivery_costs odc ON o.id = odc.order_id
                WHERE DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%Y-%m') = ?
            ");
            $stmt->execute([$month]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_revenue' => 0,
                'product_costs' => 0,
                'delivery_costs' => 0,
                'delivered_orders' => 0
            ];

            $result = [
                'total_revenue' => (float) $row['total_revenue'],
                'product_costs' => (float) $row['product_costs'],
                'delivery_costs' => (float) $row['delivery_costs'],
                'gross_profit' => (float) $row['total_revenue'] - (float) $row['product_costs'] - (float) $row['delivery_costs'],
                'delivered_orders' => (int) $row['delivered_orders']
            ];

            // Ajouter des détails supplémentaires
            $result['expense_breakdown'] = $this->getMonthlyExpensesByCategory($month);
            $result['salaries_detail'] = $this->getMonthlySalaries($month);

            // Ajout du profit net
            $totalExpenses = 0;
            foreach ($result['expense_breakdown'] as $e) {
                $totalExpenses += (float) ($e['total_amount'] ?? 0);
            }
            $totalSalaries = 0;
            foreach ($result['salaries_detail'] as $s) {
                $totalSalaries += (float) ($s['total_salary'] ?? 0);
            }
            $result['net_profit'] = $result['gross_profit'] - $totalExpenses - $totalSalaries;

            return $result;
        } catch (Exception $e) {
            error_log("Erreur getMonthlyFinancialSummary: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir la rentabilité des assistantes
     */
    public function getAssistantProfitability($month = null) {
        try {
            $sql = "SELECT * FROM assistant_profitability";
            $params = [];

            if ($month) {
                $sql .= " WHERE month = ?";
                $params[] = $month;
            }

            $sql .= " ORDER BY net_profit DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Calculer le profit d'une commande spécifique
     */
    public function calculateOrderProfit($orderId) {
        try {
            // Utiliser le snapshot si disponible, sinon fallback sur prix d'achat courant à la date
            $stmt = $this->pdo->prepare("SELECT * FROM order_cost_snapshots WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $snap = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($snap) {
                $stmt = $this->pdo->prepare("SELECT total_price, product_id, quantity FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                $delivery = $this->getDeliveryCost($orderId);
                $gross = ((float)$order['total_price']) - ((float)$snap['total_purchase_cost_at_sale']) - $delivery;
                return [
                    'id' => (int)$orderId,
                    'revenue' => (float)$order['total_price'],
                    'product_cost' => (float)$snap['total_purchase_cost_at_sale'],
                    'delivery_cost' => $delivery,
                    'net_profit' => $gross
                ];
            }

            // Fallback: prix d'achat le plus récent avant la livraison
            $stmt = $this->pdo->prepare("
                SELECT o.*, 
                       (SELECT pph.purchase_price 
                        FROM product_purchase_history pph 
                        WHERE pph.product_id = o.product_id 
                          AND pph.purchase_date <= o.updated_at
                        ORDER BY pph.purchase_date DESC, pph.id DESC
                        LIMIT 1) as purchase_price
                FROM orders o WHERE o.id = ?
            ");
            $stmt->execute([$orderId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
            $delivery = $this->getDeliveryCost($orderId);
            $productCost = ((float)($row['purchase_price'] ?? 0)) * (int)$row['quantity'];
            $gross = ((float)$row['total_price']) - $productCost - $delivery;
            return [
                'id' => (int)$orderId,
                'revenue' => (float)$row['total_price'],
                'product_cost' => $productCost,
                'delivery_cost' => $delivery,
                'net_profit' => $gross
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Enregistrer un snapshot des coûts pour une commande (idempotent)
     */
    public function snapshotOrderCosts($orderId) {
        try {
            // Vérifier existence
            $stmt = $this->pdo->prepare("SELECT id FROM order_cost_snapshots WHERE order_id = ?");
            $stmt->execute([$orderId]);
            if ($stmt->fetchColumn()) return true;

            // Récupérer commande
            $stmt = $this->pdo->prepare("SELECT id, product_id, quantity, updated_at FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $o = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$o) return false;

            // Prix d'achat à la date de livraison
            $stmt = $this->pdo->prepare("
                SELECT purchase_price FROM product_purchase_history 
                WHERE product_id = ? AND purchase_date <= ?
                ORDER BY purchase_date DESC, id DESC LIMIT 1
            ");
            $stmt->execute([(int)$o['product_id'], $o['updated_at']]);
            $pp = (float) ($stmt->fetchColumn() ?: 0);

            // Coût livraison
            $delivery = $this->getDeliveryCost($orderId);

            // Insérer snapshot
            $qty = (int) $o['quantity'];
            $tot = $pp * $qty;
            $stmt = $this->pdo->prepare("
                INSERT INTO order_cost_snapshots 
                (order_id, product_id, quantity, unit_purchase_price_at_sale, total_purchase_cost_at_sale, delivery_cost_at_sale)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([(int)$orderId, (int)$o['product_id'], $qty, $pp, $tot, $delivery]);
        } catch (Exception $e) {
            error_log('Erreur snapshotOrderCosts: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Appliquer l'impact stock lors de la livraison d'une commande (idempotent autant que possible)
     */
    public function applyOrderDeliveryImpact($orderId) {
        try {
            // Récupérer commande
            $stmt = $this->pdo->prepare("SELECT id, product_id, quantity FROM orders WHERE id = ? AND newstat = 'deliver'");
            $stmt->execute([$orderId]);
            $o = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$o) return false;

            // Sortie stock
            $this->updateStock((int)$o['product_id'], -1 * (int)$o['quantity'], 'out', 'vente', (int)$orderId, null);

            // Snapshot coûts
            $this->snapshotOrderCosts((int)$orderId);

            return true;
        } catch (Exception $e) {
            error_log('Erreur applyOrderDeliveryImpact: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir coût de livraison d'une commande (0 si non défini)
     */
    private function getDeliveryCost($orderId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COALESCE(delivery_cost, 0) FROM order_delivery_costs WHERE order_id = ?");
            $stmt->execute([$orderId]);
            return (float) ($stmt->fetchColumn() ?: 0);
        } catch (Exception $e) {
            return 0.0;
        }
    }

    /**
     * Mettre à jour le stock + tracer le mouvement
     */
    private function updateStock($productId, $deltaQty, $movementType, $reason = null, $orderId = null, $purchaseId = null) {
        try {
            $this->pdo->beginTransaction();
            // Upsert product_stock
            $stmt = $this->pdo->prepare("
                INSERT INTO product_stock (product_id, quantity)
                VALUES (?, GREATEST(0, ?))
                ON DUPLICATE KEY UPDATE quantity = GREATEST(0, quantity + VALUES(quantity))
            ");
            $stmt->execute([(int)$productId, (int)$deltaQty]);

            // Mouvement
            $stmt = $this->pdo->prepare("
                INSERT INTO stock_movements (product_id, order_id, purchase_id, movement_type, quantity, reason)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$productId,
                $orderId !== null ? (int)$orderId : null,
                $purchaseId !== null ? (int)$purchaseId : null,
                $movementType,
                (int)$deltaQty,
                $reason
            ]);
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Erreur updateStock: ' . $e->getMessage());
        }
    }

    /**
     * Alerte de stock bas
     */
    public function getLowStockAlerts($threshold = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ps.product_id, p.name, ps.quantity, ps.low_stock_threshold
                FROM product_stock ps
                JOIN products p ON ps.product_id = p.id
                WHERE ps.quantity <= COALESCE(?, ps.low_stock_threshold)
                ORDER BY ps.quantity ASC
            ");
            $stmt->execute([(int)$threshold]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * ============================================================================
     * OBJECTIFS ET BUDGETS
     * ============================================================================
     */

    /**
     * Définir un objectif mensuel
     */
    public function setMonthlyTarget($month, $targetRevenue, $userId = null, $targetOrders = 0, $targetProfit = 0, $notes = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO monthly_targets 
                (month, user_id, target_revenue, target_orders, target_profit, notes)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    target_revenue = VALUES(target_revenue),
                    target_orders = VALUES(target_orders),
                    target_profit = VALUES(target_profit),
                    notes = VALUES(notes)
            ");
            return $stmt->execute([$month, $userId, $targetRevenue, $targetOrders, $targetProfit, $notes]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Définir un budget mensuel
     */
    public function setMonthlyBudget($month, $category, $allocatedAmount, $notes = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO monthly_budgets 
                (month, budget_category, allocated_amount, notes)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    allocated_amount = VALUES(allocated_amount),
                    notes = VALUES(notes)
            ");
            return $stmt->execute([$month, $category, $allocatedAmount, $notes]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtenir les objectifs vs réalisations
     */
    public function getTargetVsActual($month) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    mt.*,
                    u.name as assistant_name,
                    COALESCE(ap.total_revenue, 0) as actual_revenue,
                    COALESCE(ap.delivered_orders, 0) as actual_orders,
                    COALESCE(ap.net_profit, 0) as actual_profit,
                    CASE 
                        WHEN mt.target_revenue > 0 THEN 
                            ROUND((COALESCE(ap.total_revenue, 0) / mt.target_revenue) * 100, 2)
                        ELSE 0 
                    END as achievement_rate
                FROM monthly_targets mt
                LEFT JOIN users u ON mt.user_id = u.id
                LEFT JOIN assistant_profitability ap ON mt.user_id = ap.assistant_id AND mt.month = ap.month
                WHERE mt.month = ?
            ");
            $stmt->execute([$month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir les budgets vs dépenses
     */
    public function getBudgetVsSpent($month) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    mb.*,
                    COALESCE(e.total_spent, 0) as actual_spent,
                    (mb.allocated_amount - COALESCE(e.total_spent, 0)) as remaining,
                    CASE 
                        WHEN mb.allocated_amount > 0 THEN 
                            ROUND((COALESCE(e.total_spent, 0) / mb.allocated_amount) * 100, 2)
                        ELSE 0 
                    END as usage_rate
                FROM monthly_budgets mb
                LEFT JOIN (
                    SELECT 
                        expense_type as category,
                        SUM(amount) as total_spent
                    FROM operational_expenses
                    WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?
                    GROUP BY expense_type
                ) e ON mb.budget_category = e.category
                WHERE mb.month = ?
            ");
            $stmt->execute([$month, $month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupérer les indicateurs de stock et de rentabilité par produit
     */
    public function getProductStockPerformance($month = null, $lowStockThreshold = 5) {
        try {
            $params = [];
            $monthFilter = '';

            if ($month) {
                $monthFilter = " AND DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%Y-%m') = ?";
                $params[] = $month;
            }

            $sql = "
                SELECT 
                    p.id,
                    p.name,
                    p.price AS selling_price,
                    COALESCE(pcc.current_purchase_price, 0) AS current_purchase_price,
                    COALESCE(pur.total_purchased, 0) AS total_purchased,
                    COALESCE(pur.total_cost, 0) AS total_purchase_cost,
                    COALESCE(sales_month.units_sold, 0) AS units_sold,
                    COALESCE(sales_month.revenue, 0) AS revenue,
                    COALESCE(sales_month.delivery_costs, 0) AS delivery_costs,
                    COALESCE(sales_all.units_sold_total, 0) AS units_sold_total
                FROM products p
                LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
                LEFT JOIN (
                    SELECT product_id, SUM(quantity) AS total_purchased, SUM(total_cost) AS total_cost
                    FROM product_purchase_history
                    GROUP BY product_id
                ) pur ON p.id = pur.product_id
                LEFT JOIN (
                    SELECT 
                        o.product_id,
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity ELSE 0 END) AS units_sold,
                        SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) AS revenue,
                        SUM(CASE WHEN o.newstat = 'deliver' THEN COALESCE(odc.delivery_cost, 0) ELSE 0 END) AS delivery_costs
                    FROM orders o
                    LEFT JOIN order_delivery_costs odc ON o.id = odc.order_id
                    WHERE 1 = 1 {$monthFilter}
                    GROUP BY o.product_id
                ) sales_month ON p.id = sales_month.product_id
                LEFT JOIN (
                    SELECT 
                        product_id,
                        SUM(CASE WHEN newstat = 'deliver' THEN quantity ELSE 0 END) AS units_sold_total
                    FROM orders
                    GROUP BY product_id
                ) sales_all ON p.id = sales_all.product_id
                WHERE p.status = 1
                ORDER BY p.name
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $products = [];
            $totalRevenue = 0;

            foreach ($rows as $row) {
                $totalPurchased = (int) ($row['total_purchased'] ?? 0);
                $totalPurchaseCost = (float) ($row['total_purchase_cost'] ?? 0);
                $unitsSoldMonth = (int) ($row['units_sold'] ?? 0);
                $unitsSoldTotal = (int) ($row['units_sold_total'] ?? 0);
                $revenue = (float) ($row['revenue'] ?? 0);
                $deliveryCosts = (float) ($row['delivery_costs'] ?? 0);
                $currentPurchasePrice = (float) ($row['current_purchase_price'] ?? 0);

                $averagePurchasePrice = 0;
                if ($totalPurchased > 0 && $totalPurchaseCost > 0) {
                    $averagePurchasePrice = $totalPurchaseCost / $totalPurchased;
                } elseif ($currentPurchasePrice > 0) {
                    $averagePurchasePrice = $currentPurchasePrice;
                }

                $costOfGoodsSold = $unitsSoldMonth * $averagePurchasePrice;
                $currentStock = max(0, $totalPurchased - $unitsSoldTotal);
                $currentStockValue = $currentStock * $averagePurchasePrice;

                $stockStatus = 'OK';
                if ($currentStock <= 0) {
                    $stockStatus = 'Rupture';
                } elseif ($currentStock <= $lowStockThreshold) {
                    $stockStatus = 'Stock bas';
                }

                $products[] = [
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                    'selling_price' => (float) $row['selling_price'],
                    'current_purchase_price' => $currentPurchasePrice,
                    'average_purchase_price' => $averagePurchasePrice,
                    'total_purchased' => $totalPurchased,
                    'units_sold_month' => $unitsSoldMonth,
                    'units_sold_total' => $unitsSoldTotal,
                    'revenue' => $revenue,
                    'delivery_costs' => $deliveryCosts,
                    'cost_of_goods_sold' => $costOfGoodsSold,
                    'current_stock' => $currentStock,
                    'current_stock_value' => $currentStockValue,
                    'stock_status' => $stockStatus,
                    'low_stock' => $stockStatus !== 'OK'
                ];

                $totalRevenue += $revenue;
            }

            $operationalExpenses = 0;
            if ($month) {
                $expenseBreakdown = $this->getMonthlyExpensesByCategory($month);
                foreach ($expenseBreakdown as $expense) {
                    $operationalExpenses += (float) ($expense['total_amount'] ?? 0);
                }
            }

            foreach ($products as &$product) {
                $revenue = $product['revenue'];
                $allocatedExpenses = ($totalRevenue > 0) ? ($operationalExpenses * ($revenue / $totalRevenue)) : 0;
                $product['allocated_expenses'] = $allocatedExpenses;
                $product['net_profit'] = $revenue - $product['cost_of_goods_sold'] - $product['delivery_costs'] - $allocatedExpenses;
                $product['margin_percent'] = $revenue > 0
                    ? round(($product['net_profit'] / $revenue) * 100, 2)
                    : 0;
            }

            return [
                'products' => $products,
                'operational_expenses' => $operationalExpenses,
                'total_revenue' => $totalRevenue,
                'low_stock_threshold' => $lowStockThreshold
            ];
        } catch (Exception $e) {
            error_log("Erreur getProductStockPerformance: " . $e->getMessage());
            return [
                'products' => [],
                'operational_expenses' => 0,
                'total_revenue' => 0,
                'low_stock_threshold' => $lowStockThreshold
            ];
        }
    }

    /**
     * Récupérer l'évolution financière mensuelle pour comparaison
     */
    public function getMonthlyFinancialTrends($monthsBack = 6) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%Y-%m') as month,
                    DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%M %Y') as month_label,
                    -- Revenus
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as revenue,
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                    -- Coûts produits (basé sur prix d'achat moyen)
                    SUM(CASE 
                        WHEN o.newstat = 'deliver' THEN 
                            o.quantity * COALESCE(pcc.current_purchase_price, 0)
                        ELSE 0 
                    END) as product_costs,
                    -- Coûts de livraison
                    SUM(CASE 
                        WHEN o.newstat = 'deliver' THEN 
                            COALESCE(odc.delivery_cost, 0)
                        ELSE 0 
                    END) as delivery_costs,
                    -- Profit brut
                    (SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) - 
                     SUM(CASE WHEN o.newstat = 'deliver' THEN o.quantity * COALESCE(pcc.current_purchase_price, 0) ELSE 0 END) -
                     SUM(CASE WHEN o.newstat = 'deliver' THEN COALESCE(odc.delivery_cost, 0) ELSE 0 END)) as gross_profit
                FROM orders o
                LEFT JOIN products p ON o.product_id = p.id
                LEFT JOIN product_current_costs pcc ON p.id = pcc.product_id
                LEFT JOIN order_delivery_costs odc ON o.id = odc.order_id
                WHERE DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL ? MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%Y-%m')
                ORDER BY month DESC
            ");
            $stmt->execute([$monthsBack]);
            $trendsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les dépenses opérationnelles par mois
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE_FORMAT(expense_date, '%Y-%m') as month,
                    SUM(amount) as operational_expenses
                FROM operational_expenses
                WHERE DATE_FORMAT(expense_date, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL ? MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
            ");
            $stmt->execute([$monthsBack]);
            $expensesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $expensesByMonth = [];
            foreach ($expensesData as $expense) {
                $expensesByMonth[$expense['month']] = (float) $expense['operational_expenses'];
            }

            // Récupérer les salaires par mois
            $stmt = $this->pdo->prepare("
                SELECT 
                    month,
                    SUM(total_salary) as total_salaries
                FROM assistant_salaries
                WHERE month >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL ? MONTH), '%Y-%m')
                  AND payment_status = 'paid'
                GROUP BY month
            ");
            $stmt->execute([$monthsBack]);
            $salariesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $salariesByMonth = [];
            foreach ($salariesData as $salary) {
                $salariesByMonth[$salary['month']] = (float) $salary['total_salaries'];
            }

            // Assembler les données avec calcul du profit net
            $trends = [];
            foreach ($trendsData as $trend) {
                $month = $trend['month'];
                $operationalExpenses = $expensesByMonth[$month] ?? 0;
                $salaries = $salariesByMonth[$month] ?? 0;
                $netProfit = (float) $trend['gross_profit'] - $operationalExpenses - $salaries;

                $trends[] = [
                    'month' => $month,
                    'month_label' => $trend['month_label'],
                    'revenue' => (float) $trend['revenue'],
                    'delivered_orders' => (int) $trend['delivered_orders'],
                    'product_costs' => (float) $trend['product_costs'],
                    'delivery_costs' => (float) $trend['delivery_costs'],
                    'operational_expenses' => $operationalExpenses,
                    'salaries' => $salaries,
                    'gross_profit' => (float) $trend['gross_profit'],
                    'net_profit' => $netProfit,
                    'margin_percent' => $trend['revenue'] > 0 ? round(($netProfit / (float) $trend['revenue']) * 100, 2) : 0
                ];
            }

            return array_reverse($trends); // Plus ancien au plus récent pour les graphiques
        } catch (Exception $e) {
            error_log("Erreur getMonthlyFinancialTrends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer la performance des assistantes sur plusieurs mois
     */
    public function getAssistantTrendComparison($monthsBack = 6) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id as assistant_id,
                    u.name as assistant_name,
                    DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%Y-%m') as month,
                    DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%M') as month_short,
                    -- Métriques de performance
                    COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) as delivered_orders,
                    SUM(CASE WHEN o.newstat = 'deliver' THEN o.total_price ELSE 0 END) as revenue,
                    COUNT(o.id) as total_orders,
                    ROUND((COUNT(CASE WHEN o.newstat = 'deliver' THEN 1 END) * 100.0 / NULLIF(COUNT(o.id), 0)), 2) as conversion_rate
                FROM users u
                LEFT JOIN orders o ON u.id = o.manager_id 
                    AND DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%Y-%m') >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL ? MONTH), '%Y-%m')
                WHERE u.role = 0 AND u.is_active = 1
                GROUP BY u.id, u.name, DATE_FORMAT(CASE WHEN o.newstat = 'deliver' THEN o.updated_at ELSE o.created_at END, '%Y-%m')
                ORDER BY u.name, month
            ");
            $stmt->execute([$monthsBack]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getAssistantTrendComparison: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les derniers achats de produits
     */
    public function getRecentPurchases($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pph.*, p.name as product_name
                FROM product_purchase_history pph
                INNER JOIN products p ON pph.product_id = p.id
                ORDER BY pph.purchase_date DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupérer les dernières dépenses opérationnelles
     */
    public function getRecentExpenses($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM operational_expenses
                ORDER BY expense_date DESC, id DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupérer les derniers salaires enregistrés
     */
    public function getRecentSalaries($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.name as assistant_name
                FROM assistant_salaries s
                INNER JOIN users u ON s.user_id = u.id
                ORDER BY s.updated_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupérer les dernières campagnes publicitaires (toutes statuts)
     */
    public function getRecentCampaigns($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM advertising_campaigns
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupérer les derniers coûts de livraison enregistrés
     */
    public function getRecentDeliveryCosts($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT odc.*, o.id as order_ref, o.total_price, o.newstat, o.created_at,
                       p.name as product_name,
                       u.name as assistant_name
                FROM order_delivery_costs odc
                INNER JOIN orders o ON odc.order_id = o.id
                INNER JOIN products p ON o.product_id = p.id
                LEFT JOIN users u ON o.manager_id = u.id
                ORDER BY odc.created_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Récupérer la rentabilité des dernières commandes livrées
     */
    public function getRecentOrderProfitability($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    o.id,
                    CONCAT('#', o.id) as order_ref,
                    o.updated_at as delivery_date,
                    o.quantity,
                    o.total_price as sale_amount,
                    o.newstat,
                    p.name as product_name,
                    u.name as assistant_name,
                    IFNULL(pph.purchase_price, 0) as purchase_price,
                    ROUND(IFNULL(pph.purchase_price, 0) * o.quantity, 2) as purchase_total,
                    ROUND(IFNULL(odc.delivery_cost, 0), 2) as delivery_cost,
                    ROUND(o.total_price - (IFNULL(pph.purchase_price, 0) * o.quantity) - IFNULL(odc.delivery_cost, 0), 2) as gross_profit,
                    CASE 
                        WHEN o.total_price > 0 THEN ROUND(((o.total_price - (IFNULL(pph.purchase_price, 0) * o.quantity) - IFNULL(odc.delivery_cost, 0)) / o.total_price) * 100, 2)
                        ELSE 0
                    END as margin_percent
                FROM orders o
                INNER JOIN products p ON o.product_id = p.id
                LEFT JOIN users u ON o.manager_id = u.id
                LEFT JOIN order_delivery_costs odc ON o.id = odc.order_id
                LEFT JOIN product_purchase_history pph ON pph.id = (
                    SELECT pph2.id
                    FROM product_purchase_history pph2
                    WHERE pph2.product_id = o.product_id
                      AND pph2.purchase_date <= o.updated_at
                    ORDER BY pph2.purchase_date DESC, pph2.id DESC
                    LIMIT 1
                )
                WHERE o.newstat = 'deliver'
                ORDER BY o.updated_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur getRecentOrderProfitability: " . $e->getMessage());
            return [];
        }
    }
}
