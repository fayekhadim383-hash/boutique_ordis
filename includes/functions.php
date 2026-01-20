<?php
require_once 'config.php';

/**
 * Récupère tous les produits
 */
function getAllProducts($conn, $limit = null, $categorie_id = null, $marque = null) {
    $sql = "SELECT p.*, c.nom as categorie_nom FROM produits p 
            LEFT JOIN categories c ON p.categorie_id = c.id 
            WHERE p.quantite > 0";
    
    $params = [];
    $types = "";
    
    if ($categorie_id) {
        $sql .= " AND p.categorie_id = ?";
        $params[] = $categorie_id;
        $types .= "i";
    }
    
    if ($marque) {
        $sql .= " AND p.marque = ?";
        $params[] = $marque;
        $types .= "s";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $products;
}

/**
 * Récupère un produit par ID
 */
function getProductById($conn, $id) {
    $sql = "SELECT p.*, c.nom as categorie_nom FROM produits p 
            LEFT JOIN categories c ON p.categorie_id = c.id 
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    return $product;
}

/**
 * Récupère les catégories
 */
function getCategories($conn) {
    $sql = "SELECT * FROM categories ORDER BY nom";
    $result = $conn->query($sql);
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    return $categories;
}

/**
 * Formatage du prix
 */
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' ' . CURRENCY;
}

/**
 * Calculer le total du panier
 */
function calculateCartTotal($conn) {
    $total = 0;
    
    if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
        foreach ($_SESSION['panier'] as $product_id => $quantity) {
            $product = getProductById($conn, $product_id);
            if ($product) {
                $total += $product['prix'] * $quantity;
            }
        }
    }
    
    return $total;
}

/**
 * Vérifier le stock
 */
function checkStock($conn, $product_id, $quantity) {
    $sql = "SELECT quantite FROM produits WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    return $product['quantite'] >= $quantity;
}

/**
 * Créer une commande
 */
function createOrder($conn, $user_id, $total, $payment_method, $shipping_address) {
    $order_number = 'CMD-' . date('Ymd') . '-' . strtoupper(uniqid());
    
    $sql = "INSERT INTO commandes (utilisateur_id, numero_commande, total, methode_paiement, adresse_livraison, statut) 
            VALUES (?, ?, ?, ?, ?, 'en_attente')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $order_number, $total, $payment_method, $shipping_address);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();
    
    return ['order_id' => $order_id, 'order_number' => $order_number];
}

/**
 * Ajouter des produits à la commande
 */
function addOrderItems($conn, $order_id, $cart_items) {
    foreach ($cart_items as $product_id => $quantity) {
        $product = getProductById($conn, $product_id);
        
        $sql = "INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $product['prix']);
        $stmt->execute();
        $stmt->close();
        
        // Mettre à jour le stock
        updateStock($conn, $product_id, $quantity);
    }
}

/**
 * Mettre à jour le stock
 */
function updateStock($conn, $product_id, $quantity_sold) {
    $sql = "UPDATE produits SET quantite = quantite - ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quantity_sold, $product_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Envoyer un email de confirmation
 */
function sendOrderConfirmation($to_email, $order_number, $total, $order_details) {
    $subject = "Confirmation de commande #" . $order_number;
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; }
            .footer { text-align: center; margin-top: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Confirmation de commande</h1>
            </div>
            <div class='content'>
                <p>Merci pour votre commande !</p>
                <p><strong>Numéro de commande :</strong> $order_number</p>
                <p><strong>Total :</strong> " . formatPrice($total) . "</p>
                <p>Nous traiterons votre commande dans les plus brefs délais.</p>
            </div>
            <div class='footer'>
                <p>PC Pro - Boutique d'Ordinateurs</p>
                <p>© " . date('Y') . " Tous droits réservés</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_EMAIL . "\r\n";
    
    return mail($to_email, $subject, $message, $headers);
}

/**
 * Générer un token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valider le token CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


?>