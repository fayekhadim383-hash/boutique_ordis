<?php
// Menu de navigation de l'administration
$current_page = basename($_SERVER['PHP_SELF']);

// Définir le chemin racine
$root_url = dirname(dirname($_SERVER['PHP_SELF']));
$root_url = str_replace('\\', '/', $root_url);
if ($root_url == '/') {
    $root_url = '';
}
?>

<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
            <i class="fas fa-tachometer-alt"></i>
            Tableau de bord
        </a>
    </li>
</ul>

<h6 class="sidebar-heading mt-4 mb-1 text-muted">
    <span>Gestion du site</span>
</h6>

<ul class="nav flex-column mb-2">
    <li class="nav-item">
        <a class="nav-link <?php echo ($current_page == 'produits.php' || $current_page == 'ajouter-produit.php' || $current_page == 'modifier-produit.php') ? 'active' : ''; ?>" href="produits.php">
            <i class="fas fa-laptop"></i>
            Produits
            <?php
            // Afficher le nombre de produits avec stock faible
            $low_stock = $conn->query("SELECT COUNT(*) as count FROM produits WHERE quantite <= seuil_alerte AND quantite > 0")->fetch_assoc()['count'];
            if ($low_stock > 0): ?>
                <span class="badge bg-warning float-end"><?php echo $low_stock; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($current_page == 'categories.php' || $current_page == 'ajouter-categorie.php' || $current_page == 'modifier-categorie.php') ? 'active' : ''; ?>" href="categories.php">
            <i class="fas fa-tags"></i>
            Catégories
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($current_page == 'commandes.php' || $current_page == 'details-commande.php' || $current_page == 'modifier-commande.php') ? 'active' : ''; ?>" href="commandes.php">
            <i class="fas fa-shopping-cart"></i>
            Commandes
            <?php
            // Afficher le nombre de commandes en attente
            $pending_orders = $conn->query("SELECT COUNT(*) as count FROM commandes WHERE statut = 'en_attente'")->fetch_assoc()['count'];
            if ($pending_orders > 0): ?>
                <span class="badge bg-danger float-end"><?php echo $pending_orders; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo ($current_page == 'utilisateurs.php' || $current_page == 'ajouter-utilisateur.php' || $current_page == 'modifier-utilisateur.php') ? 'active' : ''; ?>" href="utilisateurs.php">
            <i class="fas fa-users"></i>
            Utilisateurs
        </a>
    </li>
</ul>

<h6 class="sidebar-heading mt-4 mb-1 text-muted">
    <span>Configuration</span>
</h6>

<ul class="nav flex-column mb-2">
    <li class="nav-item">
        <a class="nav-link <?php echo ($current_page == 'parametres.php') ? 'active' : ''; ?>" href="parametres.php">
            <i class="fas fa-cog"></i>
            Paramètres
        </a>
    </li>
</ul>

<hr class="mt-4">

<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link text-danger" href="<?php echo $root_url; ?>/pages/deconnexion.php">
            <i class="fas fa-sign-out-alt"></i>
            Déconnexion
        </a>
    </li>
</ul>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}
</script>