<?php
require_once '../vendor/autoload.php';
require_once '../src/Connectdb.php';

use Src\ProductManager;
use Src\AnalyticsManager;
use Src\Connectdb;

$cnx = Connectdb::getConnection();

$assistantId = $_GET['id'] ?? null;

if (!$assistantId) {
      header('Location: index.php');
      exit;
}


$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

if ($dateFrom && !strpos($dateFrom, ':')) {
      $dateFrom .= ' 00:00:00';
}
if ($dateTo && !strpos($dateTo, ':')) {
      $dateTo .= ' 23:59:59';
}

// Instancier les managers
$productManager = new ProductManager($cnx);
$analyticsManager = new AnalyticsManager($cnx);

// Récupérer les informations de l'assistante
$assistant = $analyticsManager->getAssistantById($assistantId);

if (!$assistant) {
      header('Location: index.php');
      exit;
}

// Obtenir les statistiques de l'assistante
$assistantStats = $analyticsManager->getAssistantStats($assistantId, $dateFrom, $dateTo);

// Pagination pour les commandes récentes
$limit = $_GET['limit'] ?? 25; // Limite par défaut: 25
$page = $_GET['page'] ?? 1; // Page actuelle

// Si "tout" est sélectionné, on met une très grande limite
if ($limit === 'all') {
      $limit = PHP_INT_MAX;
      $offset = 0;
} else {
      $limit = (int)$limit;
      $offset = ($page - 1) * $limit;
}

// Compter le nombre total de commandes pour la pagination
$totalOrders = $analyticsManager->countAssistantOrders($assistantId, $dateFrom, $dateTo);
$totalPages = ($limit != PHP_INT_MAX) ? ceil($totalOrders / $limit) : 1;

// Récupérer les commandes de l'assistante avec pagination
$recentOrders = $analyticsManager->getAssistantOrders($assistantId, $dateFrom, $dateTo, $limit, $offset);

// Récupérer les produits les plus vendus par cette assistante
$topProducts = $analyticsManager->getAssistantTopProducts($assistantId, $dateFrom, $dateTo, 5);

// Évolution des ventes (30 derniers jours)
$salesEvolution = $analyticsManager->getAssistantSalesEvolution($assistantId, 30);

