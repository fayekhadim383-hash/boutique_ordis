<?php
require_once 'includes/admin-header.php';

$page_title = "Gestion des commandes";

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtres
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Construire la requête
$sql = "SELECT SQL_CALC_FOUND_ROWS c.*, u.nom as client_nom, u.email as client_email 
        FROM commandes c 
        JOIN utilisateurs u ON c.utilisateur_id = u.id 
        WHERE 1=1";

$conditions = [];
$params = [];
$types = "";

if ($status !== 'all') {
    $conditions[] = "c.statut = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($date_from)) {
    $conditions[] = "DATE(c.date_commande) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $conditions[] = "DATE(c.date_commande) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if (!empty($search)) {
    $conditions[] = "(c.numero_commande LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY c.date_commande DESC LIMIT ? OFFSET ?";
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
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Récupérer le nombre total
$total_result = $conn->query("SELECT FOUND_ROWS() as total");
$total_row = $total_result->fetch_assoc();
$total_orders = $total_row['total'];
$total_pages = ceil($total_orders / $limit);

// Statistiques des commandes
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'payee' THEN 1 ELSE 0 END) as payee,
        SUM(CASE WHEN statut = 'expediee' THEN 1 ELSE 0 END) as expediee,
        SUM(CASE WHEN statut = 'livree' THEN 1 ELSE 0 END) as livree,
        SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) as annulee,
        SUM(CASE WHEN statut IN ('payee', 'expediee', 'livree') THEN total ELSE 0 END) as total_revenue
    FROM commandes
    WHERE 1=1
";

// Appliquer les mêmes conditions aux statistiques
if (!empty($conditions)) {
    $stats_sql .= " AND " . implode(" AND ", array_slice($conditions, 0, count($conditions) - 2));
}

