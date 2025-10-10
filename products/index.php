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

        /* Style pour les notifications toast */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }
        .custom-toast {
            background-color: var(--main);
            color: white;
            padding: 15px 25px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .custom-toast.show {
            opacity: 1;
        }
        .custom-toast.success {
            background-color: #28a745;
        }
        .custom-toast.error {
            background-color: #dc3545;
        }

        /* Style pour le résumé statistique */
        .stats-container {
            background: linear-gradient(135deg, var(--main) 0%, var(--secondary) 100%);
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .stat-item {
            position: relative;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }
        .stat-item:hover {
            transform: translateY(-5px);
        }
        .stat-item h3 {
            font-size: 1rem;
            margin-bottom: 7px;
            color: rgba(255, 255, 255, 0.8);
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0 0 8px 8px;
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
                <!-- Container pour les notifications toast -->
                <div class="toast-container"></div>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Inventaire des produits</h1>
                </div>

                <!-- Résumé statistique -->
                <div class="stats-container mb-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="stat-item">
                                <h3><i class="fas fa-shopping-cart me-2"></i>Total Achats</h3>
                                <div class="stat-value" id="totalAchats">
                                    <?php echo number_format($productManager->getTotalPurchaseAmount(), 0, ',', ' '); ?> F
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <h3><i class="fas fa-file-invoice-dollar me-2"></i>Total Dépenses</h3>
                                <div class="stat-value" id="totalDepenses">
                                    <?php echo number_format($financeManager->getTotalExpensesByType('products'), 0, ',', ' '); ?> F
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-item">
                                <h3><i class="fas fa-cash-register me-2"></i>Total Ventes</h3>
                                <div class="stat-value" id="totalVentes">
                                    <?php echo number_format($productManager->getTotalSalesAmount(), 0, ',', ' '); ?> F
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

    <script>
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `custom-toast ${type}`;
            toast.textContent = message;
            toastContainer.appendChild(toast);

            // Animation d'apparition
            setTimeout(() => toast.classList.add('show'), 100);

            // Disparition après 5 secondes
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Fonction pour vérifier si on vient d'ajouter une dépense
        function checkForNewExpense() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === 'expense') {
                showToast('La dépense a été ajoutée avec succès !');
                // Nettoyer l'URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        $(document).ready(function() {
            // Vérifier si une dépense vient d'être ajoutée
            checkForNewExpense();
            // Initialisation du tableau principal
            $('#productsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tous"]],
                columnDefs: [
                    { orderable: true, targets: '_all' },
                    { searchable: false, targets: [0, 2, 3, 4, 5, 6, 7, 8] }
                ]
            });

            // Initialisation des tableaux de dépenses
            $('.expenses-table').each(function() {
                $(this).DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                    },
                    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                         "<'row'<'col-sm-12'tr>>" +
                         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    pageLength: 10,
                    ordering: true,
                    order: [[0, 'desc']],
                    columnDefs: [
                        { type: 'date', targets: 0 }
                    ]
                });
            });

            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('.expenses-table').DataTable().search('').draw();
            });
        });
    </script>
</body>

</html>