<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Panier - " . SITE_NAME;

// Initialiser le panier s'il n'existe pas
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Traitement des actions sur le panier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $quantity = intval($quantity);
            if ($quantity > 0) {
                $_SESSION['panier'][$product_id] = $quantity;
            } else {
                unset($_SESSION['panier'][$product_id]);
            }
        }
        $_SESSION['success'] = "Panier mis à jour";
    } elseif (isset($_POST['clear_cart'])) {
        $_SESSION['panier'] = [];
        $_SESSION['success'] = "Panier vidé";
    } elseif (isset($_POST['remove_item'])) {
        $product_id = intval($_POST['product_id']);
        unset($_SESSION['panier'][$product_id]);
        $_SESSION['success'] = "Produit retiré du panier";
    }
    redirect('panier.php');
}

// Récupérer les informations des produits dans le panier
$cart_items = [];
$total = 0;
$subtotal = 0;

if (!empty($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $product_id => $quantity) {
        $product = getProductById($conn, $product_id);
        if ($product) {
            $item_total = $product['prix'] * $quantity;
            $subtotal += $item_total;
            
            $cart_items[] = [
                'id' => $product['id'],
                'nom' => $product['nom'],
                'image' => $product['image_url'],
                'prix' => $product['prix'],
                'quantite' => $quantity,
                'stock' => $product['quantite'],
                'total' => $item_total
            ];
        }
    }
    
    // Calculer la TVA et le total
    $tva = $subtotal * TAX_RATE;
    $total = $subtotal + $tva;
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-shopping-cart"></i> Mon panier</h4>
            </div>
            <div class="card-body">
                <?php if (empty($cart_items)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                        <h4>Votre panier est vide</h4>
                        <p class="text-muted">Ajoutez des produits à votre panier pour commencer vos achats</p>
                        <a href="produits.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-laptop"></i> Voir les produits
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="10%">Image</th>
                                        <th width="35%">Produit</th>
                                        <th width="15%">Prix</th>
                                        <th width="20%">Quantité</th>
                                        <th width="15%">Total</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo SITE_URL . $item['image']; ?>" 
                                                 alt="<?php echo $item['nom']; ?>" 
                                                 class="img-thumbnail" 
                                                 style="width: 60px; height: 60px; object-fit: contain;">
                                        </td>
                                        <td>
                                            <a href="produit-details.php?id=<?php echo $item['id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo $item['nom']; ?>
                                            </a>
                                            <?php if ($item['quantite'] > $item['stock']): ?>
                                                <div class="text-danger small">
                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                    Stock insuffisant (<?php echo $item['stock']; ?> disponibles)
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatPrice($item['prix']); ?></td>
                                        <td>
                                            <div class="input-group" style="width: 120px;">
                                                <input type="number" 
                                                       name="quantities[<?php echo $item['id']; ?>]" 
                                                       value="<?php echo $item['quantite']; ?>" 
                                                       min="1" 
                                                       max="<?php echo $item['stock']; ?>" 
                                                       class="form-control">
                                            </div>
                                        </td>
                                        <td class="fw-bold"><?php echo formatPrice($item['total']); ?></td>
                                        <td>
                                            <button type="submit" 
                                                    name="remove_item" 
                                                    value="1"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="this.form.product_id.value=<?php echo $item['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <input type="hidden" name="product_id" value="">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-3">
                            <a href="produits.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Continuer les achats
                            </a>
                            <div>
                                <button type="submit" name="clear_cart" class="btn btn-outline-danger me-2">
                                    <i class="fas fa-trash"></i> Vider le panier
                                </button>
                                <button type="submit" name="update_cart" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Mettre à jour
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recommandations -->
        <?php if (!empty($cart_items)): ?>
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Vous aimerez aussi</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $recommended = getAllProducts($conn, 3);
                    foreach ($recommended as $product): 
                        if (isset($_SESSION['panier'][$product['id']])) continue;
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <img src="<?php echo SITE_URL . $product['image_url']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo $product['nom']; ?>"
                                 style="height: 120px; object-fit: contain;">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo $product['nom']; ?></h6>
                                <p class="card-text text-primary fw-bold"><?php echo formatPrice($product['prix']); ?></p>
                                <button class="btn btn-sm btn-primary add-to-cart" 
                                        data-id="<?php echo $product['id']; ?>"
                                        data-name="<?php echo $product['nom']; ?>">
                                    <i class="fas fa-cart-plus"></i> Ajouter
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-receipt"></i> Récapitulatif</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cart_items)): ?>
                    <p class="text-muted">Ajoutez des produits pour voir le récapitulatif</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <tr>
                            <td>Sous-total</td>
                            <td class="text-end"><?php echo formatPrice($subtotal); ?></td>
                        </tr>
                        <tr>
                            <td>TVA (<?php echo (TAX_RATE * 100); ?>%)</td>
                            <td class="text-end"><?php echo formatPrice($tva); ?></td>
                        </tr>
                        <tr>
                            <td>Livraison</td>
                            <td class="text-end">
                                <small class="text-success">Gratuite</small>
                            </td>
                        </tr>
                        <tr class="table-active fw-bold">
                            <td>Total</td>
                            <td class="text-end"><?php echo formatPrice($total); ?></td>
                        </tr>
                    </table>
                    
                    <!-- Vérifier le stock -->
                    <?php 
                    $stock_ok = true;
                    foreach ($cart_items as $item) {
                        if ($item['quantite'] > $item['stock']) {
                            $stock_ok = false;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if (!$stock_ok): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Certains produits ont un stock insuffisant. Veuillez ajuster les quantités.
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2">
                        <?php if (isLoggedIn()): ?>
                            <a href="checkout.php" 
                               class="btn btn-success btn-lg <?php echo !$stock_ok ? 'disabled' : ''; ?>">
                                <i class="fas fa-lock"></i> Procéder au paiement
                            </a>
                        <?php else: ?>
                            <a href="connexion.php?redirect=checkout.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Se connecter pour payer
                            </a>
                            <a href="inscription.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus"></i> Créer un compte
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <h6><i class="fas fa-shield-alt"></i> Paiement sécurisé</h6>
                        <p class="small text-muted">
                            <i class="fas fa-lock text-success"></i> Transactions 100% sécurisées
                        </p>
                        <div class="text-center">
                            <img src="<?php echo SITE_URL; ?>assets/images/paiement-methods.png" 
                                 alt="Méthodes de paiement" 
                                 class="img-fluid">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>