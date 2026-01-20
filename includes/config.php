<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'boutique_ordis');

// Configuration du site
define('SITE_NAME', 'Elite Informatique - Boutique d\'Ordinateurs');
define('SITE_URL', 'http://localhost/boutique-ordinateurs/');
define('SITE_EMAIL', 'contact@pcpro.com');


// Chemins
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/produits/');
define('UPLOAD_URL', SITE_URL . 'uploads/produits/');

// Configuration des paiements (À configurer avec vos clés)
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID');
define('PAYPAL_SECRET', 'YOUR_PAYPAL_SECRET');
define('PAYPAL_ENVIRONMENT', 'sandbox'); // sandbox ou live

define('STRIPE_PUBLIC_KEY', 'pk_test_YOUR_STRIPE_KEY');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_STRIPE_SECRET');

// Configuration Wave et Orange Money (À configurer)
define('WAVE_API_KEY', 'YOUR_WAVE_API_KEY');
define('ORANGE_MONEY_MERCHANT', 'YOUR_ORANGE_MERCHANT_CODE');

// Paramètres du site
define('ITEMS_PER_PAGE', 12);
define('CURRENCY', 'FCFA');
define('TAX_RATE', 0.18); // 18% de TVA

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Échec de la connexion à la base de données: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Définir le fuseau horaire
    date_default_timezone_set('Africa/Dakar');
    
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}

// Fonction pour sécuriser les données
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

// Fonction pour rediriger
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}
?>