<?php
require_once 'includes/admin-header.php';

$page_title = "Gestion des produits";

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtres
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$stock_filter = isset($_GET['stock_filter']) ? sanitize($_GET['stock_filter']) : 'all';
$status_filter = isset($_GET['status_filter']) ? sanitize($_GET['status_filter']) : 'all';

// Construire la requête
$sql = "SELECT SQL_CALC_FOUND_ROWS p.*, c.nom as categorie_nom 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id 
        WHERE 1=1";

$conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $conditions[] = "(p.nom LIKE ? OR p.description LIKE ? OR p.marque LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if ($category_id > 0) {
    $conditions[] = "p.categorie_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($stock_filter === 'low') {
    $conditions[] = "p.quantite <= p.seuil_alerte AND p.quantite > 0";
} elseif ($stock_filter === 'out') {
    $conditions[] = "p.quantite = 0";
} elseif ($stock_filter === 'in') {
    $conditions[] = "p.quantite > 0";
}

if ($status_filter === 'active') {
    $conditions[] = "p.est_actif = 1";
} elseif ($status_filter === 'inactive') {
    $conditions[] = "p.est_actif = 0";
} elseif ($status_filter === 'promo') {
    $conditions[] = "p.est_en_promotion = 1";
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
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
$categories = $conn->query("SELECT * FROM categories ORDER BY nom")->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestion des produits</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="ajouter-produit.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Ajouter un produit
        </a>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Recherche</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Nom, description, marque...">
            </div>
            
            <div class="col-md-3">
                <label for="category_id" class="form-label">Catégorie</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="0">Toutes les catégories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" 
                        <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['nom']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="stock_filter" class="form-label">Stock</label>
                <select class="form-select" id="stock_filter" name="stock_filter">
                    <option value="all" <?php echo ($stock_filter == 'all') ? 'selected' : ''; ?>>Tous</option>
                    <option value="low" <?php echo ($stock_filter == 'low') ? 'selected' : ''; ?>>Faible</option>
                    <option value="out" <?php echo ($stock_filter == 'out') ? 'selected' : ''; ?>>Rupture</option>
                    <option value="in" <?php echo ($stock_filter == 'in') ? 'selected' : ''; ?>>En stock</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status_filter" class="form-label">Statut</label>
                <select class="form-select" id="status_filter" name="status_filter">
                    <option value="all" <?php echo ($status_filter == 'all') ? 'selected' : ''; ?>>Tous</option>
                    <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Actifs</option>
                    <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactifs</option>
                    <option value="promo" <?php echo ($status_filter == 'promo') ? 'selected' : ''; ?>>En promotion</option>
                </select>
            </div>
            
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des produits -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-laptop me-2"></i>Liste des produits (<?php echo $total_products; ?>)</h6>
        <div>
            <a href="produits.php?stock_filter=low" class="btn btn-sm btn-warning">
                <i class="fas fa-exclamation-triangle"></i> Stock faible
            </a>
            <a href="produits.php?stock_filter=out" class="btn btn-sm btn-danger">
                <i class="fas fa-times-circle"></i> Rupture
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Aucun produit trouvé.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Image</th>
                            <th>Nom</th>
                            <th width="10%">Catégorie</th>
                            <th width="10%">Prix</th>
                            <th width="8%">Stock</th>
                            <th width="10%">Statut</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <img src="../<?php echo $product['image_url']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                                     class="product-img-admin">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['nom']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($product['marque']); ?></small>
                                <?php if ($product['est_en_promotion']): ?>
                                    <span class="badge bg-danger">PROMO</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['categorie_nom']); ?></td>
                            <td>
                                <strong class="text-primary"><?php echo formatPrice($product['prix']); ?></strong>
                                <?php if ($product['prix_promotion']): ?>
                                    <br>
                                    <small class="text-success"><?php echo formatPrice($product['prix_promotion']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['quantite'] == 0): ?>
                                    <span class="badge bg-danger">Rupture</span>
                                <?php elseif ($product['quantite'] <= $product['seuil_alerte']): ?>
                                    <span class="badge bg-warning"><?php echo $product['quantite']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?php echo $product['quantite']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['est_actif']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="../../pages/produit-details.php?id=<?php echo $product['id']; ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-primary" 
                                       title="Voir sur le site">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="modifier-produit.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-warning" 
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-delete" 
                                            data-id="<?php echo $product['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($product['nom']); ?>"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Pagination">
                <ul class="pagination justify-content-center mt-4">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php 
                            echo http_build_query(array_merge($_GET, ['page' => $page - 1])); 
                        ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php 
                                    echo http_build_query(array_merge($_GET, ['page' => $i])); 
                                ?>">
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
                        <a class="page-link" href="?<?php 
                            echo http_build_query(array_merge($_GET, ['page' => $page + 1])); 
                        ?>">
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

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="supprimer-produit.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le produit <strong id="delete_name"></strong> ?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Cette action est irréversible. Toutes les commandes associées seront affectées.
                    </p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirm_delete" required>
                        <label class="form-check-label" for="confirm_delete">
                            Je confirme vouloir supprimer ce produit
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gérer la modal de suppression
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.btn-delete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });
    });
    
    // Initialiser DataTables
    $('.datatable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json"
        },
        "pageLength": 25,
        "order": [[0, 'desc']],
        "responsive": true
    });
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>