// Statistiques par statut
$orderStatusStats = $analyticsManager->getAssistantOrderStatusStats($assistantId, $dateFrom, $dateTo);

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
      <link href="../assets/css/dashboard.css" rel="stylesheet">
      <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">
      <div class="container-fluid">
            <div class="row">
                  <!-- Sidebar -->
                  <?php include '../includes/sidebar.php'; ?>

                  <!-- Main content -->
                  <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 magenta-bg">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                              <h3>
                                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($assistant['name']) ?>
                                    <small class="text-muted" style="font-size: 0.6em;"><?= htmlspecialchars($assistant['email']) ?></small>
                              </h3>

                              <!-- Filtres de période -->
                              <div class="mb-2 mb-md-0">
                                    <form method="GET" class="d-flex gap-2 align-items-center">
                                          <input type="hidden" name="id" value="<?= $assistantId ?>">
                                          <div class="d-flex gap-2">
                                                <input type="date" name="date_from" class="form-control form-control-md" style="border-bottom: 2px solid var(--main);"
                                                      value="<?= $dateFrom ? date('Y-m-d', strtotime($dateFrom)) : '' ?>"
                                                      placeholder="Date début"
                                                      style="width: 140px;">
                                                <input type="date" name="date_to" class="form-control form-control-md" style="border-bottom: 2px solid var(--main);"
                                                      value="<?= $dateTo ? date('Y-m-d', strtotime($dateTo)) : '' ?>"
                                                      placeholder="Date fin"
                                                      style="width: 140px;">
                                          </div>

                                          <button type="submit" class="btn main-bg btn-sm">
                                                <i class="fas fa-search btn-primary"></i>
                                          </button>
                                          <!-- reset / retour -->
                                          <?php if ($dateFrom || $dateTo): ?>
                                                <a href="details.php?id=<?= $assistantId ?>" class="btn btn-warning btn-sm" title="Réinitialiser les filtres">
                                                      <i class="fas fa-times"></i>
                                                </a>
                                          <?php endif; ?>
                                          <a href="index.php" class="btn btn-secondary btn-sm" title="Retour à la liste">
                                                <i class="fas fa-arrow-left"></i>
                                          </a>
                                    </form>
                              </div>
                        </div>

                        <!-- Indication de période -->
                        <?php if ($dateFrom && $dateTo): ?>
                              <div class="alert alert-info mb-3">
                                    <i class="fas fa-calendar-alt"></i> Période : du <?= date('d/m/Y', strtotime($dateFrom)) ?> au <?= date('d/m/Y', strtotime($dateTo)) ?>
                              </div>
                        <?php else: ?>
                              <p></p>
                        <?php endif; ?>

                        <!-- Cartes statistiques principales -->
                        <div class="row mb-4">
                              <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card dashboard-card paper-bg h-100 py-2" style="border: 1px solid; border-left: 5px solid var(--main);">
                                          <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                      <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-uppercase mb-1 secondary-color">
                                                                  Chiffre d'Affaires
                                                            </div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                  <?= number_format($assistantStats['total_revenue'] ?? 0, 0, ',', ' ') ?> FCFA
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
                                                                  Commandes Livrées
                                                            </div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                  <?= $assistantStats['delivered_orders'] ?? 0 ?>
                                                                  <small class="text-muted" style="font-size: 0.7em;">/ <?= $assistantStats['total_orders'] ?? 0 ?> total</small>
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
                                                                  Taux de Conversion
                                                            </div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                  <?= number_format($assistantStats['conversion_rate'] ?? 0, 1) ?>%
                                                            </div>
                                                      </div>
                                                      <div class="col-auto">
                                                            <i class="fas fa-percentage stat-icon main-color"></i>
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
                                                                  Quantité Vendue
                                                            </div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                  <?= $assistantStats['total_quantity_sold'] ?? 0 ?> unités
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

                        <!-- Graphiques et produits top -->
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

                        <!-- Top produits vendus -->
                        <div class="row">
                              <div class="col-12">
                                    <div class="card dashboard-card mb-4 paper-bg" style="border: 1px solid var(--main);">
                                          <div class="card-header py-3">
                                                <h6 class="m-0 font-weight-bold text-primary">Top 5 Produits Vendus</h6>
                                          </div>
                                          <div class="card-body">
                                                <?php if (!empty($topProducts)): ?>
                                                      <div class="table-responsive">
                                                            <table class="table table-bordered table-hover">
                                                                  <thead>
                                                                        <tr>
                                                                              <th>#</th>
                                                                              <th>Produit</th>
                                                                              <th>Pays</th>
                                                                              <th>Commandes</th>
                                                                              <th>Quantité Vendue</th>
                                                                              <th>CA Généré</th>
                                                                        </tr>
                                                                  </thead>
                                                                  <tbody>
                                                                        <?php $rank = 1;
                                                                        foreach ($topProducts as $product): ?>
                                                                              <tr>
                                                                                    <td><?= $rank++ ?></td>
                                                                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                                                                    <td><?= htmlspecialchars($product['country']) ?></td>
                                                                                    <td><?= $product['total_orders'] ?></td>
                                                                                    <td><?= $product['total_sold'] ?></td>
                                                                                    <td><?= number_format($product['total_revenue'], 0, ',', ' ') ?> FCFA</td>
                                                                              </tr>
                                                                        <?php endforeach; ?>
                                                                  </tbody>
                                                            </table>
                                                      </div>
                                                <?php else: ?>
                                                      <div class="alert alert-info">Aucun produit vendu durant cette période.</div>
                                                <?php endif; ?>
                                          </div>
                                    </div>
                              </div>
                        </div>

                        <!-- Commandes récentes -->
                        <div class="row" id="commandes">
                              <div class="col-12">
                                    <div class="card dashboard-card mb-4 paper-bg" style="border: 1px solid var(--main);">
                                          <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                                <h6 class="m-0 font-weight-bold text-primary">
                                                      Commandes Récentes
                                                      <span class="badge bg-secondary"><?= $totalOrders ?> au total</span>
                                                </h6>
                                                <div class="d-flex gap-2 align-items-center">
                                                      <label class="me-2 mb-0" style="font-size: 0.9rem;">Afficher :</label>
                                                      <select class="form-select form-select-sm" id="limitSelector" style="width: auto;">
                                                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                                                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                                                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                                                            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                                                            <option value="all" <?= $limit == PHP_INT_MAX ? 'selected' : '' ?>>Tout</option>
                                                      </select>
                                                </div>
                                          </div>
                                          <div class="card-body">
                                                <?php if (!empty($recentOrders)): ?>
                                                      <div class="table-responsive">
                                                            <table class="table table-bordered table-hover table-sm">
                                                                  <thead>
                                                                        <tr>
                                                                              <th>ID</th>
                                                                              <th>Produit</th>
                                                                              <th>Pays</th>
                                                                              <th>Client</th>
                                                                              <th>Quantité</th>
                                                                              <th>Prix Total</th>
                                                                              <th>Statut</th>
                                                                              <th>Date</th>
                                                                        </tr>
                                                                  </thead>
                                                                  <tbody>
                                                                        <?php foreach ($recentOrders as $order): ?>
                                                                              <tr>
                                                                                    <td><?= $order['id'] ?></td>
                                                                                    <td><?= htmlspecialchars($order['product_name']) ?></td>
                                                                                    <td><?= htmlspecialchars($order['country']) ?></td>
                                                                                    <td><?= htmlspecialchars($order['client_name']) ?></td>
                                                                                    <td><?= $order['quantity'] ?></td>
                                                                                    <td><?= number_format($order['total_price'], 0, ',', ' ') ?> FCFA</td>
                                                                                    <td>
                                                                                          <?php
                                                                                          $badgeClass = 'secondary';
                                                                                          if ($order['newstat'] == 'deliver') $badgeClass = 'success';
                                                                                          elseif ($order['newstat'] == 'canceled') $badgeClass = 'danger';
                                                                                          elseif ($order['newstat'] == 'processing') $badgeClass = 'warning';
                                                                                          ?>
                                                                                          <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($order['newstat']) ?></span>
                                                                                    </td>
                                                                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                                                              </tr>
                                                                        <?php endforeach; ?>
                                                                  </tbody>
                                                            </table>
                                                      </div>

                                                      <!-- Pagination -->
                                                      <?php if ($limit != PHP_INT_MAX && $totalPages > 1): ?>
                                                            <nav aria-label="Pagination des commandes" class="mt-3">
                                                                  <ul class="pagination justify-content-center">
                                                                        <!-- Bouton Précédent -->
                                                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                                              <a class="page-link" href="?id=<?= $assistantId ?>&limit=<?= $limit ?>&page=<?= $page - 1 ?><?= $dateFrom ? '&date_from=' . date('Y-m-d', strtotime($dateFrom)) : '' ?><?= $dateTo ? '&date_to=' . date('Y-m-d', strtotime($dateTo)) : '' ?>#commandes">
                                                                                    Précédent
                                                                              </a>
                                                                        </li>

                                                                        <?php
                                                                        // Afficher les numéros de page
                                                                        $startPage = max(1, $page - 2);
                                                                        $endPage = min($totalPages, $page + 2);

                                                                        if ($startPage > 1): ?>
                                                                              <li class="page-item">
                                                                                    <a class="page-link" href="?id=<?= $assistantId ?>&limit=<?= $limit ?>&page=1<?= $dateFrom ? '&date_from=' . date('Y-m-d', strtotime($dateFrom)) : '' ?><?= $dateTo ? '&date_to=' . date('Y-m-d', strtotime($dateTo)) : '' ?>#commandes">1</a>
                                                                              </li>
                                                                              <?php if ($startPage > 2): ?>
                                                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                                              <?php endif; ?>
                                                                        <?php endif; ?>

                                                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                                              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                                                    <a class="page-link" href="?id=<?= $assistantId ?>&limit=<?= $limit ?>&page=<?= $i ?><?= $dateFrom ? '&date_from=' . date('Y-m-d', strtotime($dateFrom)) : '' ?><?= $dateTo ? '&date_to=' . date('Y-m-d', strtotime($dateTo)) : '' ?>#commandes">
                                                                                          <?= $i ?>
                                                                                    </a>
                                                                              </li>
                                                                        <?php endfor; ?>

                                                                        <?php if ($endPage < $totalPages): ?>
                                                                              <?php if ($endPage < $totalPages - 1): ?>
                                                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                                              <?php endif; ?>
                                                                              <li class="page-item">
                                                                                    <a class="page-link" href="?id=<?= $assistantId ?>&limit=<?= $limit ?>&page=<?= $totalPages ?><?= $dateFrom ? '&date_from=' . date('Y-m-d', strtotime($dateFrom)) : '' ?><?= $dateTo ? '&date_to=' . date('Y-m-d', strtotime($dateTo)) : '' ?>#commandes"><?= $totalPages ?></a>
                                                                              </li>
                                                                        <?php endif; ?>

                                                                        <!-- Bouton Suivant -->
                                                                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                                                              <a class="page-link" href="?id=<?= $assistantId ?>&limit=<?= $limit ?>&page=<?= $page + 1 ?><?= $dateFrom ? '&date_from=' . date('Y-m-d', strtotime($dateFrom)) : '' ?><?= $dateTo ? '&date_to=' . date('Y-m-d', strtotime($dateTo)) : '' ?>#commandes">
                                                                                    Suivant
                                                                              </a>
                                                                        </li>
                                                                  </ul>
                                                            </nav>

                                                            <div class="text-center text-muted small">
                                                                  Page <?= $page ?> sur <?= $totalPages ?> (<?= $totalOrders ?> commande(s) au total)
                                                            </div>
                                                      <?php endif; ?>

                                                <?php else: ?>
                                                      <div class="alert alert-info">Aucune commande trouvée pour cette période.</div>
                                                <?php endif; ?>
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

      <script>
            // Gestion du changement de limite pour le tableau des commandes
            document.getElementById('limitSelector').addEventListener('change', function() {
                  const urlParams = new URLSearchParams(window.location.search);
                  urlParams.set('limit', this.value);
                  urlParams.set('page', '1'); // Retour à la page 1 quand on change la limite
                  window.location.href = window.location.pathname + '?' + urlParams.toString() + '#commandes';
            });
      </script>
</body>

</html>