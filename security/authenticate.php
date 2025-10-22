<?php
session_start();
require_once '../vendor/autoload.php';

use src\Connectbd;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Veuillez remplir tous les champs';
    header('Location: login.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email invalide';
    header('Location: login.php');
    exit();
}

try {
    $database = new Connectbd();
    $pdo = $database->getConnection();
    
    // Rechercher l'utilisateur par email
    $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'utilisateur existe et si le mot de passe correspond
    if ($user && password_verify($password, $user['password'])) {
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $_SESSION['logged_in'] = true;
        
        // Se souvenir de moi (cookie pour 30 jours)
        if ($remember) {
            setcookie('remember_user', $user['id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
        }
        
        // Rediriger vers le dashboard
        header('Location: ../index.php');
        exit();
    } else {
        // Échec de la connexion
        $_SESSION['error'] = 'Email ou mot de passe incorrect';
        header('Location: login.php');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    $_SESSION['error'] = 'Erreur de connexion à la base de données';
    header('Location: login.php');
    exit();
}
