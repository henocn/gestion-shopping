<?php
require_once '../vendor/autoload.php';

use Src\Connectdb;

header('Content-Type: application/json');

try {
    // Vérification des données requises
    $requiredFields = ['product_id', 'type', 'cout', 'description'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        throw new Exception('Champs manquants : ' . implode(', ', $missingFields));
    }

    // Récupération et nettoyage des données
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    $cout = filter_input(INPUT_POST, 'cout', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $date = date('Y-m-d H:i:s'); // Date actuelle

    // Validation supplémentaire
    if (!$product_id || !$cout) {
        throw new Exception('Données invalides');
    }

    // Types de dépenses autorisés
    $allowedTypes = ['transport', 'stockage', 'marketing', 'autre'];
    if (!in_array($type, $allowedTypes)) {
        throw new Exception('Type de dépense non valide');
    }

    // Connexion à la base de données
    $db = new Connectdb();
    $pdo = $db->getConnection();

    // Préparation et exécution de la requête
    $sql = "INSERT INTO expenses (product_id, type, cout, description, date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id, $type, $cout, $description, $date]);

    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Dépense enregistrée avec succès'
    ]);

} catch (Exception $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