$stats_stmt = $conn->prepare($stats_sql);
if (!empty(array_slice($params, 0, count($params) - 2))) {
    $stats_stmt->bind_param(
        substr($types, 0, -2), 
        ...array_slice($params, 0, count($params) - 2)
    );
}
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestion des commandes</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-primary me-2">
            <?php echo formatPrice($stats['total_revenue'] ?? 0); ?> de CA
        </span>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="row mb-4">
    <div class="col-md-2 col-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center py-3">
                <h6 class="card-title mb-1">Total</h6>
                <h4 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 col-6 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center py-3">
                <h6 class="card-title mb-1">En attente</h6>
                <h4 class="mb-0"><?php echo $stats['en_attente'] ?? 0; ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 col-6 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center py-3">
                <h6 class="card-title mb-1">Payées</h6>
                <h4 class="mb-0"><?php echo $stats['payee'] ?? 0; ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 col-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center py-3">
                <h6 class="card-title mb-1">Expédiées</h6>
                <h4 class="mb-0"><?php echo $stats['expediee'] ?? 0; ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 col-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center py-3">
                <h6 class="card-title mb-1">Livrées</h6>
                <h4 class="mb-0"><?php echo $stats['livree'] ?? 0; ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-2 col-6 mb-3">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center py-3">
                <h6 class="card-title mb-1">Annulées</h6>
                <h4 class="mb-0"><?php echo $stats['annulee'] ?? 0; ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Statut</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>Tous les statuts</option>
                    <option value="en_attente" <?php echo ($status == 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                    <option value="payee" <?php echo ($status == 'payee') ? 'selected' : ''; ?>>Payée</option>
                    <option value="expediee" <?php echo ($status == 'expediee') ? 'selected' : ''; ?>>Expédiée</option>
                    <option value="livree" <?php echo ($status == 'livree') ? 'selected' : ''; ?>>Livrée</option>
                    <option value="annulee" <?php echo ($status == 'annulee') ? 'selected' : ''; ?>>Annulée</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">Date de début</label>
                <input type="date" class="form-control" id="date_from" name="date_from"
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">Date de fin</label>
                <input type="date" class="form-control" id="date_to" name="date_to"
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Recherche</label>
                <input type="text" class="form-control" id="search" name="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="N° commande, client, email...">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Filtrer
                </button>
            </div>
        </form>
        
        <!-- Boutons de filtre rapide -->
        <div class="mt-3">
            <div class="btn-group btn-group-sm" role="group">
                <a href="commandes.php?status=en_attente" class="btn btn-outline-warning">
                    <i class="fas fa-clock"></i> En attente (<?php echo $stats['en_attente'] ?? 0; ?>)
                </a>
                <a href="commandes.php?status=payee" class="btn btn-outline-info">
                    <i class="fas fa-money-bill-wave"></i> Payées (<?php echo $stats['payee'] ?? 0; ?>)
                </a>
                <a href="commandes.php?status=expediee" class="btn btn-outline-primary">
                    <i class="fas fa-shipping-fast"></i> Expédiées (<?php echo $stats['expediee'] ?? 0; ?>)
                </a>
                <a href="commandes.php?status=livree" class="btn btn-outline-success">
                    <i class="fas fa-check-circle"></i> Livrées (<?php echo $stats['livree'] ?? 0; ?>)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tableau des commandes -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-shopping-cart me-2"></i>
            Liste des commandes (<?php echo $total_orders; ?>)
        </h6>
        <a href="?export=csv" class="btn btn-sm btn-success">
            <i class="fas fa-download"></i> Exporter CSV
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Aucune commande trouvée.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th width="10%">N° Commande</th>
                            <th width="15%">Client</th>
                            <th width="10%">Date</th>
                            <th width="10%">Total</th>
                            <th width="15%">Paiement</th>
                            <th width="15%">Statut</th>
                            <th width="25%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="details-commande.php?id=<?php echo $order['id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo $order['numero_commande']; ?>
                                    </a>
                                </strong>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($order['client_nom']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($order['client_email']); ?></small>
                                </div>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($order['date_commande'])); ?>
                                <br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($order['date_commande'])); ?></small>
                            </td>
                            <td class="fw-bold"><?php echo formatPrice($order['total']); ?></td>
                            <td>
                                <?php 
                                $payment_methods = [
                                    'carte_visa' => '<i class="fab fa-cc-visa text-primary"></i> Carte',
                                    'wave' => '<i class="fas fa-mobile-alt text-success"></i> Wave',
                                    'orange_money' => '<i class="fas fa-mobile-alt text-warning"></i> Orange Money',
                                    'paypal' => '<i class="fab fa-paypal text-primary"></i> PayPal',
                                    'especes' => '<i class="fas fa-money-bill-wave text-success"></i> Espèces'
                                ];
                                echo $payment_methods[$order['methode_paiement']] ?? $order['methode_paiement'];
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    switch($order['statut']) {
                                        case 'en_attente': echo 'warning'; break;
                                        case 'payee': echo 'info'; break;
                                        case 'expediee': echo 'primary'; break;
                                        case 'livree': echo 'success'; break;
                                        case 'annulee': echo 'secondary'; break;
                                        default: echo 'light';
                                    }
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['statut'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="details-commande.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-outline-primary" 
                                       title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="modifier-commande.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-outline-warning" 
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($order['statut'] == 'en_attente'): ?>
                                        <button type="button" 
                                                class="btn btn-outline-success btn-update-status" 
                                                data-id="<?php echo $order['id']; ?>"
                                                data-status="payee"
                                                title="Marquer comme payée">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="../../pages/confirmation.php?id=<?php echo $order['id']; ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-info" 
                                       title="Voir facture">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
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

<!-- Modal pour changer le statut -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="modifier-commande.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" id="update_order_id">
                <input type="hidden" name="action" value="update_status">
                
                <div class="modal-header">
                    <h5 class="modal-title">Changer le statut de la commande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Nouveau statut</label>
                        <select class="form-select" id="new_status" name="statut" required>
                            <option value="en_attente">En attente</option>
                            <option value="payee">Payée</option>
                            <option value="expediee">Expédiée</option>
                            <option value="livree">Livrée</option>
                            <option value="annulee">Annulée</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status_notes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" id="status_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gérer la modal de changement de statut
document.addEventListener('DOMContentLoaded', function() {
    const updateButtons = document.querySelectorAll('.btn-update-status');
    
    updateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            
            document.getElementById('update_order_id').value = orderId;
            
            // Pré-sélectionner le statut suivant
            const statusSelect = document.getElementById('new_status');
            if (currentStatus === 'en_attente') {
                statusSelect.value = 'payee';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
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