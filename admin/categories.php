<?php
require_once 'includes/admin-header.php';

$page_title = "Gestion des catégories";

// Récupérer toutes les catégories
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) as nb_produits 
    FROM categories c 
    LEFT JOIN produits p ON c.id = p.categorie_id 
    GROUP BY c.id 
    ORDER BY c.nom
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestion des catégories</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="ajouter-categorie.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Ajouter une catégorie
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Liste des catégories</h6>
    </div>
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Aucune catégorie n'a été créée.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th>Nom</th>
                            <th>Slug</th>
                            <th width="15%">Produits</th>
                            <th width="20%">Description</th>
                            <th width="20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($category['nom']); ?></strong>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($category['slug']); ?></code>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $category['nb_produits']; ?> produits</span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo substr($category['description'], 0, 100); ?>
                                    <?php if (strlen($category['description']) > 100): ?>...<?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="modifier-categorie.php?id=<?php echo $category['id']; ?>" 
                                       class="btn btn-outline-warning" 
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($category['nb_produits'] == 0): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-delete-category" 
                                                data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['nom']); ?>"
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-outline-secondary" 
                                                title="Impossible de supprimer (contient des produits)"
                                                disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="supprimer-categorie.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" id="delete_category_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer la catégorie <strong id="delete_category_name"></strong> ?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Cette action est irréversible.
                    </p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirm_category_delete" required>
                        <label class="form-check-label" for="confirm_category_delete">
                            Je confirme vouloir supprimer cette catégorie
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
// Gérer la modal de suppression de catégorie
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.btn-delete-category');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = name;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            modal.show();
        });
    });
    
    // Initialiser DataTables
    $('.datatable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json"
        },
        "pageLength": 25,
        "order": [[0, 'asc']],
        "responsive": true
    });
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>