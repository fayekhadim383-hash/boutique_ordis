<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Confirmation - " . SITE_NAME;

// Vérifier si l'ID de commande est présent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Commande non trouvée";
    redirect('produits.php');
}

$order_id = intval($_GET['id']);

// Récupérer les informations de la commande
$sql = "SELECT c.*, u.nom as client_nom, u.email as client_email 
        FROM commandes c 
        JOIN utilisateurs u ON c.utilisateur_id = u.id 
        WHERE c.id = ? AND c.utilisateur_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = "Commande non trouvée";
    redirect('produits.php');
}

// Récupérer les détails de la commande
$sql = "SELECT cd.*, p.nom as produit_nom, p.image_url 
        FROM commande_details cd 
        JOIN produits p ON cd.produit_id = p.id 
        WHERE cd.commande_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card text-center">
            <div class="card-body py-5">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h1 class="mb-3">Merci pour votre commande !</h1>
                <p class="lead mb-4">Votre commande a été reçue et est en cours de traitement.</p>
                
                <div class="alert alert-info text-start mb-4">
                    <h5><i class="fas fa-info-circle"></i> Informations importantes</h5>
                    <ul class="mb-0">
                        <li>Vous recevrez un email de confirmation sous peu</li>
                        <li>Votre commande sera expédiée dans les 24-48 heures</li>
                        <li>Numéro de suivi sera envoyé par email</li>
                    </ul>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Récapitulatif de commande</h5>
                    </div>
                    <div class="card-body text-start">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Informations de commande</h6>
                                <p class="mb-1"><strong>Numéro de commande :</strong> <?php echo $order['numero_commande']; ?></p>
                                <p class="mb-1"><strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></p>
                                <p class="mb-1"><strong>Statut :</strong> 
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
                                </p>
                                <p class="mb-1"><strong>Méthode de paiement :</strong> 
                                    <?php echo ucfirst(str_replace('_', ' ', $order['methode_paiement'])); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Informations de livraison</h6>
                                <p class="mb-1"><strong>Client :</strong> <?php echo $order['client_nom']; ?></p>
                                <p class="mb-1"><strong>Email :</strong> <?php echo $order['client_email']; ?></p>
                                <p class="mb-0"><strong>Adresse :</strong><br><?php echo nl2br($order['adresse_livraison']); ?></p>
                            </div>
                        </div>
                        
                        <h6 class="mt-4">Produits commandés</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th class="text-center">Quantité</th>
                                        <th class="text-end">Prix unitaire</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo SITE_URL . $item['image_url']; ?>" 
                                                     alt="<?php echo $item['produit_nom']; ?>" 
                                                     class="img-thumbnail me-2" 
                                                     style="width: 50px; height: 50px; object-fit: contain;">
                                                <div><?php echo $item['produit_nom']; ?></div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?php echo $item['quantite']; ?></td>
                                        <td class="text-end"><?php echo formatPrice($item['prix_unitaire']); ?></td>
                                        <td class="text-end fw-bold"><?php echo formatPrice($item['prix_unitaire'] * $item['quantite']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total :</strong></td>
                                        <td class="text-end fw-bold text-primary"><?php echo formatPrice($order['total']); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-center">
                    <a href="compte.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-circle"></i> Voir mes commandes
                    </a>
                    <a href="produits.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-shopping-bag"></i> Continuer mes achats
                    </a>
                </div>
                
                <div class="mt-4">
                    <p class="text-muted">
                        Des questions ? Contactez-nous à 
                        <a href="mailto:<?php echo SITE_EMAIL; ?>"><?php echo SITE_EMAIL; ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>