<?php
require_once '../vendor/autoload.php';

use src\Finance;
use src\Product;
use src\Connectdb;

function redirectWithMessage(string $status, string $message, string $month): void {
    $query = http_build_query([
        'month' => $month,
        'status' => $status,
        'message' => $message
    ]);
    header('Location: index.php?' . $query);
    exit;
}

function formatCurrency(float $value): string {
    return number_format($value, 0, ',', ' ');
}

function convertDateTime(?string $value): ?string {
    if (!$value) {
        return null;
    }

    $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    if ($dateTime instanceof DateTime) {
        return $dateTime->format('Y-m-d H:i:s');
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTime ? $date->format('Y-m-d H:i:s') : null;
}

$finance = new Finance();
$productService = new Product();
$pdo = Connectdb::getConnection();

$selectedMonth = $_GET['month'] ?? date('Y-m');
$statusQuery = $_GET['status'] ?? null;
$messageQuery = $_GET['message'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectMonth = $_POST['redirect_month'] ?? $selectedMonth;

    switch ($action) {
        case 'record_purchase':
            $productId = (int) ($_POST['product_id'] ?? 0);
            $purchasePrice = (float) ($_POST['purchase_price'] ?? 0);
            $quantity = (int) ($_POST['quantity'] ?? 0);
            $supplier = trim($_POST['supplier'] ?? '') ?: null;
            $purchaseDate = convertDateTime($_POST['purchase_date'] ?? null);
            $notes = trim($_POST['notes'] ?? '') ?: null;

            if (!$productId || $purchasePrice <= 0 || $quantity <= 0) {
                redirectWithMessage('error', 'Veuillez fournir un produit, un prix et une quantité valides.', $redirectMonth);
            }

            $result = $finance->recordProductPurchase($productId, $purchasePrice, $quantity, $supplier, $purchaseDate, $notes);

            if ($result) {
                redirectWithMessage('success', 'Achat de produit enregistré avec succès.', $redirectMonth);
            }

            redirectWithMessage('error', "Impossible d'enregistrer l'achat de produit.", $redirectMonth);
            break;

        case 'record_expense':
            $expenseType = $_POST['expense_type'] ?? null;
            $amount = (float) ($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $expenseDate = $_POST['expense_date'] ?? null;
            $category = trim($_POST['category'] ?? '') ?: null;
            $vendor = trim($_POST['vendor'] ?? '') ?: null;
            $paymentMethod = $_POST['payment_method'] ?? 'cash';
            $paymentStatus = $_POST['payment_status'] ?? 'paid';
            $receiptNumber = trim($_POST['receipt_number'] ?? '') ?: null;
            $notes = trim($_POST['notes'] ?? '') ?: null;

            if (!$expenseType || !$expenseDate || $amount <= 0 || !$description) {
                redirectWithMessage('error', 'Veuillez renseigner tous les champs obligatoires de la dépense.', $redirectMonth);
            }

            $result = $finance->recordExpense($expenseType, $amount, $description, $expenseDate, $category, $vendor, $paymentMethod, $paymentStatus, $receiptNumber, $notes);

            if ($result) {
                redirectWithMessage('success', 'Dépense opérationnelle enregistrée.', $redirectMonth);
            }

            redirectWithMessage('error', "Impossible d'enregistrer la dépense.", $redirectMonth);
            break;

        case 'record_salary':
            $assistantId = (int) ($_POST['assistant_id'] ?? 0);
            $month = $_POST['salary_month'] ?? $redirectMonth;
            $baseSalary = (float) ($_POST['base_salary'] ?? 0);
            $commissionRate = (float) ($_POST['commission_rate'] ?? 0);
            $bonus = (float) ($_POST['bonus'] ?? 0);
            $deductions = (float) ($_POST['deductions'] ?? 0);
            $notes = trim($_POST['notes'] ?? '') ?: null;

            if (!$assistantId || !$month || $baseSalary < 0) {
                redirectWithMessage('error', 'Veuillez sélectionner une assistante et un salaire de base.', $redirectMonth);
            }

            $result = $finance->recordAssistantSalary($assistantId, $month, $baseSalary, $commissionRate, $bonus, $deductions, $notes);

            if ($result) {
                redirectWithMessage('success', 'Salaire enregistré ou mis à jour.', $redirectMonth);
            }

            redirectWithMessage('error', 'Enregistrement du salaire impossible.', $redirectMonth);
            break;

        case 'record_delivery_cost':
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $deliveryCost = (float) ($_POST['delivery_cost'] ?? 0);
            $deliveryPartner = trim($_POST['delivery_partner'] ?? '') ?: null;
            $deliveryDate = $_POST['delivery_date'] ?? null;
            $notes = trim($_POST['notes'] ?? '') ?: null;

            if (!$orderId || $deliveryCost <= 0) {
                redirectWithMessage('error', 'Sélectionnez une commande et un coût de livraison valide.', $redirectMonth);
            }

            $result = $finance->recordDeliveryCost($orderId, $deliveryCost, $deliveryPartner, $deliveryDate, $notes);

            if ($result) {
                redirectWithMessage('success', 'Coût de livraison enregistré.', $redirectMonth);
            }

            redirectWithMessage('error', "Impossible d'enregistrer le coût de livraison.", $redirectMonth);
            break;

        case 'create_campaign':
            $campaignName = trim($_POST['campaign_name'] ?? '');
            $platform = $_POST['platform'] ?? null;
            $startDate = $_POST['start_date'] ?? null;
            $budget = (float) ($_POST['budget'] ?? 0);
            $endDate = $_POST['end_date'] ?? null;
            $notes = trim($_POST['notes'] ?? '') ?: null;

            if (!$campaignName || !$platform || !$startDate || $budget <= 0) {
                redirectWithMessage('error', 'Veuillez compléter les informations de la campagne.', $redirectMonth);
            }

            $campaignId = $finance->createCampaign($campaignName, $platform, $startDate, $budget, $endDate ?: null, $notes);

            if ($campaignId) {
                redirectWithMessage('success', 'Campagne publicitaire créée.', $redirectMonth);
            }

            redirectWithMessage('error', 'Impossible de créer la campagne.', $redirectMonth);
            break;

        case 'set_monthly_target':
            $month = $_POST['target_month'] ?? $redirectMonth;
            $userId = $_POST['target_assistant_id'] !== '' ? (int) $_POST['target_assistant_id'] : null;
            $targetRevenue = (float) ($_POST['target_revenue'] ?? 0);
            $targetOrders = (int) ($_POST['target_orders'] ?? 0);
            $targetProfit = (float) ($_POST['target_profit'] ?? 0);
            $notes = trim($_POST['notes'] ?? '') ?: null;

            if (!$month || $targetRevenue <= 0) {
                redirectWithMessage('error', "Spécifiez au minimum le mois et l'objectif de chiffre d'affaires.", $redirectMonth);
            }

            $result = $finance->setMonthlyTarget($month, $targetRevenue, $userId, $targetOrders, $targetProfit, $notes);

            if ($result) {
                redirectWithMessage('success', 'Objectif mensuel enregistré.', $redirectMonth);
            }

            redirectWithMessage('error', "Impossible d'enregistrer l'objectif.", $redirectMonth);
            break;

        case 'set_monthly_budget':
            $month = $_POST['budget_month'] ?? $redirectMonth;
            $category = $_POST['budget_category'] ?? null;
            $allocatedAmount = (float) ($_POST['allocated_amount'] ?? 0);
            $notes = trim($_POST['notes'] ?? '') ?: null;

            if (!$month || !$category || $allocatedAmount <= 0) {
                redirectWithMessage('error', 'Veuillez renseigner le mois, la catégorie et le montant.', $redirectMonth);
            }

            $result = $finance->setMonthlyBudget($month, $category, $allocatedAmount, $notes);

            if ($result) {
                redirectWithMessage('success', 'Budget mensuel défini.', $redirectMonth);
            }

            redirectWithMessage('error', 'Impossible de définir le budget.', $redirectMonth);
            break;

        default:
            redirectWithMessage('error', 'Action non reconnue.', $redirectMonth);
    }
}

$products = $productService->getAllProducts();
$assistantsList = $pdo->query("SELECT id, name FROM users WHERE role = 0 AND is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$orderOptionsStmt = $pdo->prepare("
    SELECT o.id, o.total_price, o.updated_at as delivery_date, p.name as product_name
    FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE o.newstat = 'deliver'
    ORDER BY o.updated_at DESC
    LIMIT 100
");
$orderOptionsStmt->execute();
$orderOptions = $orderOptionsStmt->fetchAll(PDO::FETCH_ASSOC);

$tableMap = [
    'products' => 'Produits actifs',
    'orders' => 'Commandes',
    'product_purchase_history' => 'Achats produits',
    'product_current_costs' => 'Prix actuels',
    'order_delivery_costs' => 'Coûts de livraison',
    'operational_expenses' => 'Dépenses',
    'assistant_salaries' => 'Salaires',
    'advertising_campaigns' => 'Campagnes publicitaires',
    'monthly_targets' => 'Objectifs',
    'monthly_budgets' => 'Budgets'
];

$tableStats = [];
foreach ($tableMap as $tableName => $label) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$tableName}");
    $tableStats[] = [
        'label' => $label,
        'total' => (int) $stmt->fetchColumn()
    ];
}

$financialSummary = $finance->getMonthlyFinancialSummary($selectedMonth) ?? [
    'total_revenue' => 0,
    'product_costs' => 0,
    'delivery_costs' => 0,
    'gross_profit' => 0,
    'salaries' => 0,
    'operational_expenses' => 0,
    'net_profit' => 0,
    'expense_breakdown' => [],
    'salaries_detail' => []
];

$expenseBreakdown = $financialSummary['expense_breakdown'] ?? [];
$salariesDetail = $financialSummary['salaries_detail'] ?? [];

$recentPurchases = $finance->getRecentPurchases(10);
$recentExpenses = $finance->getRecentExpenses(10);
$recentSalaries = $finance->getRecentSalaries(10);
$recentDeliveryCosts = $finance->getRecentDeliveryCosts(10);
$orderProfitability = $finance->getRecentOrderProfitability(12);
$assistantProfitability = $finance->getAssistantProfitability($selectedMonth);
$targets = $finance->getTargetVsActual($selectedMonth);
$budgetUsage = $finance->getBudgetVsSpent($selectedMonth);
$activeCampaigns = $finance->getActiveCampaigns();
$recentCampaigns = $finance->getRecentCampaigns(6);

$productPerformanceData = $finance->getProductStockPerformance($selectedMonth, 5);
$productPerformance = $productPerformanceData['products'];
$lowStockProducts = array_values(array_filter($productPerformance, fn($item) => $item['stock_status'] !== 'OK'));

usort($lowStockProducts, function ($a, $b) {
    return ($a['current_stock'] <=> $b['current_stock']) ?: strcmp($a['name'], $b['name']);
});

// Données de tendances comparatives
$monthlyTrends = $finance->getMonthlyFinancialTrends(6);
$assistantTrends = $finance->getAssistantTrendComparison(6);

// Préparer les données pour les graphiques de tendances
$trendsLabels = array_column($monthlyTrends, 'month_label');
$trendsRevenue = array_column($monthlyTrends, 'revenue');
$trendsNetProfit = array_column($monthlyTrends, 'net_profit');
$trendsMargin = array_column($monthlyTrends, 'margin_percent');
$trendsOrders = array_column($monthlyTrends, 'delivered_orders');

$expenseLabels = array_map(static fn ($item) => ucfirst($item['expense_type']), $expenseBreakdown);
$expenseValues = array_map(static fn ($item) => (float) $item['total_amount'], $expenseBreakdown);

$profitChartData = [
    'revenue' => (float) ($financialSummary['total_revenue'] ?? 0),
    'product_costs' => (float) ($financialSummary['product_costs'] ?? 0),
    'delivery_costs' => (float) ($financialSummary['delivery_costs'] ?? 0),
    'salaries' => (float) ($financialSummary['salaries'] ?? 0),
    'operational_expenses' => (float) ($financialSummary['operational_expenses'] ?? 0),
    'net_profit' => (float) ($financialSummary['net_profit'] ?? 0)
];

$totalCosts = $profitChartData['product_costs'] + $profitChartData['delivery_costs'] + $profitChartData['salaries'] + $profitChartData['operational_expenses'];
$netMargin = $profitChartData['revenue'] > 0 ? (($profitChartData['net_profit'] / $profitChartData['revenue']) * 100) : 0;

$expenseTypes = ['publicite', 'hebergement', 'domaine', 'marketing', 'logistique', 'emballage', 'livraison', 'fournitures', 'telecommunications', 'autres'];
$campaignPlatforms = ['facebook', 'instagram', 'google', 'tiktok', 'whatsapp', 'autres'];
$paymentMethods = ['cash', 'bank_transfer', 'mobile_money', 'credit_card', 'other'];
$paymentStatuses = ['paid', 'pending', 'cancelled'];
$budgetCategories = array_unique(array_merge(['salaires'], $expenseTypes));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finances & Rentabilité - Gestion Shopping</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 sidebar px-3 py-4">
                <div class="text-center mb-4">
                    <h4 class="text-white">
                        <i class="fas fa-coins"></i>
                        Gestion Shopping
                    </h4>
                    <small class="text-light">Module Financier & Rentabilité</small>
                </div>

                <div class="nav flex-column">
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-tachometer-alt"></i> Tableau de Bord
                    </a>
                    <a class="nav-link" href="../assistant/index.php">
                        <i class="fas fa-users"></i> Performance Assistantes
                    </a>
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-coins"></i> Finance & Rentabilité
                    </a>
                    <hr class="text-light">
                    <div class="text-light small mb-2">Actions rapides</div>
                    <button class="btn btn-light btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalPurchase">
                        <i class="fas fa-cart-plus"></i> Ajouter un achat
                    </button>
                    <button class="btn btn-light btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalExpense">
                        <i class="fas fa-receipt"></i> Ajouter une dépense
                    </button>
                    <button class="btn btn-light btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalSalary">
                        <i class="fas fa-user-check"></i> Salaire assistante
                    </button>
                    <button class="btn btn-light btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalDelivery">
                        <i class="fas fa-truck"></i> Coût livraison
                    </button>
                    <button class="btn btn-light btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalCampaign">
                        <i class="fas fa-bullhorn"></i> Campagne pub
                    </button>
                    <button class="btn btn-light btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalTarget">
                        <i class="fas fa-bullseye"></i> Objectif mensuel
                    </button>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalBudget">
                        <i class="fas fa-wallet"></i> Budget mensuel
                    </button>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h2">Pilotage Financier</h1>
                        <p class="text-muted mb-0">Analyse des coûts, marges et rentabilité pour une prise de décision éclairée.</p>
                    </div>
                    <form method="get" class="d-flex align-items-center gap-2">
                        <label for="finance-month" class="text-muted small mb-0">Mois d'analyse</label>
                        <input type="month" id="finance-month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" class="form-control form-control-sm" max="<?= date('Y-m') ?>">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fas fa-filter"></i> Mettre à jour
                        </button>
                    </form>
                </div>

                <?php if ($statusQuery && $messageQuery): ?>
                    <div class="alert alert-<?= $statusQuery === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($messageQuery) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <section class="mb-4">
                    <div class="row g-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="card dashboard-card finance-card revenue">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="text-uppercase text-muted fw-semibold">Chiffre d'affaires</h6>
                                            <h3 class="mb-0"><?= formatCurrency($profitChartData['revenue']) ?> FCFA</h3>
                                            <small class="text-muted">Commandes livrées sur <?= htmlspecialchars($selectedMonth) ?></small>
                                        </div>
                                        <span class="stat-icon text-primary"><i class="fas fa-chart-line"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card dashboard-card finance-card costs">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="text-uppercase text-muted fw-semibold">Coûts totaux</h6>
                                            <h3 class="mb-0"><?= formatCurrency($totalCosts) ?> FCFA</h3>
                                            <small class="text-muted">Produits, livraisons, salaires & dépenses</small>
                                        </div>
                                        <span class="stat-icon text-danger"><i class="fas fa-money-bill-wave"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card dashboard-card finance-card profit">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="text-uppercase text-muted fw-semibold">Profit net</h6>
                                            <h3 class="mb-0"><?= formatCurrency($profitChartData['net_profit']) ?> FCFA</h3>
                                            <small class="text-muted">Après frais opérationnels</small>
                                        </div>
                                        <span class="stat-icon text-success"><i class="fas fa-piggy-bank"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card dashboard-card finance-card margin">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div>
                                            <h6 class="text-uppercase text-muted fw-semibold">Marge nette</h6>
                                            <h3 class="mb-0"><?= number_format($netMargin, 2, ',', ' ') ?>%</h3>
                                            <small class="text-muted">Profit / CA</small>
                                        </div>
                                        <span class="stat-icon text-info"><i class="fas fa-percentage"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="row g-4 mb-4">
                    <div class="col-xl-8">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Évolution financière comparative (6 derniers mois)</h6>
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="trendView" id="trendRevenue" checked>
                                    <label class="btn btn-outline-primary" for="trendRevenue">CA & Profit</label>
                                    <input type="radio" class="btn-check" name="trendView" id="trendMargin">
                                    <label class="btn btn-outline-primary" for="trendMargin">Marges</label>
                                    <input type="radio" class="btn-check" name="trendView" id="trendOrders">
                                    <label class="btn btn-outline-primary" for="trendOrders">Commandes</label>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="financeTrendsChart" height="280"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Analyse mensuelle <?= htmlspecialchars($selectedMonth) ?></h6>
                                <span class="badge bg-light text-dark">Détails</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <canvas id="financeSummaryChart" height="180"></canvas>
                                </div>
                                <?php if (!empty($expenseBreakdown)): ?>
                                    <div class="mt-3">
                                        <h6 class="text-muted small">Répartition des dépenses</h6>
                                        <canvas id="expenseBreakdownChart" height="120"></canvas>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0 small">Aucune dépense enregistrée pour ce mois.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="row g-4 mb-4">
                    <div class="col-xl-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Rentabilité des commandes (dernières livraisons)</h6>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDelivery"><i class="fas fa-truck"></i> Ajouter coût</button>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table align-middle table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Produit</th>
                                            <th>Assistante</th>
                                            <th>CA</th>
                                            <th>Coût</th>
                                            <th>Profit</th>
                                            <th>Marge</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($orderProfitability)): ?>
                                            <?php foreach ($orderProfitability as $order): ?>
                                                <?php
                                                    $costTotal = ($order['purchase_total'] ?? 0) + ($order['delivery_cost'] ?? 0);
                                                    $profit = $order['gross_profit'] ?? 0;
                                                ?>
                                                <tr>
                                                    <td>#<?= (int) $order['id'] ?></td>
                                                    <td>
                                                        <span class="fw-semibold"><?= htmlspecialchars($order['product_name']) ?></span><br>
                                                        <small class="text-muted"><?= date('d/m/Y', strtotime($order['delivery_date'])) ?> · Qté <?= (int) $order['quantity'] ?></small>
                                                    </td>
                                                    <td><?= htmlspecialchars($order['assistant_name'] ?? 'Non assignée') ?></td>
                                                    <td class="text-primary fw-semibold"><?= formatCurrency((float) $order['sale_amount']) ?> FCFA</td>
                                                    <td class="text-muted">
                                                        <?= formatCurrency((float) $costTotal) ?> FCFA<br>
                                                        <small>Achat <?= formatCurrency((float) ($order['purchase_total'] ?? 0)) ?> + Liv <?= formatCurrency((float) ($order['delivery_cost'] ?? 0)) ?></small>
                                                    </td>
                                                    <td class="fw-bold <?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        <?= formatCurrency((float) $profit) ?> FCFA
                                                    </td>
                                                    <td><?= number_format((float) ($order['margin_percent'] ?? 0), 1, ',', ' ') ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">Aucune commande livrée disponible.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Rentabilité par produit</h6>
                                <a href="#modalPurchase" data-bs-toggle="modal" class="btn btn-sm btn-outline-primary"><i class="fas fa-cart-plus"></i> Nouvel achat</a>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table align-middle table-sm">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>CA (<?= htmlspecialchars($selectedMonth) ?>)</th>
                                            <th>Coûts</th>
                                            <th>Profit net</th>
                                            <th>Marge</th>
                                            <th>Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($productPerformance)): ?>
                                            <?php foreach ($productPerformance as $product): ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-semibold"><?= htmlspecialchars($product['name']) ?></span><br>
                                                        <small class="text-muted">Prix vente: <?= formatCurrency((float) $product['selling_price']) ?> FCFA · Achat moyen: <?= formatCurrency((float) $product['average_purchase_price']) ?> FCFA</small>
                                                    </td>
                                                    <td><?= formatCurrency((float) $product['revenue']) ?> FCFA<br><small class="text-muted">Qté <?= (int) $product['units_sold_month'] ?></small></td>
                                                    <td>
                                                        <small class="text-muted">Produits: <?= formatCurrency((float) $product['cost_of_goods_sold']) ?> FCFA</small><br>
                                                        <small class="text-muted">Livraison: <?= formatCurrency((float) $product['delivery_costs']) ?> FCFA</small>
                                                        <?php if ($productPerformanceData['operational_expenses'] > 0): ?>
                                                            <br><small class="text-muted">Dépenses alloc: <?= formatCurrency((float) $product['allocated_expenses']) ?> FCFA</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="fw-bold <?= $product['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        <?= formatCurrency((float) $product['net_profit']) ?> FCFA
                                                    </td>
                                                    <td><?= number_format((float) $product['margin_percent'], 1, ',', ' ') ?>%</td>
                                                    <td>
                                                        <span class="badge <?= $product['stock_status'] === 'OK' ? 'bg-success' : ($product['stock_status'] === 'Stock bas' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                                            <?= htmlspecialchars($product['stock_status']) ?>
                                                        </span>
                                                        <br><small class="text-muted">Qté <?= (int) $product['current_stock'] ?></small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">Rentabilité produit non disponible pour le moment.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                <?php if (!empty($productPerformance)): ?>
                                    <p class="small text-muted mb-0">
                                        Calcul: profit net = chiffre d'affaires - (coût des produits + coûts de livraison + part des dépenses opérationnelles).
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-4">
                    <div class="card dashboard-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">Alertes stock & réassort</h6>
                            <span class="badge <?= empty($lowStockProducts) ? 'bg-success' : 'bg-danger' ?>">
                                Seuil: <?= (int) $productPerformanceData['low_stock_threshold'] ?> unités
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($lowStockProducts)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th>Produit</th>
                                                <th>Stock restant</th>
                                                <th>Valeur (coût)</th>
                                                <th>Ventes <?= htmlspecialchars($selectedMonth) ?></th>
                                                <th>Statut</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lowStockProducts as $product): ?>
                                                <?php
                                                    $action = $product['stock_status'] === 'Rupture'
                                                        ? "Commander immédiatement"
                                                        : "Prévoir un réassort";
                                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-semibold"><?= htmlspecialchars($product['name']) ?></span><br>
                                                        <small class="text-muted">Prix vente: <?= formatCurrency((float) $product['selling_price']) ?> FCFA</small>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold"><?= (int) $product['current_stock'] ?></span>
                                                        <small class="text-muted"> unités restantes</small>
                                                    </td>
                                                    <td><?= formatCurrency((float) $product['current_stock_value']) ?> FCFA</td>
                                                    <td>
                                                        <small class="text-muted"><?= (int) $product['units_sold_month'] ?> vendues</small><br>
                                                        <small class="text-muted">CA: <?= formatCurrency((float) $product['revenue']) ?> FCFA</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $product['stock_status'] === 'Rupture' ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                                            <?= htmlspecialchars($product['stock_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="text-primary fw-semibold"><?= $action ?></span><br>
                                                        <small class="text-muted">Coût moyen: <?= formatCurrency((float) $product['average_purchase_price']) ?> FCFA</small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">Tous les stocks sont au-dessus du seuil minimal de <?= (int) $productPerformanceData['low_stock_threshold'] ?> unités.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="row g-4 mb-4">
                    <div class="col-lg-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Achats récents</h6>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalPurchase"><i class="fas fa-plus"></i></button>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($recentPurchases)): ?>
                                        <?php foreach ($recentPurchases as $purchase): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($purchase['product_name']) ?></h6>
                                                        <small class="text-muted">Fournisseur: <?= htmlspecialchars($purchase['supplier'] ?? 'N/A') ?></small>
                                                    </div>
                                                    <span class="badge bg-primary">Qté <?= (int) $purchase['quantity'] ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <small class="text-muted">Le <?= date('d/m/Y', strtotime($purchase['purchase_date'])) ?></small>
                                                    <strong><?= formatCurrency((float) $purchase['total_cost']) ?> FCFA</strong>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-group-item text-muted">Aucun achat enregistré.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Dépenses récentes</h6>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalExpense"><i class="fas fa-plus"></i></button>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($recentExpenses)): ?>
                                        <?php foreach ($recentExpenses as $expense): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 text-capitalize"><?= htmlspecialchars($expense['expense_type']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($expense['description']) ?></small>
                                                    </div>
                                                    <span class="badge bg-secondary"><?= date('d/m', strtotime($expense['expense_date'])) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <small class="text-muted">Par <?= htmlspecialchars($expense['vendor'] ?? 'interne') ?></small>
                                                    <strong><?= formatCurrency((float) $expense['amount']) ?> FCFA</strong>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-group-item text-muted">Aucune dépense récemment enregistrée.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Salaires récents</h6>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalSalary"><i class="fas fa-plus"></i></button>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($recentSalaries)): ?>
                                        <?php foreach ($recentSalaries as $salary): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($salary['assistant_name']) ?></h6>
                                                        <small class="text-muted">Mois <?= htmlspecialchars($salary['month']) ?> · Taux <?= number_format((float) $salary['commission_rate'], 1, ',', ' ') ?>%</small>
                                                    </div>
                                                    <span class="badge bg-success text-uppercase"><?= htmlspecialchars($salary['payment_status']) ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <small class="text-muted">Base <?= formatCurrency((float) $salary['base_salary']) ?> FCFA</small>
                                                    <strong><?= formatCurrency((float) $salary['total_salary']) ?> FCFA</strong>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-group-item text-muted">Aucun salaire enregistré pour le moment.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="row g-4 mb-4">
                    <div class="col-xl-7">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Comparaison performance assistantes (6 mois)</h6>
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="assistantMetric" id="assistantRevenue" checked>
                                    <label class="btn btn-outline-success" for="assistantRevenue">CA</label>
                                    <input type="radio" class="btn-check" name="assistantMetric" id="assistantOrders">
                                    <label class="btn btn-outline-success" for="assistantOrders">Commandes</label>
                                    <input type="radio" class="btn-check" name="assistantMetric" id="assistantConversion">
                                    <label class="btn btn-outline-success" for="assistantConversion">Taux</label>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="assistantComparisonChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Rentabilité par assistante</h6>
                                <span class="badge bg-light text-dark">Mois <?= htmlspecialchars($selectedMonth) ?></span>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table align-middle table-sm">
                                    <thead>
                                        <tr>
                                            <th>Assistante</th>
                                            <th>CA</th>
                                            <th>Profit</th>
                                            <th>Commandes</th>
                                            <th>Marge</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($assistantProfitability)): ?>
                                            <?php foreach ($assistantProfitability as $assistantData): ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-semibold"><?= htmlspecialchars($assistantData['assistant_name']) ?></span><br>
                                                        <small class="text-muted"><?= number_format((float) ($assistantData['achievement_rate'] ?? 0), 1, ',', ' ') ?>% objectif</small>
                                                    </td>
                                                    <td><?= formatCurrency((float) ($assistantData['total_revenue'] ?? 0)) ?> FCFA</td>
                                                    <td class="text-success fw-semibold"><?= formatCurrency((float) ($assistantData['net_profit'] ?? 0)) ?> FCFA</td>
                                                    <td><?= (int) ($assistantData['delivered_orders'] ?? 0) ?></td>
                                                    <td><?= number_format((float) ($assistantData['profit_margin'] ?? 0), 1, ',', ' ') ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Aucune donnée de rentabilité par assistante.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Campagnes publicitaires</h6>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCampaign"><i class="fas fa-plus"></i></button>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($activeCampaigns)): ?>
                                    <h6 class="text-muted">Campagnes actives</h6>
                                    <div class="list-group mb-3">
                                        <?php foreach ($activeCampaigns as $campaign): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($campaign['campaign_name']) ?></h6>
                                                        <small class="text-muted text-capitalize">Plateforme: <?= htmlspecialchars($campaign['platform']) ?></small>
                                                    </div>
                                                    <span class="badge bg-info text-dark">Budget restant: <?= formatCurrency((float) $campaign['remaining_budget']) ?> FCFA</span>
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <small class="text-muted">Dépensé: <?= formatCurrency((float) ($campaign['spent'] ?? 0)) ?> FCFA</small>
                                                    <small class="text-muted">Conversions: <?= (int) ($campaign['conversions'] ?? 0) ?> (<?= number_format((float) ($campaign['conversion_rate'] ?? 0), 1, ',', ' ') ?>%)</small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Aucune campagne active actuellement.</p>
                                <?php endif; ?>
                                <?php if (!empty($recentCampaigns)): ?>
                                    <h6 class="text-muted">Campagnes récentes</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($recentCampaigns as $campaign): ?>
                                            <span class="badge rounded-pill bg-light text-dark">
                                                <?= htmlspecialchars($campaign['campaign_name']) ?> · <?= formatCurrency((float) ($campaign['budget'] ?? 0)) ?> FCFA
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="row g-4 mb-5">
                    <div class="col-xl-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Objectifs mensuels</h6>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTarget"><i class="fas fa-plus"></i></button>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Scope</th>
                                            <th>Objectif CA</th>
                                            <th>Réalisation</th>
                                            <th>Taux</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($targets)): ?>
                                            <?php foreach ($targets as $target): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($target['assistant_name'] ?? 'Global') ?></td>
                                                    <td><?= formatCurrency((float) $target['target_revenue']) ?> FCFA</td>
                                                    <td><?= formatCurrency((float) ($target['actual_revenue'] ?? 0)) ?> FCFA</td>
                                                    <td>
                                                        <span class="badge <?= ($target['achievement_rate'] ?? 0) >= 100 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                            <?= number_format((float) ($target['achievement_rate'] ?? 0), 1, ',', ' ') ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Aucun objectif défini pour <?= htmlspecialchars($selectedMonth) ?>.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Budgets vs dépenses</h6>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalBudget"><i class="fas fa-plus"></i></button>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Budget</th>
                                            <th>Dépensé</th>
                                            <th>Reste</th>
                                            <th>Usage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($budgetUsage)): ?>
                                            <?php foreach ($budgetUsage as $budget): ?>
                                                <tr>
                                                    <td class="text-capitalize"><?= htmlspecialchars($budget['budget_category']) ?></td>
                                                    <td><?= formatCurrency((float) $budget['allocated_amount']) ?> FCFA</td>
                                                    <td><?= formatCurrency((float) ($budget['actual_spent'] ?? 0)) ?> FCFA</td>
                                                    <td><?= formatCurrency((float) ($budget['remaining'] ?? 0)) ?> FCFA</td>
                                                    <td>
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar" role="progressbar" style="width: <?= min(100, (float) ($budget['usage_rate'] ?? 0)) ?>%;"></div>
                                                        </div>
                                                        <small class="text-muted"><?= number_format((float) ($budget['usage_rate'] ?? 0), 1, ',', ' ') ?>%</small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">Aucun budget défini pour <?= htmlspecialchars($selectedMonth) ?>.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-5">
                    <div class="card dashboard-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-primary">Vue rapide de la base de données</h6>
                            <span class="badge bg-light text-dark">Suivi des volumes</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($tableStats as $stat): ?>
                                    <div class="col-xl-3 col-lg-4 col-md-6">
                                        <div class="mini-stat-card">
                                            <span class="mini-stat-label"><?= htmlspecialchars($stat['label']) ?></span>
                                            <h4 class="mini-stat-value"><?= number_format($stat['total'], 0, ',', ' ') ?></h4>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <?php $redirectMonthValue = htmlspecialchars($selectedMonth); ?>

    <!-- Modal: Enregistrer un achat -->
    <div class="modal fade" id="modalPurchase" tabindex="-1" aria-labelledby="modalPurchaseLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="record_purchase">
                    <input type="hidden" name="redirect_month" value="<?= $redirectMonthValue ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalPurchaseLabel"><i class="fas fa-cart-plus me-2"></i>Enregistrer un nouvel achat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Produit *</label>
                                <select name="product_id" class="form-select" required>
                                    <option value="">Sélectionnez un produit</option>
                                    <?php foreach ($products as $prod): ?>
                                        <option value="<?= (int) $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?> (Stock: <?= formatCurrency((float) $prod['price']) ?> FCFA)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Prix d'achat (FCFA) *</label>
                                <input type="number" name="purchase_price" step="0.01" min="0" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantité *</label>
                                <input type="number" name="quantity" min="1" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fournisseur</label>
                                <input type="text" name="supplier" class="form-control" placeholder="Nom du fournisseur">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date d'achat *</label>
                                <input type="datetime-local" name="purchase_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Conditions d'achat, numéro de facture..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer l'achat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Enregistrer une dépense -->
    <div class="modal fade" id="modalExpense" tabindex="-1" aria-labelledby="modalExpenseLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="record_expense">
                    <input type="hidden" name="redirect_month" value="<?= $redirectMonthValue ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalExpenseLabel"><i class="fas fa-receipt me-2"></i>Nouvelle dépense opérationnelle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Type de dépense *</label>
                                <select name="expense_type" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <?php foreach ($expenseTypes as $type): ?>
                                        <option value="<?= htmlspecialchars($type) ?>"><?= ucfirst($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Montant (FCFA) *</label>
                                <input type="number" name="amount" step="0.01" min="0" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date *</label>
                                <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description *</label>
                                <input type="text" name="description" class="form-control" placeholder="Objet de la dépense" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catégorie personnalisée</label>
                                <input type="text" name="category" class="form-control" placeholder="Campagne Facebook, Hébergeur...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fournisseur / Vendeur</label>
                                <input type="text" name="vendor" class="form-control" placeholder="Nom du fournisseur">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Mode de paiement</label>
                                <select name="payment_method" class="form-select">
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <option value="<?= htmlspecialchars($method) ?>"><?= ucfirst(str_replace('_', ' ', $method)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Statut</label>
                                <select name="payment_status" class="form-select">
                                    <?php foreach ($paymentStatuses as $state): ?>
                                        <option value="<?= htmlspecialchars($state) ?>"><?= ucfirst($state) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Référence / Reçu</label>
                                <input type="text" name="receipt_number" class="form-control" placeholder="N° facture ou reçu">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Informations supplémentaires"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer la dépense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Salaire assistante -->
    <div class="modal fade" id="modalSalary" tabindex="-1" aria-labelledby="modalSalaryLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="record_salary">
                    <input type="hidden" name="redirect_month" value="<?= $redirectMonthValue ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSalaryLabel"><i class="fas fa-user-check me-2"></i>Définir le salaire d'une assistante</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Assistante *</label>
                                <select name="assistant_id" class="form-select" required>
                                    <option value="">Sélectionnez</option>
                                    <?php foreach ($assistantsList as $assistant): ?>
                                        <option value="<?= (int) $assistant['id'] ?>"><?= htmlspecialchars($assistant['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mois *</label>
                                <input type="month" name="salary_month" class="form-control" value="<?= htmlspecialchars($selectedMonth) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Salaire de base *</label>
                                <input type="number" name="base_salary" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Taux commission (%)</label>
                                <input type="number" name="commission_rate" class="form-control" step="0.1" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Prime (bonus)</label>
                                <input type="number" name="bonus" class="form-control" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Déductions</label>
                                <input type="number" name="deductions" class="form-control" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Performance, objectifs atteints..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le salaire</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Coût de livraison -->
    <div class="modal fade" id="modalDelivery" tabindex="-1" aria-labelledby="modalDeliveryLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="record_delivery_cost">
                    <input type="hidden" name="redirect_month" value="<?= $redirectMonthValue ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalDeliveryLabel"><i class="fas fa-truck me-2"></i>Enregistrer un coût de livraison</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Commande livrée *</label>
                            <select name="order_id" class="form-select" required>
                                <option value="">Sélectionner une commande</option>
                                <?php foreach ($orderOptions as $order): ?>
                                    <option value="<?= (int) $order['id'] ?>">
                                        #<?= (int) $order['id'] ?> · <?= htmlspecialchars($order['product_name']) ?> · <?= formatCurrency((float) $order['total_price']) ?> FCFA
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Coût de livraison (FCFA) *</label>
                            <input type="number" name="delivery_cost" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Partenaire / Transporteur</label>
                            <input type="text" name="delivery_partner" class="form-control" placeholder="Nom du livreur">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Campagne publicitaire -->
    <div class="modal fade" id="modalCampaign" tabindex="-1" aria-labelledby="modalCampaignLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="create_campaign">
                    <input type="hidden" name="redirect_month" value="<?= $redirectMonthValue ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCampaignLabel"><i class="fas fa-bullhorn me-2"></i>Créer une campagne publicitaire</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom de la campagne *</label>
                                <input type="text" name="campaign_name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Plateforme *</label>
                                <select name="platform" class="form-select" required>
                                    <option value="">Choisir</option>
                                    <?php foreach ($campaignPlatforms as $platform): ?>
                                        <option value="<?= htmlspecialchars($platform) ?>"><?= ucfirst($platform) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Budget (FCFA) *</label>
                                <input type="number" name="budget" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date de début *</label>
                                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date de fin</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Objectifs, ciblage, KPI..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer la campagne</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Objectif mensuel -->
    <div class="modal fade" id="modalTarget" tabindex="-1" aria-labelledby="modalTargetLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="set_monthly_target">
                    <input type="hidden" name="redirect_month" value="<?= $redirectMonthValue ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTargetLabel"><i class="fas fa-bullseye me-2"></i>Définir un objectif mensuel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Mois *</label>
                            <input type="month" name="target_month" class="form-control" value="<?= htmlspecialchars($selectedMonth) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assistante (optionnel)</label>
                            <select name="target_assistant_id" class="form-select">
                                <option value="">Objectif global</option>
                                <?php foreach ($assistantsList as $assistant): ?>
                                    <option value="<?= (int) $assistant['id'] ?>"><?= htmlspecialchars($assistant['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Objectif de chiffre d'affaires (FCFA) *</label>
                            <input type="number" name="target_revenue" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Objectif de commandes</label>
                            <input type="number" name="target_orders" class="form-control" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Objectif de profit (FCFA)</label>
                            <input type="number" name="target_profit" class="form-control" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer l'objectif</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Budget mensuel -->
    <div class="modal fade" id="modalBudget" tabindex="-1" aria-labelledby="modalBudgetLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="action" value="set_monthly_budget">
                    <input type="hidden" name="redirect_month" value="<?= $redirectMonthValue ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalBudgetLabel"><i class="fas fa-wallet me-2"></i>Définir un budget mensuel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Mois *</label>
                            <input type="month" name="budget_month" class="form-control" value="<?= htmlspecialchars($selectedMonth) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catégorie *</label>
                            <select name="budget_category" class="form-select" required>
                                <option value="">Choisir</option>
                                <?php foreach ($budgetCategories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>"><?= ucfirst($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant alloué (FCFA) *</label>
                            <input type="number" name="allocated_amount" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer le budget</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const financeSummaryData = <?= json_encode($profitChartData, JSON_UNESCAPED_UNICODE) ?>;
        const expenseLabels = <?= json_encode($expenseLabels, JSON_UNESCAPED_UNICODE) ?>;
        const expenseValues = <?= json_encode($expenseValues, JSON_UNESCAPED_UNICODE) ?>;

        if (document.getElementById('financeSummaryChart')) {
            const ctx = document.getElementById('financeSummaryChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Chiffre d\'affaires', 'Coûts produits', 'Livraisons', 'Salaires', 'Dépenses', 'Profit net'],
                    datasets: [{
                        label: 'Montant (FCFA)',
                        data: [
                            financeSummaryData.revenue,
                            financeSummaryData.product_costs,
                            financeSummaryData.delivery_costs,
                            financeSummaryData.salaries,
                            financeSummaryData.operational_expenses,
                            financeSummaryData.net_profit
                        ],
                        backgroundColor: [
                            'rgba(78, 115, 223, 0.8)',
                            'rgba(221, 107, 32, 0.8)',
                            'rgba(54, 185, 204, 0.8)',
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(246, 194, 62, 0.8)',
                            'rgba(102, 126, 234, 0.9)'
                        ],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('fr-FR').format(context.parsed.y) + ' FCFA';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('fr-FR', { notation: 'compact', compactDisplay: 'short' }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        if (document.getElementById('expenseBreakdownChart') && expenseValues.length) {
            const ctxPie = document.getElementById('expenseBreakdownChart').getContext('2d');
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: expenseLabels,
                    datasets: [{
                        label: 'Dépenses',
                        data: expenseValues,
                        backgroundColor: [
                            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#fd7e14', '#20c997', '#6f42c1', '#0d6efd'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    return context.label + ': ' + new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }

        // Données pour les tendances comparatives
        const trendsData = {
            labels: <?= json_encode($trendsLabels) ?>,
            revenue: <?= json_encode($trendsRevenue) ?>,
            profit: <?= json_encode($trendsNetProfit) ?>,
            margin: <?= json_encode($trendsMargin) ?>,
            orders: <?= json_encode($trendsOrders) ?>
        };

        const assistantTrendsData = <?= json_encode($assistantTrends) ?>;

        // Graphique des tendances financières
        const trendsChart = document.getElementById('financeTrendsChart');
        let trendsChartInstance;

        function updateTrendsChart(type = 'revenue') {
            if (trendsChartInstance) {
                trendsChartInstance.destroy();
            }

            let datasets = [];
            let yAxisConfig = { beginAtZero: true };

            switch(type) {
                case 'revenue':
                    datasets = [
                        {
                            label: 'Chiffre d\'affaires',
                            data: trendsData.revenue,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Profit net',
                            data: trendsData.profit,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ];
                    yAxisConfig.ticks = {
                        callback: function(value) { return new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(value) + ' FCFA'; }
                    };
                    break;
                case 'margin':
                    datasets = [{
                        label: 'Marge bénéficiaire (%)',
                        data: trendsData.margin,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4,
                        fill: true
                    }];
                    yAxisConfig.ticks = {
                        callback: function(value) { return value + '%'; }
                    };
                    break;
                case 'orders':
                    datasets = [{
                        label: 'Commandes livrées',
                        data: trendsData.orders,
                        borderColor: '#6610f2',
                        backgroundColor: 'rgba(102, 16, 242, 0.1)',
                        tension: 0.4,
                        fill: true
                    }];
                    break;
            }

            trendsChartInstance = new Chart(trendsChart, {
                type: 'line',
                data: { labels: trendsData.labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let value = context.parsed.y;
                                    let suffix = type === 'margin' ? '%' : (type === 'orders' ? '' : ' FCFA');
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('fr-FR').format(value) + suffix;
                                }
                            }
                        }
                    },
                    scales: {
                        y: yAxisConfig
                    }
                }
            });
        }

        // Graphique de comparaison des assistantes
        const assistantCompChart = document.getElementById('assistantComparisonChart');
        let assistantCompInstance;

        function updateAssistantChart(metric = 'revenue') {
            if (assistantCompInstance) {
                assistantCompInstance.destroy();
            }

            // Regrouper les données par assistante
            const assistantNames = [...new Set(assistantTrendsData.map(item => item.assistant_name))];
            const months = [...new Set(assistantTrendsData.map(item => item.month_label))];

            const datasets = assistantNames.map((name, index) => {
                const assistantData = assistantTrendsData.filter(item => item.assistant_name === name);
                let data = [];
                let field = '';

                switch(metric) {
                    case 'revenue': field = 'revenue'; break;
                    case 'orders': field = 'delivered_orders'; break;
                    case 'conversion': field = 'conversion_rate'; break;
                }

                data = months.map(month => {
                    const monthData = assistantData.find(item => item.month_label === month);
                    return monthData ? parseFloat(monthData[field] || 0) : 0;
                });

                const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6610f2', '#fd7e14'];
                return {
                    label: name,
                    data: data,
                    borderColor: colors[index % colors.length],
                    backgroundColor: colors[index % colors.length] + '20',
                    tension: 0.4
                };
            });

            let yAxisConfig = { beginAtZero: true };
            if (metric === 'revenue') {
                yAxisConfig.ticks = { 
                    callback: function(value) { 
                        return new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(value) + ' FCFA'; 
                    } 
                };
            } else if (metric === 'conversion') {
                yAxisConfig.ticks = { callback: function(value) { return value + '%'; } };
            }

            assistantCompInstance = new Chart(assistantCompChart, {
                type: 'line',
                data: { labels: months, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let value = context.parsed.y;
                                    let suffix = metric === 'conversion' ? '%' : (metric === 'orders' ? '' : ' FCFA');
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('fr-FR').format(value) + suffix;
                                }
                            }
                        }
                    },
                    scales: { y: yAxisConfig }
                }
            });
        }

        // Initialiser les graphiques de tendances
        if (trendsChart && assistantCompChart) {
            updateTrendsChart('revenue');
            updateAssistantChart('revenue');

            // Event listeners pour les boutons radio
            document.querySelectorAll('input[name="trendView"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        const type = this.id.replace('trend', '').toLowerCase();
                        updateTrendsChart(type);
                    }
                });
            });

            document.querySelectorAll('input[name="assistantMetric"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        const metric = this.id.replace('assistant', '').toLowerCase();
                        updateAssistantChart(metric);
                    }
                });
            });
        }
    </script>
</body>
</html>
