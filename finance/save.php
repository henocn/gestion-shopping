<?php
session_start();
require_once '../vendor/autoload.php';


use Src\Connectdb;
use Src\FinanceManager;

$cnx = Connectdb::getConnection();

$financeManager = new FinanceManager($cnx);

if (!isset($_POST['action'])) {
    $_SESSION['error'] = 'Action non définie';
    header('Location: index.php');
    exit;
}

$action = $_POST['action'];

switch ($action) {
    case 'create_expense':
        if (
            isset($_POST['expense_type']) && !empty($_POST['expense_type']) &&
            isset($_POST['expense_amount']) && !empty($_POST['expense_amount'])
        ) {
            $type = filter_input(INPUT_POST, 'expense_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $amount = filter_input(INPUT_POST, 'expense_amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $description = filter_input(INPUT_POST, 'expense_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
            $managerId = filter_input(INPUT_POST, 'manager_id', FILTER_SANITIZE_NUMBER_INT);
            $date = filter_input(INPUT_POST, 'expense_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            // Convertir la date si fournie
            if ($date) {
                $date = date('Y-m-d H:i:s', strtotime($date));
            }

            // Valider le montant
            if ($amount <= 0) {
                $_SESSION['error'] = "Le montant doit être supérieur à zéro";
                header('Location: index.php');
                exit;
            }

            try {
                if ($financeManager->createExpense($type, $amount, $description, $productId ?: null, $managerId ?: null, $date)) {
                    $_SESSION['success'] = "La dépense a été enregistrée avec succès";
                } else {
                    $_SESSION['error'] = "Erreur lors de l'enregistrement de la dépense";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Erreur : " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Le type et le montant sont obligatoires";
        }
        header('Location: index.php');
        exit;

    default:
        $_SESSION['error'] = "Action non reconnue";
        header('Location: index.php');
        exit;
}
