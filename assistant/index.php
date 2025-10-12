<?php
require_once '../vendor/autoload.php';
require_once '../src/Connectdb.php';

use Src\Connectdb;
use Src\AnalyticsManager;

$cnx = Connectdb::getConnection();

$analyticsManager = new AnalyticsManager($cnx);

$assistantes = $analyticsManager->getActiveAssistants();


?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Assistantes - Gestion Shopping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
                <?php include '../includes/sidebar.php'; ?>
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                    <h2 class="h2"><i class="fas fa-users"></i> Assistantes</h2>
                </div>
                <div class="card dashboard-card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Classement</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($assistantes)): ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Détails</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1;
                                    foreach ($assistantes as $assistant): ?>
                                        <tr>
                                            <td><?= $rank ?></td>
                                            <td><?= htmlspecialchars($assistant['name']) ?></td>
                                            <td><?= htmlspecialchars($assistant['email']) ?></td>
                                            <td><a href="details.php?id=<?= $assistant['id'] ?>" class="btn btn-info btn-sm">Voir</a></td>
                                        </tr>
                                    <?php $rank++;
                                    endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info">Aucune donnée.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>