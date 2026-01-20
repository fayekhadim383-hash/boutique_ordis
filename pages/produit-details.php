<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'ID est présent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Produit non trouvé";
    redirect(SITE_URL . 'pages/produits.php');
}

$product_id = intval($_GET['id']);
$product = getProductById($conn, $product_id);

if (!$product) {
    $_SESSION['error'] = "Produit non trouvé";
    redirect(SITE_URL . 'pages/produits.php');
}

$page_title = $product['nom'] . " - " . SITE_NAME;

// Récupérer les produits similaires
$similar_products = getAllProducts($conn, 4, $product['categorie_id']);

// Traitement de l'ajout au panier via AJAX sera géré par panier.js
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-6">
        <!-- Image principale -->
        <div class="product-image mb-3">
            <img src="<?php echo SITE_URL . $product['image_url']; ?>" 
                 alt="<?php echo $product['nom']; ?>" 
                 class="img-fluid rounded border" 
                 id="main-product-image">
        </div>
        
        <!-- Images miniatures (si disponibles) -->
        <div class="row g-2">
            <div class="col-3">
                <img src="<?php echo SITE_URL . $product['image_url']; ?>" 
                     class="img-thumbnail active" 
                     style="cursor: pointer;"
                     onclick="changeMainImage(this.src)">
            </div>
            <!-- Ajouter plus d'images si disponibles -->
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="product-details">
            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="badge bg-primary"><?php echo $product['categorie_nom']; ?></span>
                    <span class="badge bg-info"><?php echo $product['marque']; ?></span>
                    <?php if ($product['quantite'] <= 5 && $product['quantite'] > 0): ?>
                        <span class="badge bg-warning">Stock faible</span>
                    <?php elseif ($product['quantite'] == 0): ?>
                        <span class="badge bg-danger">Rupture de stock</span>
                    <?php endif; ?>
                </div>
                <div>
                    <button class="btn btn-outline-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                </div>
            </div>
            
            <!-- Nom et prix -->
            <h1 class="h2 mb-3"><?php echo $product['nom']; ?></h1>
            <p class="lead text-primary fw-bold fs-3 mb-4"><?php echo formatPrice($product['prix']); ?></p>
            
            <!-- Description -->
            <div class="mb-4">
                <h5>Description</h5>
                <p><?php echo nl2br($product['description']); ?></p>
            </div>
            
            <!-- Caractéristiques -->
            <div class="mb-4">
                <h5>Caractéristiques techniques</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="30%">Processeur</th>
                        <td><?php echo $product['processeur']; ?></td>
                    </tr>
                    <tr>
                        <th>Mémoire RAM</th>
                        <td><?php echo $product['ram']; ?></td>
                    </tr>
                    <tr>
                        <th>Stockage</th>
                        <td><?php echo $product['stockage']; ?></td>
                    </tr>
                    <tr>
                        <th>Écran</th>
                        <td><?php echo $product['ecran']; ?></td>
                    </tr>
                    <tr>
                        <th>Carte graphique</th>
                        <td><?php echo $product['carte_graphique']; ?></td>
                    </tr>
                    <tr>
                        <th>Système d'exploitation</th>
                        <td>Windows 11 Professionnel</td>
                    </tr>
                    <tr>
                        <th>Garantie</th>
                        <td>2 ans</td>
                    </tr>
                </table>
            </div>
            
            <!-- Stock et quantité -->
            <div class="mb-4">
                <h5>Disponibilité</h5>
                <?php if ($product['quantite'] > 0): ?>
                    <p class="text-success">
                        <i class="fas fa-check-circle"></i> En stock (<?php echo $product['quantite']; ?> disponibles)
                    </p>
                <?php else: ?>
                    <p class="text-danger">
                        <i class="fas fa-times-circle"></i> Rupture de stock
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Ajout au panier -->
            <?php if ($product['quantite'] > 0): ?>
            <div class="card bg-light">
                <div class="card-body">
                    <form id="add-to-cart-form">
                        <div class="row align-items-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label for="quantity" class="form-label">Quantité</label>
                                <select class="form-select" id="quantity" name="quantity">
                                    <?php for ($i = 1; $i <= min(10, $product['quantite']); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <div class="d-grid gap-2">
                                    <button type="button" 
                                            class="btn btn-primary btn-lg add-to-cart"
                                            data-id="<?php echo $product['id']; ?>"
                                            data-name="<?php echo $product['nom']; ?>">
                                        <i class="fas fa-cart-plus"></i> Ajouter au panier
                                    </button>
                                    <button type="button" class="btn btn-success btn-lg">
                                        <i class="fas fa-bolt"></i> Acheter maintenant
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <p>Ce produit n'est actuellement pas disponible. Vous pouvez nous contacter pour plus d'informations.</p>
                    <a href="../pages/contact.php" class="btn btn-warning">Nous contacter</a>
                </div>
            <?php endif; ?>
            
            <!-- Partage -->
            <div class="mt-4">
                <p class="mb-2">Partager ce produit :</p>
                <div class="social-sharing">
                    <a href="#" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="btn btn-outline-info btn-sm me-2">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="btn btn-outline-success btn-sm me-2">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="#" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Produits similaires -->
<?php if (!empty($similar_products)): ?>
<div class="mt-5">
    <h3 class="mb-4">Produits similaires</h3>
    <div class="row">
        <?php foreach ($similar_products as $similar): 
            if ($similar['id'] == $product['id']) continue; ?>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card h-100">
                <img src="<?php echo SITE_URL . $similar['image_url']; ?>" 
                     class="card-img-top" 
                     alt="<?php echo $similar['nom']; ?>"
                     style="height: 150px; object-fit: contain;">
                <div class="card-body">
                    <h6 class="card-title"><?php echo $similar['nom']; ?></h6>
                    <p class="card-text text-primary fw-bold"><?php echo formatPrice($similar['prix']); ?></p>
                    <a href="produit-details.php?id=<?php echo $similar['id']; ?>" class="btn btn-sm btn-outline-primary">
                        Voir détails
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Avis clients (section vide pour le moment) -->
<div class="mt-5">
    <h3>Avis clients</h3>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Soyez le premier à donner votre avis sur ce produit.
    </div>
</div>

<script>
function changeMainImage(src) {
    document.getElementById('main-product-image').src = src;
    document.querySelectorAll('.img-thumbnail').forEach(img => img.classList.remove('active'));
    event.target.classList.add('active');
}
</script>

<?php include '../includes/footer.php'; ?>