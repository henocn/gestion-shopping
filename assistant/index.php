<?php
require_once '../vendor/autoload.php';
require_once '../src/Connectdb.php';

use Src\Connectdb;
use Src\AnalyticsManager;

try {
    $database = new Connectdb();
    $pdo = $database->getConnection();
} catch (\PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$analyticsManager = new AnalyticsManager($pdo);

$period = $_GET['period'] ?? 'month';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

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
        default:
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
    }
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="container-fluid">
<div class="row">
<nav class="col-md-3 col-lg-2 sidebar px-3 py-4">
<div class="text-center mb-4">
<h4 class="text-white"><i class="fas fa-chart-line"></i> Gestion Shopping</h4>
<small class="text-light">Assistantes</small>
</div>
<div class="nav flex-column">
<a class="nav-link" href="../index.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a>
<a class="nav-link active" href="index.php"><i class="fas fa-users"></i> Assistantes</a>
<a class="nav-link" href="../finance/index.php"><i class="fas fa-coins"></i> Finance</a>
</div>
</nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
<div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2"><i class="fas fa-users"></i> Assistantes</h1>
<form method="get" class="d-flex gap-2">
<select name="period" class="form-select form-select-sm">
<option value="day" <?= $period == 'day' ? 'selected' : '' ?>>Aujourd'hui</option>
<option value="week" <?= $period == 'week' ? 'selected' : '' ?>>Cette semaine</option>
<option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Ce mois</option>
<option value="last_month" <?= $period == 'last_month' ? 'selected' : '' ?>>Mois dernier</option>
</select>
<button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
</form>
</div>
<div class="card dashboard-card mb-4">
<div class="card-header py-3">
<h6 class="m-0 font-weight-bold text-primary">Classement</h6>
</div>
<div class="card-body">
<?php if (!empty($assistantsRanking)): ?>
<table class="table table-bordered">
<thead>
<tr><th>#</th><th>Nom</th><th>Commandes</th><th>CA</th><th>Taux</th></tr>
</thead>
<tbody>
<?php $rank = 1; foreach ($assistantsRanking as $assistant): ?>
<tr>
<td><?= $rank ?></td>
<td><?= htmlspecialchars($assistant['name']) ?></td>
<td><?= (int)$assistant['total_orders'] ?></td>
<td><?= number_format((float)$assistant['total_revenue'], 0) ?> FCFA</td>
<td><?= number_format((float)($assistant['conversion_rate'] ?? 0), 1) ?>%</td>
</tr>
<?php $rank++; endforeach; ?>
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
