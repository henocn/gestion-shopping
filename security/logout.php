<?php
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 42000, '/', '', false, true);
}

session_destroy();

session_start();
$_SESSION['success'] = 'Déconnexion réussie';

header('Location: login.php');
exit();
