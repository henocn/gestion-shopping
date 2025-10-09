<?php
require_once '../vendor/autoload.php';
require_once '../src/Connectdb.php';

use Src\Connectdb;
use Src\FinanceManager;
use Src\ProductManager;
use Src\AnalyticsManager;

// Connexion à la base de données
try {
    $database = new Connectdb();
    $pdo = $database->getConnection();
} catch (\PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Initialisation des managers
$financeManager = new FinanceManager($pdo);
$productManager = new ProductManager($pdo);
$analyticsManager = new AnalyticsManager($pdo);

// Filtres de date
$period = $_GET['period'] ?? 'month';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

if ($period == 'custom' && $dateFrom && $dateTo) {
    $dateFrom .= ' 00:00:00';
    $dateTo .= ' 23:59:59';
} elseif ($period != 'custom') {
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
        case 'last_month':
            $dateFrom = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $dateTo = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            break;
    }
}

// Récupérer les données
$globalStats = $analyticsManager->getGlobalSalesStats($dateFrom, $dateTo);
$expensesSummary = $financeManager->getExpensesSummary($dateFrom, $dateTo);
$topProfitableProducts = $financeManager->getTopProfitableProducts(10, $dateFrom, $dateTo);
$lowStockProducts = $productManager->getLowStockAlerts(10);

// Calculer les totaux
$totalRevenue = (float) ($globalStats['total_revenue'] ?? 0);
$totalExpenses = $financeManager->getTotalExpenses($dateFrom, $dateTo);
$grossProfit = $totalRevenue - $totalExpenses;
$profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance - Gestion Shopping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 sidebar px-3 py-4">
                <div class="text-center mb-4">
                    <h4 class="text-white"><i class="fas fa-chart-line"></i> Gestion Shopping</h4><small class="text-light">Finance</small>
                </div>
                <div class="nav flex-column"><a class="nav-link" href="../index.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a><a class="nav-link" href="../assistant/index.php"><i class="fas fa-users"></i> Assistantes</a><a class="nav-link active" href="index.php"><i class="fas fa-coins"></i> Finance</a></div>
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-coins"></i> Finance</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <form method="get" class="d-flex gap-2 align-items-center"><select name="period" class="form-select form-select-sm" onchange="toggleCustomDates(this.value)">
                                <option value="day" <?= $period == 'day' ? 'selected' : '' ?>>Aujourd'hui</option>
                                <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>Cette semaine</option>
                                <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Ce mois</option>
                                <option value="last_month" <?= $period == 'last_month' ? 'selected' : '' ?>>Mois dernier</option>
                                <option value="custom" <?= $period == 'custom' ? 'selected' : '' ?>>Personnalisé</option>
                            </select>
                            <div id="custom-dates" style="display: <?= $period == 'custom' ? 'flex' : 'none' ?>;" class="d-flex gap-2"><input type="date" name="date_from" class="form-control form-control-sm" value="<?= $period == 'custom' ? ($_GET['date_from'] ?? '') : '' ?>" style="width: 140px;"><input type="date" name="date_to" class="form-control form-control-sm" value="<?= $period == 'custom' ? ($_GET['date_to'] ?? '') : '' ?>" style="width: 140px;"></div><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filtrer</button>
                        </form>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Revenus</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalRevenue, 0) ?> FCFA</div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-coins stat-icon text-primary"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-danger h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Dépenses</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalExpenses, 0) ?> FCFA</div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-shopping-cart stat-icon text-danger"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-<?= $grossProfit >= 0 ? 'success' : 'danger' ?> h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-<?= $grossProfit >= 0 ? 'success' : 'danger' ?> text-uppercase mb-1">Bénéfice</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($grossProfit, 0) ?> FCFA</div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-chart-line stat-icon text-<?= $grossProfit >= 0 ? 'success' : 'danger' ?>"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Marge</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($profitMargin, 1) ?>%</div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-percentage stat-icon text-info"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-xl-6">
                        <div class="card dashboard-card mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Répartition</h6>
                            </div>
                            <div class="card-body"><canvas id="revenueChart" style="height: 250px;"></canvas></div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card dashboard-card mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Dépenses par Type</h6>
                            </div>
                            <div class="card-body"><?php if (!empty($expensesSummary)): ?><div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th class="text-end">Nb</th>
                                                    <th class="text-end">Montant</th>
                                                </tr>
                                            </thead>
                                            <tbody><?php foreach ($expensesSummary as $expense): ?><tr>
                                                        <td><?= htmlspecialchars($expense['type']) ?></td>
                                                        <td class="text-end"><?= $expense['transaction_count'] ?></td>
                                                        <td class="text-end"><?= number_format($expense['total_amount'], 0) ?> FCFA</td>
                                                    </tr><?php endforeach; ?></tbody>
                                        </table>
                                    </div><?php else: ?><div class="text-muted small">Aucune dépense.</div><?php endif; ?></div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card dashboard-card mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-trophy"></i> Produits Rentables</h6>
                            </div>
                            <div class="card-body"><?php if (!empty($topProfitableProducts)): ?><div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Produit</th>
                                                    <th class="text-end">Vendus</th>
                                                    <th class="text-end">CA</th>
                                                    <th class="text-end">Profit</th>
                                                </tr>
                                            </thead>
                                            <tbody><?php foreach ($topProfitableProducts as $index => $product): ?><tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                                        <td class="text-end"><?= $product['units_sold'] ?></td>
                                                        <td class="text-end"><?= number_format($product['total_revenue'], 0) ?> FCFA</td>
                                                        <td class="text-end text-success fw-bold"><?= number_format($product['estimated_profit'], 0) ?> FCFA</td>
                                                    </tr><?php endforeach; ?></tbody>
                                        </table>
                                    </div><?php else: ?><div class="text-muted">Aucune vente.</div><?php endif; ?></div>
                        </div>
                    </div>
                </div><?php if (!empty($lowStockProducts)): ?><div class="row mb-4">
                        <div class="col-12">
                            <div class="card dashboard-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-exclamation-triangle"></i> Stock Faible <span class="badge bg-warning ms-2"><?= count($lowStockProducts) ?></span></h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Produit</th>
                                                    <th class="text-end">Stock</th>
                                                    <th class="text-end">Seuil</th>
                                                    <th class="text-center">Statut</th>
                                                </tr>
                                            </thead>
                                            <tbody><?php foreach ($lowStockProducts as $product): ?><tr>
                                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                                        <td class="text-end"><span class="badge bg-<?= (int)$product['quantity'] == 0 ? 'danger' : 'warning' ?>"><?= (int)$product['quantity'] ?></span></td>
                                                        <td class="text-end"><?= (int)$product['low_stock_threshold'] ?></td>
                                                        <td class="text-center"><span class="badge bg-<?= (int)$product['quantity'] == 0 ? 'danger' : 'warning' ?>"><?= (int)$product['quantity'] == 0 ? 'Rupture' : 'Alerte' ?></span></td>
                                                    </tr><?php endforeach; ?></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><?php endif; ?>
            </main>
        </div>
    </div>
    <script>
        function toggleCustomDates(period) {
            const customDates = document.getElementById('custom-dates');
            if (period === 'custom') {
                customDates.style.display = 'flex';
            } else {
                customDates.style.display = 'none';
                document.querySelector('form').submit();
            }
        }
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Revenus', 'Dépenses', 'Bénéfice'],
                datasets: [{
                    data: [<?= $totalRevenue ?>, <?= $totalExpenses ?>, <?= max(0, $grossProfit) ?>],
                    backgroundColor: ['#28a745', '#dc3545', '#007bff']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>