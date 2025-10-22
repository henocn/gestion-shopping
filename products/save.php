<?php
session_start();
require_once '../vendor/autoload.php';

use src\Connectbd;
use src\FinanceManager;

$db = new Connectbd();
$pdo = $db->getConnection();
$financeManager = new FinanceManager($pdo);


if (!isset($_POST['valider'])) {
    header('Location: index.php?error=401');
    exit;
}

$action = $_POST['valider'];

switch ($action) {
    case 'Créer dépense':

        if (
            isset($_POST['product_id']) && !empty($_POST['product_id']) &&
            isset($_POST['cout']) && !empty($_POST['cout']) &&
            isset($_POST['description'])
        ) {
            // Nettoyage des données
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
            $amount = filter_input(INPUT_POST, 'cout', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $description = filter_input(INPUT_POST, 'description');

            try {
                if ($financeManager->recordProductExpense($productId, $amount,$description)) {
                    $_SESSION['success'] = "La dépense a été enregistrée avec succès";
                    header('Location: index.php?success=expense');
                } else {
                    $_SESSION['error'] = "Erreur lors de l'enregistrement de la dépense";
                    header('Location: index.php');
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Erreur : " . $e->getMessage();
                header('Location: index.php');
            }
        } else {
            $_SESSION['error'] = "Tous les champs sont obligatoires";
            header('Location: index.php');
        }
        break;

    default:
        $_SESSION['error'] = "Action non reconnue";
        break;
}
