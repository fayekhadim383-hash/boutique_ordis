<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Accueil - " . SITE_NAME;

// Récupérer les produits populaires
$featured_products = getAllProducts($conn, 8);

// Récupérer les catégories
$categories = getCategories($conn);

// Marques disponibles
$brands = ['Dell', 'HP', 'Lenovo', 'Apple', 'Asus', 'Acer', 'MSI', 'Microsoft'];
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<!-- Hero Section -->
<section class="hero-section mb-5">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold text-white">Les meilleurs ordinateurs portables</h1>
                <p class="lead text-white">Découvrez notre sélection d'ordinateurs portables performants pour tous les besoins : travail, gaming, étude, et plus encore.</p>
                <div class="hero-buttons">
                    <a href="pages/produits.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-laptop me-2"></i>Voir tous les produits
                    </a>
                    <a href="#promotions" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-percent me-2"></i>Promotions
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-center">
                <div class="hero-image-container">
                    <img src="<?php echo SITE_URL; ?>assets/images/hero-bg1.jpg" alt="Ordinateur portable moderne" class="img-fluid floating">
                    <div class="badge badge-hero bg-warning text-dark">
                        <i class="fas fa-bolt"></i> Nouveautés 2026
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Catégories -->
<section class="categories-section mb-5">
    <h2 class="text-center mb-4">Catégories</h2>
    <div class="row">
        <?php foreach ($categories as $category): ?>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-laptop fa-3x text-primary mb-3"></i>
                    <h5 class="card-title"><?php echo htmlspecialchars($category['nom']); ?></h5>
                    <p class="card-text"><?php echo substr($category['description'], 0, 100); ?>...</p>
                    <a href="pages/produits.php?categorie=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                        Voir les produits
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Produits populaires -->
<section class="featured-products mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Produits populaires</h2>
        <a href="pages/produits.php" class="btn btn-primary">Voir tout</a>
    </div>
    
    <div class="row">
        <?php foreach ($featured_products as $product): ?>
        <div class="col-md-3 mb-4">
            <div class="card product-card h-100">
                <?php if ($product['quantite'] <= 5 && $product['quantite'] > 0): ?>
                    <span class="badge bg-warning position-absolute top-0 start-0 m-2">Stock faible</span>
                <?php elseif ($product['quantite'] == 0): ?>
                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">Rupture</span>
                <?php endif; ?>
                
                <?php
                // Générer le chemin de l'image
                $image_path = $product['image_url'];
                $image_url = '';
                
                if (!empty($image_path)) {
                    // Supprimer les slashs au début si présents
                    if (strpos($image_path, '/') === 0) {
                        $image_path = substr($image_path, 1);
                    }
                    
                    // Construire l'URL complète
                    $image_url = SITE_URL . $image_path;
                    
                    // Vérifier si le fichier existe
                    $full_path = $_SERVER['DOCUMENT_ROOT'] . parse_url(SITE_URL, PHP_URL_PATH) . $image_path;
                    if (!file_exists($full_path)) {
                        // Utiliser l'image par défaut
                        $image_url = SITE_URL . 'assets/images/produits/default.jpg';
                    }
                } else {
                    // Image par défaut
                    $image_url = SITE_URL . 'assets/images/produits/default.jpg';
                }
                ?>
                
                <img src="<?php echo $image_url; ?>" 
                     class="card-img-top" 
                     alt="<?php echo htmlspecialchars($product['nom']); ?>"
                     style="height: 200px; object-fit: contain;"
                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>assets/images/produits/default.jpg';">
                
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['nom']); ?></h5>
                    <p class="card-text text-muted">
                        <small><?php echo htmlspecialchars($product['marque']); ?> • <?php echo htmlspecialchars($product['processeur']); ?></small>
                    </p>
                    <div class="specs mb-2">
                        <small><i class="fas fa-memory"></i> <?php echo htmlspecialchars($product['ram']); ?></small>
                        <small><i class="fas fa-hdd"></i> <?php echo htmlspecialchars($product['stockage']); ?></small>
                        <small><i class="fas fa-tv"></i> <?php echo htmlspecialchars($product['ecran']); ?></small>
                    </div>
                    <p class="card-text fw-bold text-primary"><?php echo formatPrice($product['prix']); ?></p>
                    
                    <div class="d-grid gap-2">
                        <a href="pages/produit-details.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-outline-primary">Voir détails</a>
                        
                        <?php if ($product['quantite'] > 0): ?>
                            <button class="btn btn-primary add-to-cart" 
                                    data-id="<?php echo $product['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($product['nom']); ?>">
                                <i class="fas fa-cart-plus"></i> Ajouter au panier
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Rupture de stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Marques -->
<section class="brands-section mb-5">
    <h2 class="text-center mb-4">Nos marques</h2>
    <div class="row justify-content-center">
        <?php foreach ($brands as $brand): ?>
        <div class="col-md-2 col-4 text-center mb-3">
            <div class="brand-item p-3 border rounded">
                <h5 class="mb-0"><?php echo htmlspecialchars($brand); ?></h5>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Pourquoi nous choisir -->
<section class="why-us bg-light py-5 rounded">
    <h2 class="text-center mb-5">Pourquoi choisir PC Pro ?</h2>
    <div class="row text-center">
        <div class="col-md-3 mb-4">
            <div class="why-item">
                <i class="fas fa-shipping-fast fa-3x text-primary mb-3"></i>
                <h5>Livraison rapide</h5>
                <p>Livraison express sous 24-48h dans toute la région</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="why-item">
                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                <h5>Garantie 2 ans</h5>
                <p>Tous nos produits bénéficient d'une garantie de 2 ans</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="why-item">
                <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                <h5>Support 7j/7</h5>
                <p>Notre équipe est disponible pour vous aider</p>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="why-item">
                <i class="fas fa-undo fa-3x text-primary mb-3"></i>
                <h5>Retour facile</h5>
                <p>Retour possible sous 30 jours sans raison</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
