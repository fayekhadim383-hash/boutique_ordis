<?php
// Configuration admin spécifique

// Démarrer la session si pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Chemin vers la configuration principale
$root_path = dirname(dirname(dirname(__FILE__)));
require_once $root_path . '/includes/config.php';

// Fonctions spécifiques à l'admin
function requireAdmin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Veuillez vous connecter";
        header('Location: ../../pages/connexion.php');
        exit();
    }
    
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['error'] = "Accès non autorisé";
        header('Location: ../../index.php');
        exit();
    }
}

// Fonction pour sécuriser l'output
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>