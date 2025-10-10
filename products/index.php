<?php
require_once '../vendor/autoload.php';

use Src\Connectdb;
use Src\ProductManager;
use Src\FinanceManager;

$db = new Connectdb();
$pdo = $db->getConnection();
$productManager = new ProductManager($pdo);
$financeManager = new FinanceManager($pdo);

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
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <style>
        .dataTables_wrapper .dataTables_filter input {
            margin-left: 0.5em;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.375rem 1.75rem 0.375rem 0.75rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--main) !important;
            border-color: var(--main) !important;
            color: white !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: white !important;
        }
    </style>
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
                    <a class="nav-link mb-4" href="../index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link mb-4" href="assistant/index.php">
                        <i class="fas fa-users"></i>Assistantes
                    </a>
                    <a class="nav-link mb-4 active" href="index.php">
                        <i class="fas fa-box"></i> Iventaire
                    </a>
                    <a class="nav-link mb-4" href="finance/index.php">
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
                    <h1 class="h2">Inventaire des produits</h1>
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
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#expenses<?php echo $product['id']; ?>"
                                                    aria-expanded="false"
                                                    aria-controls="expenses<?php echo $product['id']; ?>">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Ligne extensible pour les dépenses -->
                                        <tr class="collapse" id="expenses<?php echo $product['id']; ?>">
                                            <td colspan="9" class="p-0">
                                                <div class="mx-4 my-2">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered mb-0">
                                                            <thead class="main-bg text-white">
                                                                <tr class="text-center">
                                                                    <th style="width: 20%">Date</th>
                                                                    <th style="width: 60%">Description</th>
                                                                    <th style="width: 20%">Montant</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                $expenses = $financeManager->getProductExpenses($product['id']);
                                                                foreach ($expenses as $expense): 
                                                                ?>
                                                                <tr>
                                                                    <td class="text-center align-middle"><?php echo date('d/m/Y H:i', strtotime($expense['date'])); ?></td>
                                                                    <td class="align-middle"><?php echo htmlspecialchars($expense['descrption']); ?></td>
                                                                    <td class="text-end align-middle"><?php echo number_format($expense['cout'], 0, ',', ' '); ?> F</td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                                <?php if (empty($expenses)): ?>
                                                                <tr>
                                                                    <td colspan="3" class="text-center">Aucune dépense enregistrée</td>
                                                                </tr>
                                                                <?php else: ?>
                                                                <tr class="secondary-bg text-white">
                                                                    <td colspan="2" class="text-end pe-3"><strong>Total des dépenses</strong></td>
                                                                    <td class="text-end"><strong><?php echo number_format($product['total_expenses'], 0, ',', ' '); ?> F</strong></td>
                                                                </tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Modal pour chaque produit -->
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

    <script>
        $(document).ready(function() {
            // Initialisation de DataTables
            var table = $('#productsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                order: [[0, 'desc']],
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tous"]],
                columnDefs: [
                    { orderable: true, targets: '_all' },
                    { searchable: false, targets: [0, 2, 3, 4, 5, 6, 7, 8] }
                ],
                drawCallback: function(settings) {
                    $('.collapse').collapse('hide');
                }
            });

            // Gérer les lignes de dépenses lors du tri et de la pagination
            table.on('draw', function() {
                $('.collapse').collapse('hide');
            });

            // Réinitialiser les lignes extensibles lors de la recherche
            $('.dataTables_filter input').on('keyup', function() {
                $('.collapse').collapse('hide');
            });
        });
    </script>
</body>

</html>