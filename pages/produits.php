<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Produits - " . SITE_NAME;

// Paramètres de filtrage
$categorie_id = isset($_GET['categorie']) ? intval($_GET['categorie']) : null;
$marque = isset($_GET['marque']) ? sanitize($_GET['marque']) : null;
$prix_min = isset($_GET['prix_min']) ? floatval($_GET['prix_min']) : null;
$prix_max = isset($_GET['prix_max']) ? floatval($_GET['prix_max']) : null;
$tri = isset($_GET['tri']) ? sanitize($_GET['tri']) : 'nouveautes';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Construire la requête
$sql = "SELECT SQL_CALC_FOUND_ROWS p.*, c.nom as categorie_nom FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id 
        WHERE p.quantite > 0";

$conditions = [];
$params = [];
$types = "";

if ($categorie_id) {
    $conditions[] = "p.categorie_id = ?";
    $params[] = $categorie_id;
    $types .= "i";
}

if ($marque) {
    $conditions[] = "p.marque = ?";
    $params[] = $marque;
    $types .= "s";
}

if ($prix_min !== null) {
    $conditions[] = "p.prix >= ?";
    $params[] = $prix_min;
    $types .= "d";
}

if ($prix_max !== null) {
    $conditions[] = "p.prix <= ?";
    $params[] = $prix_max;
    $types .= "d";
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Tri
switch ($tri) {
    case 'prix_croissant':
        $sql .= " ORDER BY p.prix ASC";
        break;
    case 'prix_decroissant':
        $sql .= " ORDER BY p.prix DESC";
        break;
    case 'nom':
        $sql .= " ORDER BY p.nom ASC";
        break;
    case 'nouveautes':
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

// Pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$types .= "i";
$params[] = $offset;
$types .= "i";

// Exécuter la requête
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Récupérer le nombre total
$total_result = $conn->query("SELECT FOUND_ROWS() as total");
$total_row = $total_result->fetch_assoc();
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $limit);

// Récupérer les catégories pour le filtre
$categories = getCategories($conn);

// Récupérer les marques uniques
$brands_result = $conn->query("SELECT DISTINCT marque FROM produits WHERE marque IS NOT NULL AND marque != '' ORDER BY marque");
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <!-- Sidebar avec filtres -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filtres</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <!-- Filtre par catégorie -->
                    <div class="mb-4">
                        <h6>Catégories</h6>
                        <div class="list-group list-group-flush">
                            <a href="produits.php" class="list-group-item list-group-item-action <?php echo !$categorie_id ? 'active' : ''; ?>">
                                Toutes les catégories
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="produits.php?categorie=<?php echo $category['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $categorie_id == $category['id'] ? 'active' : ''; ?>">
                                <?php echo $category['nom']; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Filtre par marque -->
                    <div class="mb-4">
                        <h6>Marques</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="marque" value="" 
                                   id="marque_all" <?php echo !$marque ? 'checked' : ''; ?> 
                                   onchange="this.form.submit()">
                            <label class="form-check-label" for="marque_all">Toutes les marques</label>
                        </div>
                        <?php foreach ($brands as $brand_item): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="marque" 
                                   value="<?php echo $brand_item['marque']; ?>" 
                                   id="marque_<?php echo $brand_item['marque']; ?>"
                                   <?php echo $marque == $brand_item['marque'] ? 'checked' : ''; ?>
                                   onchange="this.form.submit()">
                            <label class="form-check-label" for="marque_<?php echo $brand_item['marque']; ?>">
                                <?php echo $brand_item['marque']; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Filtre par prix -->
                    <div class="mb-4">
                        <h6>Prix (FCFA)</h6>
                        <div class="row g-2">
                            <div class="col">
                                <input type="number" class="form-control" name="prix_min" 
                                       placeholder="Min" value="<?php echo $prix_min ?: ''; ?>"
                                       min="0">
                            </div>
                            <div class="col">
                                <input type="number" class="form-control" name="prix_max" 
                                       placeholder="Max" value="<?php echo $prix_max ?: ''; ?>"
                                       min="0">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary mt-2 w-100">Appliquer</button>
                    </div>
                    
                    <!-- Tri -->
                    <div class="mb-3">
                        <h6>Trier par</h6>
                        <select class="form-select" name="tri" onchange="this.form.submit()">
                            <option value="nouveautes" <?php echo $tri == 'nouveautes' ? 'selected' : ''; ?>>Nouveautés</option>
                            <option value="prix_croissant" <?php echo $tri == 'prix_croissant' ? 'selected' : ''; ?>>Prix croissant</option>
                            <option value="prix_decroissant" <?php echo $tri == 'prix_decroissant' ? 'selected' : ''; ?>>Prix décroissant</option>
                            <option value="nom" <?php echo $tri == 'nom' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Promotion spéciale -->
        <div class="card bg-warning">
            <div class="card-body text-center">
                <h5 class="card-title">Promotion spéciale !</h5>
                <p class="card-text">Jusqu'à -30% sur les ordinateurs gaming</p>
                <a href="produits.php?categorie=3" class="btn btn-danger">Voir les offres</a>
            </div>
        </div>
    </div>
    
    <!-- Liste des produits -->
    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <?php 
                if ($categorie_id) {
                    $cat_name = $conn->query("SELECT nom FROM categories WHERE id = $categorie_id")->fetch_assoc()['nom'];
                    echo "Produits : " . $cat_name;
                } elseif ($marque) {
                    echo "Produits " . $marque;
                } else {
                    echo "Tous les produits";
                }
                ?>
                <small class="text-muted fs-6">(<?php echo $total_products; ?> produits)</small>
            </h2>
            <div>
                <span class="me-2">Vue :</span>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary active" onclick="changeView('grid')">
                        <i class="fas fa-th"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="changeView('list')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Aucun produit trouvé avec ces critères.
            </div>
        <?php else: ?>
            <!-- Grille des produits -->
            <div class="row" id="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card product-card h-100">
                        <?php if ($product['quantite'] <= 5 && $product['quantite'] > 0): ?>
                            <span class="badge bg-warning position-absolute top-0 start-0 m-2">Stock faible</span>
                        <?php elseif ($product['quantite'] == 0): ?>
                            <span class="badge bg-danger position-absolute top-0 start-0 m-2">Rupture</span>
                        <?php endif; ?>
                        
                        <img src="<?php echo SITE_URL . $product['image_url']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo $product['nom']; ?>"
                             style="height: 200px; object-fit: contain;">
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $product['nom']; ?></h5>
                            <p class="card-text text-muted">
                                <small><?php echo $product['marque']; ?> • <?php echo $product['processeur']; ?></small>
                            </p>
                            <div class="specs mb-2">
                                <small><i class="fas fa-memory"></i> <?php echo $product['ram']; ?></small>
                                <small><i class="fas fa-hdd"></i> <?php echo $product['stockage']; ?></small>
                                <small><i class="fas fa-tv"></i> <?php echo $product['ecran']; ?></small>
                            </div>
                            <p class="card-text fw-bold text-primary"><?php echo formatPrice($product['prix']); ?></p>
                            
                            <div class="d-grid gap-2">
                                <a href="produit-details.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-primary">Voir détails</a>
                                
                                <?php if ($product['quantite'] > 0): ?>
                                    <button class="btn btn-primary add-to-cart" 
                                            data-id="<?php echo $product['id']; ?>"
                                            data-name="<?php echo $product['nom']; ?>">
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <li class="page-item active">
                                <span class="page-link"><?php echo $i; ?></span>
                            </li>
                        <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function changeView(view) {
    const gridView = document.getElementById('products-grid');
    const buttons = document.querySelectorAll('.btn-group .btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    if (view === 'grid') {
        gridView.className = 'row';
    } else {
        gridView.className = 'list-group';
        const products = gridView.querySelectorAll('.col-md-4');
        products.forEach(product => {
            product.className = 'list-group-item';
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>