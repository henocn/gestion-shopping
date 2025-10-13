<?php
require_once 'vendor/autoload.php';
require_once 'src/Connectdb.php';

use Src\ProductManager;
use Src\FinanceManager;
use Src\AnalyticsManager;
use Src\Connectdb;

$cnx = Connectdb::getConnection();

$dateFrom = $_GET['date_from'] ?? "";
$dateTo = $_GET['date_to'] ?? "";



// Instancier les nouveaux managers
$productManager = new ProductManager($cnx);
$financeManager = new FinanceManager($cnx);
$analyticsManager = new AnalyticsManager($cnx);


// Obtenir les statistiques avec les nouvelles classes
$globalStats = $analyticsManager->getGlobalSalesStats($dateFrom, $dateTo);
$topProducts = $analyticsManager->getTopSellingProducts($dateFrom, $dateTo);
$salesEvolution = $analyticsManager->getSalesEvolution(30);
$orderStatusStats = $analyticsManager->getOrderStatusStats($dateFrom, $dateTo);
$assistantRanking = $analyticsManager->getAssistantsRanking($dateFrom, $dateTo);
$lowStock = $productManager->getLowStockAlerts(10);


?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Gestion Shopping</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar px-3 py-4 main-bg">
                <div class="text-center mb-4">
                    <h4 class="text-white">
                        <i class="fas fa-chart-line"></i>
                        LuxeManager
                    </h4>
                </div>

                <div class="nav flex-column pt-3 h-100">
                    <a class="nav-link mb-4 active" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link mb-4" href="./assistant/index.php">
                        <i class="fas fa-users"></i>Assistantes
                    </a>
                    <a class="nav-link mb-4" href="./products/index.php">
                        <i class="fas fa-box"></i> Iventaire
                    </a>
                    <a class="nav-link mb-4" href="./finance/index.php">
                        <i class="fas fa-coins"></i> Finance
                    </a>
                    <hr class="text-light">
                    <div class="text-light small mb-2">
                        Système de Gestion - Administration
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 magenta-bg">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Main Dashboard</h1>

                    <!-- Filtres de période -->
                    <div class="mb-2 mb-md-0">
                        <form method="GET" class="d-flex gap-2 align-items-center">
                            <div class="d-flex gap-2">
                                <input type="date" name="date_from" class="form-control form-control-md" style="border-bottom: 2px solid var(--main);"
                                    value="<?= $dateFrom ?>"
                                    style="width: 140px;">
                                <input type="date" name="date_to" class="form-control form-control-md" style="border-bottom: 2px solid var(--main);"
                                    value="<?= $dateTo ?>"
                                    style="width: 140px;">
                            </div>

                            <button type="submit" class="btn main-bg btn-sm">
                                <i class="fas fa-search btn-primary"></i>
                            </button>
                            <!-- reset -->
                            <a href="index.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i>
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Cartes statistiques principales -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card paper-bg h-100 py-2" style="border: 1px solid; border-left: 5px solid var(--main);">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1 secondary-color">
                                            Chiffre d'Affaires Total
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= $globalStats['total_revenue'] ?> FCFA
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-coins stat-icon main-color border"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card paper-bg h-100 py-2" style="border: 1px solid; border-left: 4px solid var(--main);">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1 secondary-color">
                                            Les Commandes Livrées
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= $globalStats['delivered_orders'] ?? 0 ?>
                                            <small class="text-muted" style="font-size: 0.7em;">/ <?= $globalStats['total_orders'] ?? 0 ?> total</small>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-truck stat-icon main-color"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card paper-bg h-100 py-2" style="border: 1px solid; border-left: 4px solid var(--main);">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1 secondary-color">
                                            Total des dépenses de la période
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= $globalStats['total_expenses'] ?? 0 ?> FCFA
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box stat-icon main-color"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card dashboard-card paper-bg h-100 py-2" style="border: 1px solid; border-left: 4px solid var(--main);">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1 secondary-color">
                                            Moyenne par Commande livrée
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= $globalStats['average_order_value'] ?> FCFA
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-bar stat-icon main-color"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques et alertes stock -->
                <div class="row">
                    <!-- Évolution des ventes -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card dashboard-card mb-4 paper-bg" style="border: 1px solid var(--main);">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold secondary-color">Évolution des Ventes (30 derniers jours)</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Répartition par statut -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card dashboard-card mb-4 paper-bg" style="border: 1px solid var(--main);">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Statut des Commandes</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Tableaux -->
                <div class="row">
                    <div class="card dashboard-card mb-4 paper-bg" style="border: 1px solid var(--main);">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold main-color">Produits les Plus Vendus</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Quantité</th>
                                            <th>Pays</th>
                                            <th>Vendeur(se)</th>
                                            <th>CA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topProducts as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td><?= $product['total_sold'] ?></td>
                                                <td><?= $product['country'] ?></td>
                                                <td><?= $product['assistant_name'] ?></td>
                                                <td><?= number_format($product['total_revenue']) ?> FCFA</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Graphique d'évolution des ventes
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($salesEvolution, 'period')) ?>,
                datasets: [{
                    label: 'Chiffre d\'Affaires',
                    data: <?= json_encode(array_column($salesEvolution, 'total_revenue')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Nombre de Commandes',
                    data: <?= json_encode(array_column($salesEvolution, 'total_orders')) ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left'
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Graphique de statut des commandes
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($orderStatusStats, 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($orderStatusStats, 'count')) ?>,
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74a3b',
                        '#858796'
                    ]
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
</body>

</html>