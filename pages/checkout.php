<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Paiement - " . SITE_NAME;

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error'] = "Veuillez vous connecter pour passer commande";
    $_SESSION['redirect'] = 'checkout.php';
    redirect('connexion.php');
}

// Vérifier si le panier n'est pas vide
if (empty($_SESSION['panier'])) {
    $_SESSION['error'] = "Votre panier est vide";
    redirect('panier.php');
}

// Vérifier le stock
$stock_ok = true;
foreach ($_SESSION['panier'] as $product_id => $quantity) {
    if (!checkStock($conn, $product_id, $quantity)) {
        $stock_ok = false;
        break;
    }
}

if (!$stock_ok) {
    $_SESSION['error'] = "Certains produits ne sont plus disponibles en quantité suffisante";
    redirect('panier.php');
}

// Récupérer les informations utilisateur
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Calculer le total
$subtotal = calculateCartTotal($conn);
$tva = $subtotal * TAX_RATE;
$total = $subtotal + $tva;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = sanitize($_POST['payment_method']);
    $shipping_address = sanitize($_POST['shipping_address']);
    $phone = sanitize($_POST['phone']);
    $notes = sanitize($_POST['notes']);
    
    // Valider les données
    $errors = [];
    
    if (empty($shipping_address)) {
        $errors[] = "L'adresse de livraison est requise";
    }
    
    if (empty($phone)) {
        $errors[] = "Le numéro de téléphone est requis";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Veuillez sélectionner une méthode de paiement";
    }
    
    if (empty($errors)) {
        // Mettre à jour les informations utilisateur si modifiées
        if ($phone != $user['telephone'] || $shipping_address != $user['adresse']) {
            $update_stmt = $conn->prepare("UPDATE utilisateurs SET telephone = ?, adresse = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $phone, $shipping_address, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        // Créer la commande
        $order = createOrder($conn, $user_id, $total, $payment_method, $shipping_address);
        addOrderItems($conn, $order['order_id'], $_SESSION['panier']);
        
        // Envoyer un email de confirmation
        $user_email = $user['email'];
        $order_number = $order['order_number'];
        
        // Préparer les détails de la commande pour l'email
        $order_details = "";
        foreach ($_SESSION['panier'] as $product_id => $quantity) {
            $product = getProductById($conn, $product_id);
            $order_details .= "- " . $product['nom'] . " x" . $quantity . " = " . formatPrice($product['prix'] * $quantity) . "\n";
        }
        
        // Envoyer l'email (en production, utiliser une librairie comme PHPMailer)
        sendOrderConfirmation($user_email, $order_number, $total, $order_details);
        
        // Vider le panier
        unset($_SESSION['panier']);
        
        // Rediriger vers la page de confirmation
        redirect('confirmation.php?id=' . $order['order_id']);
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-truck"></i> Informations de livraison</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom complet *</label>
                            <input type="text" class="form-control" id="nom" value="<?php echo $user['nom']; ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : $user['telephone']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="shipping_address" class="form-label">Adresse de livraison *</label>
                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php 
                            echo isset($_POST['shipping_address']) ? htmlspecialchars($_POST['shipping_address']) : $user['adresse'];
                        ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes de commande (optionnel)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?php 
                            echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '';
                        ?></textarea>
                        <small class="text-muted">Instructions spéciales pour la livraison</small>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="save_info" name="save_info" checked>
                        <label class="form-check-label" for="save_info">
                            Enregistrer ces informations pour mes prochaines commandes
                        </label>
                    </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-credit-card"></i> Méthode de paiement</h4>
            </div>
            <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="card" value="carte_visa" required>
                            <label class="form-check-label" for="card">
                                <i class="fab fa-cc-visa fa-lg text-primary"></i>
                                <i class="fab fa-cc-mastercard fa-lg text-warning"></i> 
                                Carte bancaire
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="wave" value="wave">
                            <label class="form-check-label" for="wave">
                                <i class="fas fa-mobile-alt fa-lg text-success"></i> Wave
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="orange" value="orange_money">
                            <label class="form-check-label" for="orange">
                                <i class="fas fa-mobile-alt fa-lg text-warning"></i> Orange Money
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                            <label class="form-check-label" for="paypal">
                                <i class="fab fa-paypal fa-lg text-primary"></i> PayPal
                            </label>
                        </div>
                    </div>
                    
                    <!-- Section pour les informations de carte (masquée par défaut) -->
                    <div id="card-details" class="border p-3 rounded mb-3" style="display: none;">
                        <h6>Informations de la carte</h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="card_number" class="form-label">Numéro de carte</label>
                                <input type="text" class="form-control" id="card_number" placeholder="1234 5678 9012 3456">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="card_expiry" class="form-label">Date d'expiration (MM/AA)</label>
                                <input type="text" class="form-control" id="card_expiry" placeholder="12/25">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="card_cvc" class="form-label">CVC</label>
                                <input type="text" class="form-control" id="card_cvc" placeholder="123">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            J'accepte les <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">conditions générales de vente</a> *
                        </label>
                    </div>
                </div>
            </div>
    </div>
    
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-shopping-bag"></i> Votre commande</h5>
            </div>
            <div class="card-body">
                <div class="order-summary">
                    <?php 
                    $cart_items = [];
                    if (!empty($_SESSION['panier'])) {
                        foreach ($_SESSION['panier'] as $product_id => $quantity) {
                            $product = getProductById($conn, $product_id);
                            if ($product) {
                                $cart_items[] = [
                                    'nom' => $product['nom'],
                                    'prix' => $product['prix'],
                                    'quantite' => $quantity
                                ];
                            }
                        }
                    }
                    ?>
                    
                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <span class="fw-bold"><?php echo $item['nom']; ?></span>
                            <br>
                            <small class="text-muted">Quantité: <?php echo $item['quantite']; ?></small>
                        </div>
                        <div class="text-end">
                            <div><?php echo formatPrice($item['prix'] * $item['quantite']); ?></div>
                            <small class="text-muted"><?php echo formatPrice($item['prix']); ?> l'unité</small>
                        </div>
                    </div>
                    <hr class="my-2">
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-between mb-1">
                        <span>Sous-total</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>TVA (<?php echo (TAX_RATE * 100); ?>%)</span>
                        <span><?php echo formatPrice($tva); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Livraison</span>
                        <span class="text-success">Gratuite</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total</span>
                        <span class="text-primary"><?php echo formatPrice($total); ?></span>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-lock"></i> Confirmer et payer
                        </button>
                    </div>
                </div>
                </form>
                
                <div class="mt-4">
                    <h6><i class="fas fa-shield-alt"></i> Paiement sécurisé</h6>
                    <p class="small text-muted">
                        Vos informations de paiement sont cryptées et sécurisées. Nous ne stockons jamais vos coordonnées bancaires.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Conditions générales -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Conditions générales de vente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Objet</h6>
                <p>Les présentes conditions régissent les ventes par PC Pro.</p>
                
                <h6>2. Prix</h6>
                <p>Les prix sont indiqués en FCFA toutes taxes comprises.</p>
                
                <h6>3. Commandes</h6>
                <p>Les commandes sont confirmées par email après paiement.</p>
                
                <h6>4. Livraison</h6>
                <p>La livraison est effectuée sous 2-5 jours ouvrables.</p>
                
                <h6>5. Retours</h6>
                <p>Les retours sont acceptés sous 14 jours après réception.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Afficher/masquer les détails de la carte
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const cardDetails = document.getElementById('card-details');
        cardDetails.style.display = this.id === 'card' ? 'block' : 'none';
    });
});

// Formatage du numéro de carte
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    let formatted = value.match(/.{1,4}/g)?.join(' ') || '';
    e.target.value = formatted;
});

// Formatage de la date d'expiration
document.getElementById('card_expiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});
</script>

<?php include '../includes/footer.php'; ?>