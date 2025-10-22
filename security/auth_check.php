<?php
// Fichier à inclure en haut de chaque page protégée
// Usage: require_once 'security/auth_check.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    $_SESSION['error'] = 'Vous devez être connecté pour accéder à cette page';
    
    header('Location: security/login.php');
    exit();
}

// Vérifier que le rôle est (admin)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    $_SESSION['error'] = 'Accès refusé. Vous n\'avez pas les permissions nécessaires.';
    header('Location: ../security/logout.php');
    exit();
}

// Vérifier que la session n'a pas expiré (optionnel, 2 heures par défaut)
$session_timeout = 2 * 60 * 60;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session expirée
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['error'] = 'Votre session a expiré. Veuillez vous reconnecter.';
    header('Location: login.php');
    exit();
}

// Mettre à jour le timestamp de dernière activité
$_SESSION['last_activity'] = time();
