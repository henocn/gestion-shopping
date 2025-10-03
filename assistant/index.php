<?php
require_once '../vendor/autoload.php';

use src\Stat;
use src\Assistant;

$stat = new Stat();
$assistant = new Assistant();

// Traitement des filtres de période
$period = $_GET['period'] ?? 'month';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

// Calcul des dates selon la période sélectionnée
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
            $dateFrom = date('Y-m-01 00:00:00');
            $dateTo = date('Y-m-t 23:59:59');
            break;
        case 'last_month':
            $dateFrom = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $dateTo = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            break;
        case 'last_3_months':
            $dateFrom = date('Y-m-01 00:00:00', strtotime('-2 months'));
            $dateTo = date('Y-m-t 23:59:59');
            break;
        default:
            $dateFrom = date('Y-m-01 00:00:00');
            $dateTo = date('Y-m-t 23:59:59');
            break;
    }
}

// Vue admin : toutes les assistantes
$assistantId = $_GET['assistant_id'] ?? null;

if ($assistantId) {
    // Vue détaillée d'une assistante spécifique
    $assistantDashboard = $assistant->getAssistantDashboard($assistantId, $period);
    $recentOrders = $assistant->getRecentOrders($assistantId, 20);
    // Récupérer les informations de l'assistante depuis la base
    try {
        $pdo = src\Connectdb::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 0 AND is_active = 1");
        $stmt->execute([$assistantId]);
        $selectedAssistant = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $selectedAssistant = null;
    }
} else {
    // Vue comparative de toutes les assistantes
    $assistantRanking = $assistant->getAssistantRanking($period);
    $assistantComparison = $assistant->compareAssistants($period);
}

