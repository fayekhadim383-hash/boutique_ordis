<?php
require_once 'includes/admin-header.php';

// Vérifier si l'ID est présent
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['error'] = "ID de catégorie non spécifié";
    header('Location: categories.php');
    exit();
}

$category_id = intval($_POST['id']);

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF invalide";
    header('Location: categories.php');
    exit();
}

// Vérifier si la catégorie existe
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

if (!$category) {
    $_SESSION['error'] = "Catégorie non trouvée";
    header('Location: categories.php');
    exit();
}

// Vérifier si la catégorie contient des produits
$check_products = $conn->prepare("SELECT COUNT(*) as nb_produits FROM produits WHERE categorie_id = ?");
$check_products->bind_param("i", $category_id);
$check_products->execute();
$check_products->bind_result($nb_produits);
$check_products->fetch();
$check_products->close();

if ($nb_produits > 0) {
    $_SESSION['error'] = "Impossible de supprimer cette catégorie car elle contient $nb_produits produit(s). Veuillez d'abord supprimer ou déplacer les produits.";
    header('Location: categories.php');
    exit();
}

// Supprimer l'image associée si elle existe
if (!empty($category['image_url']) && file_exists('../../' . $category['image_url'])) {
    unlink('../../' . $category['image_url']);
}

// Supprimer la catégorie de la base de données
$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);

if ($stmt->execute()) {
    // Journaliser l'action
    if (isset($_SESSION['user_id'])) {
        $log_stmt = $conn->prepare("
            INSERT INTO logs (utilisateur_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $action = "suppression_categorie";
        $details = "Suppression de la catégorie #$category_id: " . $category['nom'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu';
        
        $log_stmt->bind_param("issss", $_SESSION['user_id'], $action, $details, $ip_address, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    $_SESSION['success'] = "Catégorie supprimée avec succès !";
} else {
    $_SESSION['error'] = "Erreur lors de la suppression de la catégorie : " . $stmt->error;
}

$stmt->close();
header('Location: categories.php');
exit();
?>