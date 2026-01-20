<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['warning'] = "Vous n'êtes pas connecté";
    redirect(SITE_URL);
}

// Récupérer le nom de l'utilisateur avant déconnexion pour le message
$user_name = $_SESSION['user_nom'] ?? 'Utilisateur';

// Détruire toutes les variables de session
$_SESSION = array();

// Si vous voulez détruire complètement la session, supprimez également
// le cookie de session.
// Note : Cela détruira la session et pas seulement les données de session !
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalement, détruire la session
session_destroy();

// Message de confirmation
$_SESSION['success'] = "À bientôt $user_name ! Vous avez été déconnecté avec succès.";

// Rediriger vers la page d'accueil
redirect(SITE_URL);