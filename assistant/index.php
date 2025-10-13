<?php
require_once '../vendor/autoload.php';
require_once '../src/Connectdb.php';

use Src\Connectdb;
use Src\AnalyticsManager;

$cnx = Connectdb::getConnection();

$analyticsManager = new AnalyticsManager($cnx);

$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

if ($dateFrom && !strpos($dateFrom, ':')) {
    $dateFrom .= ' 00:00:00';
}
if ($dateTo && !strpos($dateTo, ':')) {
    $dateTo .= ' 23:59:59';
}

$assistantsRanking = $analyticsManager->getAssistantsRanking($dateFrom, $dateTo);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Assistantes - Gestion Shopping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-users"></i> Assistantes</h1>
                    <form method="get" class="d-flex gap-2 align-items-center">
                        <input type="date" name="date_from" class="form-control form-control-md" style="border-bottom: 2px solid var(--main);"
                            value="<?= $dateFrom ? date('Y-m-d', strtotime($dateFrom)) : '' ?>"
                            placeholder="Date début"
                            style="width: 150px;">
                        <input type="date" name="date_to" class="form-control form-control-md" style="border-bottom: 2px solid var(--main);"
                            value="<?= $dateTo ? date('Y-m-d', strtotime($dateTo)) : '' ?>"
                            placeholder="Date fin"
                            style="width: 150px;">
                        <button type="submit" class="btn main-bg btn-sm">
                            <i class="fas fa-search btn-primary"></i>
                        </button>
                        <?php if ($dateFrom || $dateTo): ?>
                            <a href="index.php" class="btn btn-warning btn-sm" title="Réinitialiser">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Indication de période -->
                <?php if ($dateFrom && $dateTo): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-calendar-alt"></i> Classement pour la période : du <?= date('d/m/Y', strtotime($dateFrom)) ?> au <?= date('d/m/Y', strtotime($dateTo)) ?>
                    </div>
                <?php else: ?>
                    <p></p>
                <?php endif; ?>

                <div class="card dashboard-card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Classement</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($assistantsRanking)): ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nom</th>
                                        <th>Commandes</th>
                                        <th>CA</th>
                                        <th>Taux</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1;
                                    foreach ($assistantsRanking as $assistant): ?>
                                        <tr>
                                            <td><?= $rank ?></td>
                                            <td><?= htmlspecialchars($assistant['name']) ?></td>
                                            <td><?= (int)$assistant['total_orders'] ?></td>
                                            <td><?= number_format((float)$assistant['total_revenue'], 0) ?> FCFA</td>
                                            <td><?= number_format((float)($assistant['conversion_rate'] ?? 0), 1) ?>%</td>
                                            <td>
                                                <a href="details.php?id=<?= $assistant['id'] ?>" class="btn main-bg btn-sm" style="font-color: white;">
                                                    <i class="fas fa-eye"></i> Voir détails
                                                </a>
                                            </td>
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