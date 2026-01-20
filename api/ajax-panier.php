<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier que c'est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    die('Accès non autorisé');
}

session_start();

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'cart_count' => 0,
    'cart_total' => 0
];

try {
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Initialiser le panier s'il n'existe pas
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
    
    switch ($action) {
        case 'add':
            if ($product_id <= 0) {
                throw new Exception('Produit non valide');
            }
            
            // Vérifier si le produit existe et est en stock
            $product = getProductById($conn, $product_id);
            if (!$product) {
                throw new Exception('Produit non trouvé');
            }
            
            if ($product['quantite'] <= 0) {
                throw new Exception('Produit en rupture de stock');
            }
            
            // Vérifier la quantité demandée
            $current_quantity = isset($_SESSION['panier'][$product_id]) ? $_SESSION['panier'][$product_id] : 0;
            $requested_quantity = $current_quantity + $quantity;
            
            if ($requested_quantity > $product['quantite']) {
                throw new Exception('Quantité demandée non disponible. Stock restant: ' . $product['quantite']);
            }
            
            // Ajouter au panier
            $_SESSION['panier'][$product_id] = $requested_quantity;
            
            $response['success'] = true;
            $response['message'] = $product['nom'] . ' ajouté au panier';
            $response['product_name'] = $product['nom'];
            break;
            
        case 'update':
            if ($product_id <= 0) {
                throw new Exception('Produit non valide');
            }
            
            if ($quantity <= 0) {
                // Supprimer du panier si quantité <= 0
                unset($_SESSION['panier'][$product_id]);
                $response['message'] = 'Produit retiré du panier';
            } else {
                // Vérifier le stock
                $product = getProductById($conn, $product_id);
                if (!$product) {
                    throw new Exception('Produit non trouvé');
                }
                
                if ($quantity > $product['quantite']) {
                    throw new Exception('Stock insuffisant. Maximum: ' . $product['quantite']);
                }
                
                $_SESSION['panier'][$product_id] = $quantity;
                $response['message'] = 'Quantité mise à jour';
            }
            
            $response['success'] = true;
            break;
            
        case 'remove':
            if ($product_id <= 0) {
                throw new Exception('Produit non valide');
            }
            
            if (isset($_SESSION['panier'][$product_id])) {
                unset($_SESSION['panier'][$product_id]);
                $response['success'] = true;
                $response['message'] = 'Produit retiré du panier';
            } else {
                throw new Exception('Produit non présent dans le panier');
            }
            break;
            
        case 'clear':
            $_SESSION['panier'] = [];
            $response['success'] = true;
            $response['message'] = 'Panier vidé';
            break;
            
        case 'get':
            $response['success'] = true;
            $response['cart_items'] = [];
            
            if (!empty($_SESSION['panier'])) {
                foreach ($_SESSION['panier'] as $pid => $qty) {
                    $product = getProductById($conn, $pid);
                    if ($product) {
                        $response['cart_items'][] = [
                            'id' => $product['id'],
                            'name' => $product['nom'],
                            'price' => $product['prix'],
                            'quantity' => $qty,
                            'image' => $product['image_url'],
                            'stock' => $product['quantite'],
                            'subtotal' => $product['prix'] * $qty
                        ];
                    }
                }
            }
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
    // Mettre à jour les statistiques du panier
    $cart_count = 0;
    $cart_total = 0;
    
    if (!empty($_SESSION['panier'])) {
        $cart_count = array_sum($_SESSION['panier']);
        
        foreach ($_SESSION['panier'] as $pid => $qty) {
            $product = getProductById($conn, $pid);
            if ($product) {
                $cart_total += $product['prix'] * $qty;
            }
        }
    }
    
    $response['cart_count'] = $cart_count;
    $response['cart_total'] = $cart_total;
    $response['cart_total_formatted'] = formatPrice($cart_total);
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;