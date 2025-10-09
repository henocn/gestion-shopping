<?php
require_once '../vendor/autoload.php';

use Src\Connectdb;
use Src\ProductManager;

$db = new Connectdb();
$pdo = $db->getConnection();
$productManager = new ProductManager($pdo);

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="productsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Rendement</th>
                                        <th>Produit</th>
                                        <th>Qté vendue</th>
                                        <th>P.T. Achat</th>
                                        <th>P.T. Vente</th>
                                        <th>B.M/unité</th>
                                        <th>Bénéfice total</th>
                                        <th>Actions</th>
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
                                            <td class="<?php echo $profitClass; ?>">
                                                <?php echo number_format($product['avg_profit_per_unit'], 0, ',', ' '); ?> F
                                            </td>
                                            <td class="<?php echo $profitClass; ?>">
                                                <?php echo number_format($product['total_profit'], 0, ',', ' '); ?> F
                                            </td>
                                            <td>
                                                <button
                                                    class="btn main-bg text-white btn-sm add-expense"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#expenseModal"
                                                    data-product-id="<?php echo $product['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                    <i class="fas fa-plus-circle"></i> Dépense
                                                </button>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal pour ajouter une dépense -->
                <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="expenseModalLabel">Ajouter une dépense</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="expenseForm">
                                    <input type="hidden" id="productId" name="productId">
                                    <div class="mb-3">
                                        <label for="productName" class="form-label">Produit</label>
                                        <input type="text" class="form-control" id="productName" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="expenseAmount" class="form-label">Montant de la dépense</label>
                                        <input type="number" class="form-control" id="expenseAmount" name="expenseAmount" step="0.01" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="expenseDescription" class="form-label">Description</label>
                                        <textarea class="form-control" id="expenseDescription" name="expenseDescription" rows="3" required></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" id="saveExpense">Enregistrer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialisation de DataTables
            $('#productsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                },
                order: [
                    [1, 'desc']
                ], // Tri par défaut sur la quantité vendue
                responsive: true
            });

            // Gestion du modal pour ajouter une dépense
            $('.add-expense').on('click', function() {
                const productId = $(this).data('product-id');
                const productName = $(this).data('product-name');

                $('#productId').val(productId);
                $('#productName').val(productName);
            });

            // Gestion de la soumission du formulaire de dépense
            $('#saveExpense').on('click', function() {
                const formData = {
                    productId: $('#productId').val(),
                    amount: $('#expenseAmount').val(),
                    description: $('#expenseDescription').val()
                };

                if (!formData.amount || !formData.description) {
                    alert('Veuillez remplir tous les champs');
                    return;
                }

                // Envoi des données au serveur (à implémenter)
                $.ajax({
                    url: '../api/add_expense.php', // À créer
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        alert('Dépense enregistrée avec succès');
                        $('#expenseModal').modal('hide');
                        $('#expenseForm')[0].reset();
                        // Recharger la page ou mettre à jour le tableau si nécessaire
                        location.reload();
                    },
                    error: function() {
                        alert('Erreur lors de l\'enregistrement de la dépense');
                    }
                });
            });
        });
    </script>
</body>

</html>