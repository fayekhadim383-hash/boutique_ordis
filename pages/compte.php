<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Mon compte - " . SITE_NAME;

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error'] = "Veuillez vous connecter pour accéder à votre compte";
    redirect('connexion.php');
}

$user_id = $_SESSION['user_id'];

// Récupérer les informations utilisateur
$user_stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Mettre à jour les informations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $nom = sanitize($_POST['nom']);
        $telephone = sanitize($_POST['telephone']);
        $adresse = sanitize($_POST['adresse']);
        
        $stmt = $conn->prepare("UPDATE utilisateurs SET nom = ?, telephone = ?, adresse = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nom, $telephone, $adresse, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_nom'] = $nom;
            $_SESSION['success'] = "Profil mis à jour avec succès";
            redirect('compte.php');
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour";
        }
        $stmt->close();
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Vérifier le mot de passe actuel
        if (!password_verify($current_password, $user['password'])) {
            $_SESSION['error'] = "Mot de passe actuel incorrect";
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error'] = "Le nouveau mot de passe doit contenir au moins 6 caractères";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "Les nouveaux mots de passe ne correspondent pas";
        } else {
            // Mettre à jour le mot de passe
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Mot de passe modifié avec succès";
                redirect('compte.php');
            } else {
                $_SESSION['error'] = "Erreur lors du changement de mot de passe";
            }
            $stmt->close();
        }
    }
}

// Récupérer les commandes de l'utilisateur
$orders_stmt = $conn->prepare("SELECT * FROM commandes WHERE utilisateur_id = ? ORDER BY date_commande DESC");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5><?php echo $user['nom']; ?></h5>
                <p class="text-muted"><?php echo $user['email']; ?></p>
                <p class="small text-muted">
                    Membre depuis <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                </p>
            </div>
            <div class="list-group list-group-flush">
                <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="tab">
                    <i class="fas fa-user me-2"></i> Mon profil
                </a>
                <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                    <i class="fas fa-shopping-bag me-2"></i> Mes commandes
                    <span class="badge bg-primary rounded-pill float-end"><?php echo count($orders); ?></span>
                </a>
                <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                    <i class="fas fa-lock me-2"></i> Changer mot de passe
                </a>
                <a href="deconnexion.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="tab-content">
            <!-- Onglet Profil -->
            <div class="tab-pane fade show active" id="profile">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Mon profil</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom complet *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <small class="text-muted">L'email ne peut pas être modifié</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone"
                                           value="<?php echo htmlspecialchars($user['telephone']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="created_at" class="form-label">Date d'inscription</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('d/m/Y', strtotime($user['created_at'])); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse</label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php 
                                    echo htmlspecialchars($user['adresse']); 
                                ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Mettre à jour
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Onglet Commandes -->
            <div class="tab-pane fade" id="orders">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-bag"></i> Mes commandes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h5>Aucune commande</h5>
                                <p class="text-muted">Vous n'avez pas encore passé de commande</p>
                                <a href="produits.php" class="btn btn-primary">Commencer mes achats</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>N° Commande</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Statut</th>
                                            <th>Paiement</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $order['numero_commande']; ?></strong>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($order['date_commande'])); ?></td>
                                            <td><?php echo formatPrice($order['total']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch($order['statut']) {
                                                        case 'en_attente': echo 'warning'; break;
                                                        case 'payee': echo 'info'; break;
                                                        case 'expediee': echo 'primary'; break;
                                                        case 'livree': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['statut'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo ucfirst(str_replace('_', ' ', $order['methode_paiement'])); ?>
                                            </td>
                                            <td>
                                                <a href="confirmation.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Voir
                                                </a>
                                                <?php if ($order['statut'] == 'en_attente'): ?>
                                                    <a href="#" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Onglet Mot de passe -->
            <div class="tab-pane fade" id="password">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-lock"></i> Changer le mot de passe</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel *</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <small class="text-muted">Minimum 6 caractères</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Changer le mot de passe
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Activer les onglets avec les ancres
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash) {
        const tabTrigger = document.querySelector(`[href="${window.location.hash}"]`);
        if (tabTrigger) {
            new bootstrap.Tab(tabTrigger).show();
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>