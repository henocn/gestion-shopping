<?php
session_start();
require_once '../vendor/autoload.php';
require_once '../src/Connectdb.php';

use Src\Connectdb;
use Src\FinanceManager;
use Src\ProductManager;
use Src\AnalyticsManager;

try {
    $database = new Connectdb();
    $pdo = $database->getConnection();
} catch (\PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$financeManager = new FinanceManager($pdo);
$productManager = new ProductManager($pdo);
$analyticsManager = new AnalyticsManager($pdo);

// Messages
$successMessage = $_SESSION['success'] ?? null;
$errorMessage = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Filtres
$period = $_GET['period'] ?? 'month';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$expenseType = $_GET['type'] ?? '';
$limit = (int)($_GET['limit'] ?? 25);
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Calcul des dates selon la période
if ($period === 'custom' && $dateFrom && $dateTo) {
    $dateFrom .= ' 00:00:00';
    $dateTo .= ' 23:59:59';
} else {
    switch ($period) {
        case 'day':
            $dateFrom = date('Y-m-d 00:00:00');
            $dateTo = date('Y-m-d 23:59:59');
            break;
        case 'week':
            $dateFrom = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $dateTo = date('Y-m-d 23:59:59', strtotime('sunday this week'));
            break;
        case 'last_month':
            $dateFrom = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $dateTo = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            break;
        case 'month':
        default:
            $dateFrom = date('Y-m-01 00:00:00');
            $dateTo = date('Y-m-t 23:59:59');
            break;
    }
}

// Récupération des données
$selectedExpenseType = $expenseType !== '' ? $expenseType : null;
$totalExpensesCount = $financeManager->countExpenses($dateFrom, $dateTo, $selectedExpenseType);
$totalPages = max(1, (int)ceil($totalExpensesCount / $limit));
$expenses = $financeManager->getExpenses($dateFrom, $dateTo, $selectedExpenseType, $limit, $offset);
$expensesSummary = $financeManager->getExpensesSummary($dateFrom, $dateTo);
$globalStats = $analyticsManager->getGlobalSalesStats($dateFrom, $dateTo);

// Calculs financiers
$totalRevenue = (float)($globalStats['total_revenue'] ?? 0);
$totalExpenses = $financeManager->getTotalExpenses($dateFrom, $dateTo);
$balance = $totalRevenue - $totalExpenses;
$averageExpense = $totalExpensesCount > 0 ? $totalExpenses / $totalExpensesCount : 0;

// Dépenses par type
$expensesByType = [
    'products' => $financeManager->getTotalExpensesByType('products'),
    'users' => $financeManager->getTotalExpensesByType('users'),
    'campagn' => $financeManager->getTotalExpensesByType('campagn'),
    'others' => $financeManager->getTotalExpensesByType('others')
];

// Données pour les formulaires
$productsList = $productManager->getAllProducts();
$assistants = $analyticsManager->getActiveAssistants();
$expenseTypes = ['products' => 'Produits', 'users' => 'Assistantes', 'campagn' => 'Campagnes', 'others' => 'Autres'];

$periodLabel = date('d/m/Y', strtotime($dateFrom)) . ' → ' . date('d/m/Y', strtotime($dateTo));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Dépenses - Finance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/products.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar px-3 py-4 main-bg">
                <div class="text-center mb-4">
                    <h4 class="text-white"><i class="fas fa-chart-line"></i> LuxeManager</h4>
                </div>
                <div class="nav flex-column pt-3 h-100">
                    <a class="nav-link mb-4" href="../index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link mb-4" href="../assistant/index.php">
                        <i class="fas fa-users"></i> Assistantes
                    </a>
                    <a class="nav-link mb-4" href="../products/index.php">
                        <i class="fas fa-box"></i> Inventaire
                    </a>
                    <a class="nav-link mb-4 active" href="index.php">
                        <i class="fas fa-coins"></i> Finance
                    </a>
                    <hr class="text-light">
                    <div class="text-light small mb-2">Système de Gestion - Administration</div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 magenta-bg">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-wallet me-2"></i>Gestion des Dépenses</h1>
                    <button class="btn main-bg text-white" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="fas fa-plus-circle me-2"></i>Nouvelle Dépense
                    </button>
                </div>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($successMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($errorMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="stats-container mb-4">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h3><i class="fas fa-dollar-sign me-2"></i>Revenus</h3>
                                <div class="stat-value text-success"><?= number_format($totalRevenue, 0, ",", " ") ?> F</div>
                                <div class="stat-subvalue"><?= $periodLabel ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h3><i class="fas fa-receipt me-2"></i>Dépenses</h3>
                                <div class="stat-value text-danger"><?= number_format($totalExpenses, 0, ",", " ") ?> F</div>
                                <div class="stat-subvalue"><?= $totalExpensesCount ?> mouvement(s)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h3><i class="fas fa-balance-scale me-2"></i>Balance</h3>
                                <div class="stat-value <?= $balance >= 0 ? "text-success" : "text-danger" ?>">
                                    <?= number_format($balance, 0, ",", " ") ?> F
                                </div>
                                <div class="stat-subvalue"><?= $balance >= 0 ? "Positif" : "Négatif" ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h3><i class="fas fa-calculator me-2"></i>Moyenne</h3>
                                <div class="stat-value"><?= number_format($averageExpense, 0, ",", " ") ?> F</div>
                                <div class="stat-subvalue">Par dépense</div>
                            </div>
                        </div>
                    </div>

                    <!-- Dépenses par catégorie -->
                    <div class="row g-4 mt-2">
                        <?php foreach ($expenseTypes as $typeKey => $typeLabel): ?>
                            <?php 
                            $amount = $expensesByType[$typeKey] ?? 0;
                            $percentage = $totalExpenses > 0 ? ($amount / $totalExpenses * 100) : 0;
                            ?>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <h3><i class="fas fa-tags me-2"></i><?= $typeLabel ?></h3>
                                    <div class="stat-value"><?= number_format($amount, 0, ',', ' ') ?> F</div>
                                    <div class="stat-subvalue"><?= number_format($percentage, 1) ?>% du total</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card mb-4 paper-bg">
                    <div class="card-body">
                        <form method="get" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Période</label>
                                <select name="period" class="form-select" onchange="toggleDates(this.value)">
                                    <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Aujourd'hui</option>
                                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Cette semaine</option>
                                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Ce mois</option>
                                    <option value="last_month" <?= $period === 'last_month' ? 'selected' : '' ?>>Mois dernier</option>
                                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Personnalisé</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="customDates" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
                                <label class="form-label fw-bold">Dates personnalisées</label>
                                <div class="d-flex gap-2">
                                    <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>">
                                    <input type="date" name="date_to" class="form-control" value="<?= $_GET['date_to'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Type</label>
                                <select name="type" class="form-select">
                                    <option value="">Tous</option>
                                    <?php foreach ($expenseTypes as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $expenseType === $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Lignes</label>
                                <select name="limit" class="form-select">
                                    <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10</option>
                                    <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25</option>
                                    <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                                    <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn main-bg text-white w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <!-- Liste des dépenses -->
                    <div class="col-xl-8 mb-4">
                        <div class="card">
                            <div class="card-header main-bg text-white d-flex justify-content-between align-items-center">
                                <h5 class="m-0"><i class="fas fa-list me-2"></i>Liste des Dépenses</h5>
                                <span class="badge bg-light text-dark"><?= $totalExpensesCount ?></span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (!empty($expenses)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Référence</th>
                                                    <th>Description</th>
                                                    <th class="text-end">Montant</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($expenses as $expense): ?>
                                                    <tr>
                                                        <td>
                                                            <small class="text-muted">
                                                                <i class="fas fa-calendar-day me-1"></i>
                                                                <?= date('d/m/Y', strtotime($expense['date'])) ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= 
                                                                $expense['type'] === 'products' ? 'primary' : 
                                                                ($expense['type'] === 'users' ? 'info' : 
                                                                ($expense['type'] === 'campagn' ? 'warning' : 'secondary'))
                                                            ?>">
                                                                <?= htmlspecialchars($expenseTypes[$expense['type']] ?? $expense['type']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($expense['product_name'])): ?>
                                                                <i class="fas fa-box text-primary me-1"></i>
                                                                <small><?= htmlspecialchars($expense['product_name']) ?></small>
                                                            <?php elseif (!empty($expense['manager_name'])): ?>
                                                                <i class="fas fa-user text-info me-1"></i>
                                                                <small><?= htmlspecialchars($expense['manager_name']) ?></small>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <small><?= !empty($expense['description']) ? htmlspecialchars($expense['description']) : '<span class="text-muted">-</span>' ?></small>
                                                        </td>
                                                        <td class="text-end">
                                                            <strong class="text-danger"><?= number_format($expense['cout'], 0, ',', ' ') ?> F</strong>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="4" class="text-end">Total :</th>
                                                    <th class="text-end text-danger"><?= number_format($totalExpenses, 0, ',', ' ') ?> F</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <?php if ($totalPages > 1): ?>
                                        <div class="card-footer">
                                            <nav>
                                                <ul class="pagination pagination-sm mb-0 justify-content-end">
                                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $page - 1 ?>&period=<?= $period ?>&type=<?= $expenseType ?>&limit=<?= $limit ?><?= $period === 'custom' ? '&date_from=' . $_GET['date_from'] . '&date_to=' . $_GET['date_to'] : '' ?>">Préc.</a>
                                                    </li>
                                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                            <a class="page-link" href="?page=<?= $i ?>&period=<?= $period ?>&type=<?= $expenseType ?>&limit=<?= $limit ?><?= $period === 'custom' ? '&date_from=' . $_GET['date_from'] . '&date_to=' . $_GET['date_to'] : '' ?>"><?= $i ?></a>
                                                        </li>
                                                    <?php endfor; ?>
                                                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $page + 1 ?>&period=<?= $period ?>&type=<?= $expenseType ?>&limit=<?= $limit ?><?= $period === 'custom' ? '&date_from=' . $_GET['date_from'] . '&date_to=' . $_GET['date_to'] : '' ?>">Suiv.</a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>Aucune dépense enregistrée pour cette période</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar statistiques -->
                    <div class="col-xl-4">
                        <!-- Répartition par type -->
                        <div class="card mb-4">
                            <div class="card-header main-bg text-white">
                                <h6 class="m-0"><i class="fas fa-chart-pie me-2"></i>Répartition</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($expensesSummary)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($expensesSummary as $summary): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                <div>
                                                    <strong><?= htmlspecialchars($expenseTypes[$summary['type']] ?? $summary['type']) ?></strong>
                                                    <br><small class="text-muted"><?= $summary['transaction_count'] ?> transaction(s)</small>
                                                </div>
                                                <span class="badge bg-danger rounded-pill"><?= number_format($summary['total_amount'], 0, ',', ' ') ?> F</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Aucune donnée disponible</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Ajouter Dépense -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header main-bg text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle Dépense</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="save.php">
                    <input type="hidden" name="action" value="create_expense">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Type de dépense <span class="text-danger">*</span></label>
                                <select name="expense_type" id="expenseTypeSelect" class="form-select" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($expenseTypes as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Montant (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="expense_amount" class="form-control" min="1" step="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date</label>
                                <input type="datetime-local" name="expense_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                            </div>
                            <div class="col-md-6" id="productField" style="display: none;">
                                <label class="form-label fw-bold">Produit</label>
                                <select name="product_id" class="form-select">
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($productsList as $product): ?>
                                        <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6" id="managerField" style="display: none;">
                                <label class="form-label fw-bold">Assistante</label>
                                <select name="manager_id" class="form-select">
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($assistants as $assistant): ?>
                                        <option value="<?= $assistant['id'] ?>"><?= htmlspecialchars($assistant['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="expense_description" class="form-control" rows="3" placeholder="Détails de la dépense (facultatif)"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn main-bg text-white">
                            <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDates(period) {
            const customDates = document.getElementById('customDates');
            customDates.style.display = period === 'custom' ? 'block' : 'none';
        }

        // Afficher/masquer les champs selon le type
        document.getElementById('expenseTypeSelect').addEventListener('change', function() {
            const productField = document.getElementById('productField');
            const managerField = document.getElementById('managerField');
            
            productField.style.display = 'none';
            managerField.style.display = 'none';
            
            if (this.value === 'products') {
                productField.style.display = 'block';
            } else if (this.value === 'users') {
                managerField.style.display = 'block';
            }
        });
    </script>
</body>
</html>