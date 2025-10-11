<?php
require_once '../vendor/autoload.php';

use Src\Connectdb;
use Src\ProductManager;
use Src\FinanceManager;

$cnx = Connectdb::getConnection();


$productManager = new ProductManager($cnx);
$financeManager = new FinanceManager($cnx);

$lowStockAlerts = $productManager->getLowStockAlerts();
$soldProducts = $productManager->getSoldProducts();


?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaire des produits</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/products.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 magenta-bg">
                <!-- Container pour les notifications toast -->
                <div class="toast-container"></div>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Inventaire des produits</h1>
                </div>

                <!-- Résumé statistique -->
                <div class="stats-container mb-4">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h3><i class="fas fa-shopping-cart me-2"></i>Total Achats</h3>
                                <div class="stat-value" id="totalAchats">
                                    <?php
                                    $totalAchats = $productManager->getTotalPurchaseAmount();
                                    echo number_format($totalAchats, 0, ',', ' ');
                                    ?> F
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h3><i class="fas fa-file-invoice-dollar me-2"></i>Total Dépenses</h3>
                                <div class="stat-value" id="totalDepenses">
                                    <?php
                                    $totalDepenses = $financeManager->getTotalExpensesByType('products');
                                    echo number_format($totalDepenses, 0, ',', ' ');
                                    ?> F
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h3><i class="fas fa-cash-register me-2"></i>Total Ventes</h3>
                                <div class="stat-value" id="totalVentes">
                                    <?php
                                    $totalVentes = $productManager->getTotalSalesAmount();
                                    echo number_format($totalVentes, 0, ',', ' ');
                                    ?> F
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h3><i class="fas fa-chart-line me-2"></i>Bénéfice Total</h3>
                                <?php
                                $beneficeTotal = $totalVentes - ($totalAchats + $totalDepenses);
                                $rendement = ($totalAchats > 0) ? ($beneficeTotal / $totalAchats) * 100 : 0;
                                $profitClass = $beneficeTotal >= 0 ? 'success' : 'danger';
                                ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="stat-value <?php echo $profitClass; ?>">
                                        <?php echo number_format($beneficeTotal, 0, ',', ' '); ?> F
                                    </div>
                                    <div class="stat-subvalue <?php echo $profitClass; ?>">
                                        Rendement: <?php echo number_format($rendement, 2, ',', ' '); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-5">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="productsTable" class="table table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Rendement</th>
                                        <th>Produit</th>
                                        <th>Qté vendue</th>
                                        <th>P.T. Achat</th>
                                        <th>P.T. Vente</th>
                                        <th>T. Dép.</th>
                                        <th>B.M/unité</th>
                                        <th>Bénéfice total</th>
                                        <th>Dépenses</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($soldProducts as $product): ?>
                                        <?php
                                        $profitClass = ($product['avg_profit_per_unit'] > 0) ? 'text-success' : 'text-danger';
                                        ?>
                                        <tr>
                                            <td class="<?php echo $profitClass; ?>"><b><?php echo number_format($product['rendement'], 2, ',', ' '); ?></b> %</td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo number_format($product['total_sold'], 0, ',', ' '); ?></td>
                                            <td><?php echo number_format($product['cost_price'], 0, ',', ' '); ?> F</td>
                                            <td><?php echo number_format($product['total_selling_price'], 0, ',', ' '); ?> F</td>
                                            <td><?php echo number_format($product['total_expenses'], 0, ',', ' '); ?> F</td>
                                            <td class="<?php echo $profitClass; ?>">
                                                <?php echo number_format($product['avg_profit_per_unit'], 0, ',', ' '); ?> F
                                            </td>
                                            <td class="<?php echo $profitClass; ?>">
                                                <?php echo number_format($product['total_profit'], 0, ',', ' '); ?> F
                                            </td>
                                            <td>
                                                <button
                                                    class="btn main-bg text-white btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#expenseModal<?php echo $product['id']; ?>">
                                                    <i class="fas fa-plus-circle"></i>
                                                </button>
                                                <button
                                                    class="btn secondary-bg text-white btn-sm"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#expensesListModal<?php echo $product['id']; ?>">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Modal pour ajouter une dépense -->
                                        <div class="modal fade" id="expenseModal<?php echo $product['id']; ?>" tabindex="-1" aria-labelledby="expenseModalLabel<?php echo $product['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header main-bg text-white">
                                                        <h5 class="modal-title" id="expenseModalLabel<?php echo $product['id']; ?>">
                                                            Ajouter une dépense pour <b class="primary-color"><?php echo htmlspecialchars($product['name']); ?></b>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body paper-bg">
                                                        <form action="save.php" method="POST">
                                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="cout<?php echo $product['id']; ?>" class="form-label">Montant de la dépense</label>
                                                                <input type="number" class="form-control" id="cout<?php echo $product['id']; ?>" name="cout" step="0.01" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="description<?php echo $product['id']; ?>" class="form-label">Description</label>
                                                                <textarea class="form-control" id="description<?php echo $product['id']; ?>" name="description" rows="3"></textarea>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <input type="submit" value="Créer dépense" name="valider" class="btn main-bg text-white">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php foreach ($soldProducts as $product): ?>
                    <!-- Modal Liste des dépenses -->
                    <div class="modal fade" id="expensesListModal<?php echo $product['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header main-bg text-white">
                                    <h5 class="modal-title">
                                        Liste des dépenses - <?php echo htmlspecialchars($product['name']); ?>
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered expenses-table" style="width:100%">
                                            <thead class="main-bg text-white">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Description</th>
                                                    <th>Montant</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $expenses = $financeManager->getProductExpenses($product['id']);
                                                foreach ($expenses as $expense):
                                                ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($expense['date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($expense['descrption']); ?></td>
                                                        <td class="text-end"><?php echo number_format($expense['cout'], 0, ',', ' '); ?> F</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot class="secondary-bg text-white">
                                                <tr>
                                                    <th colspan="2" class="text-end">Total</th>
                                                    <th class="text-end"><?php echo number_format($product['total_expenses'], 0, ',', ' '); ?> F</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>

    <!-- custom scripts -->
    <script src="../assets/js/products.js"></script>
</body>

</html>