// Récupérer toutes les assistantes
try {
    $pdo = src\Connectdb::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 0 AND is_active = 1");
    $stmt->execute();
    $helpers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $helpers = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance des Assistantes - Gestion Shopping</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar px-3 py-4">
                <div class="text-center mb-4">
                    <h4 class="text-white">
                        <i class="fas fa-chart-line"></i>
                        Gestion Shopping
                    </h4>
                    <small class="text-light">Performance Assistantes</small>
                </div>
                
                <div class="nav flex-column">
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-tachometer-alt"></i> Tableau de Bord
                    </a>
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-users"></i> Performance Assistantes
                    </a>
                    <a class="nav-link" href="../finance/index.php">
                        <i class="fas fa-coins"></i> Finance & Rentabilité
                    </a>
                    
                    <hr class="text-light">
                    <small class="text-light mb-2">Assistantes:</small>
                    <?php foreach ($helpers as $helper): ?>
                    <a class="nav-link small <?= ($assistantId == $helper['id']) ? 'active' : '' ?>" 
                       href="?assistant_id=<?= $helper['id'] ?>&period=<?= $period ?>">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($helper['name']) ?>
                    </a>
                    <?php endforeach; ?>
                    
                    <a class="nav-link small <?= !$assistantId ? 'active' : '' ?>" href="?period=<?= $period ?>">
                        <i class="fas fa-chart-bar"></i> Vue d'ensemble
                    </a>
                    
                    <hr class="text-light">
                    <div class="text-light small mb-2">
                        Système de Gestion - Administration
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <?php if (isset($selectedAssistant)): ?>
                            Performance de <?= htmlspecialchars($selectedAssistant['name']) ?>
                        <?php else: ?>
                            Performance des Assistantes
                        <?php endif; ?>
                    </h1>
                    
                    <!-- Filtres de période -->
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <form method="get" class="d-flex gap-2 align-items-center">
                            <?php if (isset($assistantId)): ?>
                                <input type="hidden" name="assistant_id" value="<?= $assistantId ?>">
                            <?php endif; ?>
                            <select name="period" class="form-select form-select-sm" onchange="toggleCustomDates(this.value)">
                                <option value="day" <?= $period == 'day' ? 'selected' : '' ?>>Aujourd'hui</option>
                                <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>Cette semaine</option>
                                <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Ce mois</option>
                                <option value="last_month" <?= $period == 'last_month' ? 'selected' : '' ?>>Mois dernier</option>
                                <option value="last_3_months" <?= $period == 'last_3_months' ? 'selected' : '' ?>>3 derniers mois</option>
                                <option value="custom" <?= $period == 'custom' ? 'selected' : '' ?>>Période personnalisée</option>
                            </select>
                            
                            <div id="custom-dates" style="display: <?= $period == 'custom' ? 'flex' : 'none' ?>;" class="d-flex gap-2">
                                <input type="date" name="date_from" class="form-control form-control-sm" 
                                       value="<?= $period == 'custom' ? ($_GET['date_from'] ?? '') : '' ?>" 
                                       style="width: 140px;">
                                <input type="date" name="date_to" class="form-control form-control-sm" 
                                       value="<?= $period == 'custom' ? ($_GET['date_to'] ?? '') : '' ?>" 
                                       style="width: 140px;">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (isset($assistantDashboard)): ?>
                <!-- Dashboard d'une assistante spécifique -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card dashboard-card border-left-primary h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Commandes
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $assistantDashboard['stats']['total_orders'] ?? 0 ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shopping-cart stat-icon text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card dashboard-card border-left-success h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Chiffre d'Affaires
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($assistantDashboard['stats']['total_revenue'] ?? 0) ?> FCFA
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-coins stat-icon text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card dashboard-card border-left-info h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Taux de Livraison
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php 
                                                $deliveryRate = ($assistantDashboard['stats']['total_orders'] > 0) ? 
                                                    round(($assistantDashboard['stats']['delivered_orders'] / $assistantDashboard['stats']['total_orders']) * 100, 1) : 0;
                                                ?>
                                                <?= $deliveryRate ?>%
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-truck stat-icon text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card dashboard-card border-left-warning h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Panier Moyen
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= number_format($assistantDashboard['stats']['avg_order_value'] ?? 0) ?> FCFA
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-bar stat-icon text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques pour une assistante -->
                    <div class="row">
                        <!-- Évolution quotidienne -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card dashboard-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Évolution Quotidienne</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="dailyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Produits les plus vendus -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card dashboard-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Top Produits</h6>
                                </div>
                                <div class="card-body">
                                    <?php foreach (array_slice($assistantDashboard['top_products'], 0, 5) as $product): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span class="small"><?= htmlspecialchars($product['name']) ?></span>
                                            <span class="small font-weight-bold"><?= $product['quantity_sold'] ?></span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= ($product['quantity_sold'] / max(array_column($assistantDashboard['top_products'], 'quantity_sold'))) * 100 ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Commandes récentes -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card dashboard-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Commandes Récentes</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Client</th>
                                                    <th>Produit</th>
                                                    <th>Quantité</th>
                                                    <th>Montant</th>
                                                    <th>Statut</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                                    <td><?= htmlspecialchars($order['client_name']) ?></td>
                                                    <td><?= htmlspecialchars($order['product_name']) ?></td>
                                                    <td><?= $order['quantity'] ?></td>
                                                    <td><?= number_format($order['total_price'] ?? 0) ?> FCFA</td>
                                                    <td>
                                                        <?php
                                                        $badgeClass = '';
                                                        switch ($order['newstat']) {
                                                            case 'deliver': $badgeClass = 'bg-success'; break;
                                                            case 'processing': $badgeClass = 'bg-primary'; break;
                                                            case 'new': $badgeClass = 'bg-info'; break;
                                                            case 'canceled': $badgeClass = 'bg-danger'; break;
                                                            case 'unreachable': $badgeClass = 'bg-warning'; break;
                                                            default: $badgeClass = 'bg-secondary';
                                                        }
                                                        ?>
                                                        <span class="badge <?= $badgeClass ?>"><?= $order['newstat'] ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Vue d'ensemble des assistantes (Admin uniquement) -->
                    <div class="row">
                        <!-- Classement des assistantes -->
                        <div class="col-12">
                            <div class="card dashboard-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Classement des Assistantes</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Rang</th>
                                                    <th>Nom</th>
                                                    <th>Commandes</th>
                                                    <th>Quantité Vendue</th>
                                                    <th>Chiffre d'Affaires</th>
                                                    <th>Taux de Conversion</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assistantRanking as $assistant): ?>
                                                <tr>
                                                    <td>
                                                        <span class="ranking-badge <?= $assistant['rank'] <= 3 ? 'rank-' . $assistant['rank'] : 'rank-other' ?>">
                                                            <?= $assistant['rank'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($assistant['name']) ?></td>
                                                    <td><?= $assistant['total_orders'] ?></td>
                                                    <td><?= $assistant['total_quantity'] ?></td>
                                                    <td><?= number_format($assistant['total_revenue'] ?? 0) ?> FCFA</td>
                                                    <td><?= number_format($assistant['conversion_rate'] ?? 0, 1) ?>%</td>
                                                    <td>
                                                        <a href="?assistant_id=<?= $assistant['id'] ?>&period=<?= $period ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> Détails
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphique de comparaison -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card dashboard-card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Comparaison des Performances</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="comparisonChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour afficher/masquer les champs de date personnalisés
        function toggleCustomDates(period) {
            const customDates = document.getElementById('custom-dates');
            if (period === 'custom') {
                customDates.style.display = 'flex';
            } else {
                customDates.style.display = 'none';
                // Soumettre automatiquement pour les périodes prédéfinies
                document.querySelector('form').submit();
            }
        }
    </script>
    <script>
        <?php if (isset($assistantDashboard)): ?>
        // Graphique d'évolution quotidienne pour une assistante
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($assistantDashboard['daily_evolution'], 'date')) ?>,
                datasets: [{
                    label: 'Chiffre d\'Affaires',
                    data: <?= json_encode(array_column($assistantDashboard['daily_evolution'], 'revenue')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Nombre de Commandes',
                    data: <?= json_encode(array_column($assistantDashboard['daily_evolution'], 'orders')) ?>,
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
        <?php else: ?>
        // Graphique de comparaison des assistantes
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($assistantComparison, 'name')) ?>,
                datasets: [{
                    label: 'Chiffre d\'Affaires',
                    data: <?= json_encode(array_column($assistantComparison, 'total_revenue')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Nombre de Commandes',
                    data: <?= json_encode(array_column($assistantComparison, 'total_orders')) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left'
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
