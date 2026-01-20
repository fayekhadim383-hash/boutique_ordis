<?php
// Utiliser le chemin absolu
$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/includes/config.php';
require_once $root_path . '/includes/functions.php';

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Veuillez vous connecter pour accéder à l'administration";
    $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
    header('Location: ../../pages/connexion.php');
    exit();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Accès réservé aux administrateurs";
    header('Location: ../../index.php');
    exit();
}

// Vérifier le token CSRF pour les actions sensibles
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Token de sécurité invalide";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>