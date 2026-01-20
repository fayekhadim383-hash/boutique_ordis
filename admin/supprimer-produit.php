<?php
require_once 'includes/admin-auth.php';

// Vérifier si l'ID est présent
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['error'] = "ID de produit non spécifié";
    header('Location: produits.php');
    exit();
}

$product_id = intval($_POST['id']);

// Récupérer les informations du produit
$stmt = $conn->prepare("SELECT nom, image_url, image_url2, image_url3 FROM produits WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['error'] = "Produit non trouvé";
    header('Location: produits.php');
    exit();
}

// Vérifier si le produit est dans des commandes
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM commande_details WHERE produit_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$order_count = $result->fetch_assoc()['count'];
$stmt->close();

if ($order_count > 0) {
    // Ne pas supprimer, désactiver seulement
    $stmt = $conn->prepare("UPDATE produits SET est_actif = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        // Journaliser l'action
        $log_stmt = $conn->prepare("
            INSERT INTO logs (utilisateur_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $action = "desactivation_produit";
        $details = "Désactivation du produit #$product_id: " . $product['nom'] . " (présent dans $order_count commandes)";
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu';
        
        $log_stmt->bind_param("issss", $_SESSION['user_id'], $action, $details, $ip_address, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();
        
        $_SESSION['warning'] = "Le produit a été désactivé car il est présent dans $order_count commande(s).";
    } else {
        $_SESSION['error'] = "Erreur lors de la désactivation du produit";
    }
    $stmt->close();
} else {
    // Supprimer les images
    $images = [$product['image_url'], $product['image_url2'], $product['image_url3']];
    foreach ($images as $image) {
        if ($image && file_exists('../../' . $image) && $image !== 'assets/images/produits/default.jpg') {
            unlink('../../' . $image);
        }
    }
    
    // Supprimer le produit
    $stmt = $conn->prepare("DELETE FROM produits WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        // Journaliser l'action
        $log_stmt = $conn->prepare("
            INSERT INTO logs (utilisateur_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $action = "suppression_produit";
        $details = "Suppression du produit #$product_id: " . $product['nom'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu';
        
        $log_stmt->bind_param("issss", $_SESSION['user_id'], $action, $details, $ip_address, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();
        
        $_SESSION['success'] = "Produit supprimé avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression du produit";
    }
    $stmt->close();
}

header('Location: produits.php');
exit();