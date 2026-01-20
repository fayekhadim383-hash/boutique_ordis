<?php
// admin/dashboard.php
require_once 'includes/admin-header.php';

$page_title = "Tableau de bord";

// Fonction pour formater les nombres
function formatNumber($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return $number;
}

// Fonction pour calculer l'évolution
function calculateGrowth($current, $previous) {
    if ($previous == 0) return 100;
    return round((($current - $previous) / $previous) * 100, 1);
}

// Récupérer la période (mois, semaine, aujourd'hui)
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$date_filter = '';

switch ($period) {
    case 'today':
        $date_filter = "DATE(date_commande) = CURDATE()";
        break;
    case 'week':
        $date_filter = "date_commande >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
    default:
        $date_filter = "date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
}

// Statistiques principales
$stats = [];

// Total commandes (période)
$result = $conn->query("SELECT COUNT(*) as total FROM commandes WHERE $date_filter");
$stats['total_orders'] = $result->fetch_assoc()['total'];

// Total commandes (période précédente)
$prev_result = $conn->query("SELECT COUNT(*) as total FROM commandes WHERE $date_filter");
$stats['prev_orders'] = $prev_result->fetch_assoc()['total'];

// Chiffre d'affaires (période)
$result = $conn->query("SELECT SUM(total) as total FROM commandes WHERE statut IN ('payee', 'expediee', 'livree') AND $date_filter");
$stats['revenue'] = $result->fetch_assoc()['total'] ?: 0;

// Chiffre d'affaires (période précédente)
$prev_rev_result = $conn->query("SELECT SUM(total) as total FROM commandes WHERE statut IN ('payee', 'expediee', 'livree')");
$stats['prev_revenue'] = $prev_rev_result->fetch_assoc()['total'] ?: 0;

// Clients (période)
$result = $conn->query("SELECT COUNT(DISTINCT utilisateur_id) as total FROM commandes WHERE $date_filter");
$stats['customers'] = $result->fetch_assoc()['total'];

// Produits vendus (période)
$result = $conn->query("
    SELECT SUM(cd.quantite) as total 
    FROM commande_details cd 
    JOIN commandes c ON cd.commande_id = c.id 
    WHERE c.statut IN ('payee', 'expediee', 'livree') AND $date_filter
");
$stats['products_sold'] = $result->fetch_assoc()['total'] ?: 0;

// Produits en stock faible
$result = $conn->query("SELECT COUNT(*) as total FROM produits WHERE quantite <= seuil_alerte AND quantite > 0");
$stats['low_stock'] = $result->fetch_assoc()['total'];

// Produits en rupture
$result = $conn->query("SELECT COUNT(*) as total FROM produits WHERE quantite = 0");
$stats['out_of_stock'] = $result->fetch_assoc()['total'];

// Commandes par statut
$orders_by_status = $conn->query("
    SELECT statut, COUNT(*) as count 
    FROM commandes 
    WHERE $date_filter
    GROUP BY statut
")->fetch_all(MYSQLI_ASSOC);

// Commandes récentes (dernières 10)
$recent_orders = $conn->query("
    SELECT c.*, u.nom as client_nom, u.email as client_email
    FROM commandes c 
    JOIN utilisateurs u ON c.utilisateur_id = u.id 
    ORDER BY c.date_commande DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Meilleurs produits (top 5)
$top_products = $conn->query("
    SELECT p.id, p.nom, p.image_url, SUM(cd.quantite) as total_vendu, 
           SUM(cd.quantite * cd.prix_unitaire) as chiffre_affaires
    FROM commande_details cd
    JOIN produits p ON cd.produit_id = p.id
    JOIN commandes c ON cd.commande_id = c.id
    WHERE c.statut IN ('payee', 'expediee', 'livree')
    GROUP BY p.id
    ORDER BY total_vendu DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Meilleurs clients (top 5)
$top_customers = $conn->query("
    SELECT u.id, u.nom, u.email, COUNT(c.id) as nb_commandes, 
           SUM(c.total) as total_depense
    FROM utilisateurs u
    LEFT JOIN commandes c ON u.id = c.utilisateur_id
    WHERE c.statut IN ('payee', 'expediee', 'livree')
    GROUP BY u.id
    ORDER BY total_depense DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Revenus par jour (30 derniers jours)
$revenue_by_day = $conn->query("
    SELECT 
        DATE(date_commande) as date,
        SUM(total) as revenue,
        COUNT(*) as orders
    FROM commandes 
    WHERE statut IN ('payee', 'expediee', 'livree')
        AND date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(date_commande)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// Préparer les données pour le graphique
$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];

foreach ($revenue_by_day as $day) {
    $chart_labels[] = date('d/m', strtotime($day['date']));
    $chart_revenue[] = $day['revenue'];
    $chart_orders[] = $day['orders'];
}

// Statistiques par catégorie
$categories_stats = $conn->query("
    SELECT c.nom, COUNT(p.id) as nb_produits, 
           SUM(CASE WHEN p.quantite = 0 THEN 1 ELSE 0 END) as rupture,
           AVG(p.prix) as prix_moyen
    FROM categories c
    LEFT JOIN produits p ON c.id = p.categorie_id
    GROUP BY c.id
    ORDER BY nb_produits DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tableau de bord</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportDashboard()">
                <i class="fas fa-download"></i> Exporter
            </button>
        </div>
        <div class="btn-group me-2">
            <a href="?period=today" class="btn btn-sm <?php echo $period == 'today' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                Aujourd'hui
            </a>
            <a href="?period=week" class="btn btn-sm <?php echo $period == 'week' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                7 jours
            </a>
            <a href="?period=month" class="btn btn-sm <?php echo $period == 'month' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                30 jours
            </a>
        </div>
        <span class="text-muted"><?php echo date('d/m/Y H:i'); ?></span>
    </div>
</div>

<!-- Cartes de statistiques -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Chiffre d'affaires
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($stats['revenue'], 0, ',', ' '); ?> FCFA
                        </div>
                        <div class="mt-2 mb-0 text-muted text-xs">
                            <?php 
                            $revenue_growth = calculateGrowth($stats['revenue'], $stats['prev_revenue']);
                            $revenue_class = $revenue_growth >= 0 ? 'text-success' : 'text-danger';
                            ?>
                            <span class="<?php echo $revenue_class; ?>">
                                <i class="fas fa-arrow-<?php echo $revenue_growth >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($revenue_growth); ?>%
                            </span>
                            vs période précédente
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Commandes
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['total_orders']; ?>
                        </div>
                        <div class="mt-2 mb-0 text-muted text-xs">
                            <?php 
                            $orders_growth = calculateGrowth($stats['total_orders'], $stats['prev_orders']);
                            $orders_class = $orders_growth >= 0 ? 'text-success' : 'text-danger';
                            ?>
                            <span class="<?php echo $orders_class; ?>">
                                <i class="fas fa-arrow-<?php echo $orders_growth >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($orders_growth); ?>%
                            </span>
                            vs période précédente
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Clients
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $stats['customers']; ?>
                        </div>
                        <div class="mt-2 mb-0 text-muted text-xs">
                            <span class="text-success">
                                <i class="fas fa-users"></i>
                            </span>
                            <?php echo $stats['products_sold']; ?> produits vendus
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Stock
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <span class="text-danger"><?php echo $stats['out_of_stock']; ?></span> /
                            <span class="text-warning"><?php echo $stats['low_stock']; ?></span>
                        </div>
                        <div class="mt-2 mb-0 text-muted text-xs">
                            <span class="text-danger">Rupture</span> / 
                            <span class="text-warning">Faible stock</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-boxes fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques et tableaux -->
<div class="row">
    <!-- Graphique des revenus -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line me-2"></i>Évolution des revenus (30 derniers jours)
                </h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="chartDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow">
                        <a class="dropdown-item" href="#" onclick="downloadChart()">
                            <i class="fas fa-download fa-sm fa-fw mr-2"></i>Télécharger
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="printChart()">
                            <i class="fas fa-print fa-sm fa-fw mr-2"></i>Imprimer
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Répartition des commandes -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-pie me-2"></i>Statut des commandes
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="ordersChart" height="200"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <?php foreach ($orders_by_status as $status): ?>
                    <span class="mr-3">
                        <i class="fas fa-circle" style="color: <?php echo getStatusColor($status['statut']); ?>"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $status['statut'])); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tableaux -->
<div class="row">
    <!-- Commandes récentes -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-shopping-cart me-2"></i>Commandes récentes
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="recentOrdersTable">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="details-commande.php?id=<?php echo $order['id']; ?>" class="font-weight-bold">
                                        <?php echo $order['numero_commande']; ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($order['client_nom']); ?></td>
                                <td><?php echo date('d/m H:i', strtotime($order['date_commande'])); ?></td>
                                <td class="font-weight-bold"><?php echo number_format($order['total'], 0, ',', ' '); ?> FCFA</td>
                                <td>
                                    <span class="badge badge-<?php echo getStatusBadge($order['statut']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['statut'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="details-commande.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="commandes.php" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="fas fa-list me-1"></i>Voir toutes les commandes
                </a>
            </div>
        </div>
    </div>

    <!-- Meilleurs produits -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-trophy me-2"></i>Top 5 des produits
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($top_products as $index => $product): ?>
                    <div class="list-group-item d-flex align-items-center">
                        <span class="badge bg-primary me-3">#<?php echo $index + 1; ?></span>
                        <?php if (!empty($product['image_url'])): ?>
                        <img src="../../<?php echo $product['image_url']; ?>" 
                             alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                             class="img-thumbnail me-3" 
                             style="width: 50px; height: 50px; object-fit: contain;">
                        <?php else: ?>
                        <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px;">
                            <i class="fas fa-laptop text-muted"></i>
                        </div>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?php echo htmlspecialchars($product['nom']); ?></h6>
                            <small class="text-muted">
                                <?php echo $product['total_vendu']; ?> vendus • 
                                <?php echo number_format($product['chiffre_affaires'], 0, ',', ' '); ?> FCFA
                            </small>
                        </div>
                        <a href="modifier-produit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques par catégorie -->
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-tags me-2"></i>Statistiques par catégorie
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th>Produits</th>
                                <th>En rupture</th>
                                <th>Prix moyen</th>
                                <th>Stock moyen</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories_stats as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['nom']); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $category['nb_produits']; ?></span>
                                </td>
                                <td>
                                    <?php if ($category['rupture'] > 0): ?>
                                    <span class="badge bg-danger"><?php echo $category['rupture']; ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo number_format($category['prix_moyen'] ?? 0, 0, ',', ' '); ?> FCFA
                                </td>
                                <td>
                                    <?php 
                                    // Calculer le stock moyen pour cette catégorie
                                    $stock_result = $conn->query("
                                        SELECT AVG(quantite) as avg_stock 
                                        FROM produits 
                                        WHERE categorie_id = (SELECT id FROM categories WHERE nom = '{$category['nom']}')
                                    ");
                                    $avg_stock = $stock_result->fetch_assoc()['avg_stock'] ?? 0;
                                    echo round($avg_stock, 1);
                                    ?>
                                </td>
                                <td>
                                    <a href="produits.php?category_id=<?php 
                                        $cat_id_result = $conn->query("SELECT id FROM categories WHERE nom = '{$category['nom']}'");
                                        $cat_id = $cat_id_result->fetch_assoc()['id'] ?? 0;
                                        echo $cat_id;
                                    ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt me-2"></i>Actions rapides
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <a href="ajouter-produit.php" class="btn btn-primary w-100 py-3">
                            <i class="fas fa-plus fa-2x mb-2"></i><br>
                            Ajouter produit
                        </a>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <a href="categories.php" class="btn btn-success w-100 py-3">
                            <i class="fas fa-tags fa-2x mb-2"></i><br>
                            Catégories
                        </a>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <a href="commandes.php" class="btn btn-warning w-100 py-3">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                            Commandes
                        </a>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <a href="statistiques.php" class="btn btn-info w-100 py-3">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                            Statistiques
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fonctions utilitaires pour le dashboard -->
<?php
function getStatusColor($status) {
    switch ($status) {
        case 'en_attente': return '#f6c23e'; // Jaune
        case 'payee': return '#36b9cc'; // Cyan
        case 'expediee': return '#4e73df'; // Bleu
        case 'livree': return '#1cc88a'; // Vert
        case 'annulee': return '#e74a3b'; // Rouge
        default: return '#858796'; // Gris
    }
}

function getStatusBadge($status) {
    switch ($status) {
        case 'en_attente': return 'warning';
        case 'payee': return 'info';
        case 'expediee': return 'primary';
        case 'livree': return 'success';
        case 'annulee': return 'danger';
        default: return 'secondary';
    }
}
?>

<!-- Scripts pour les graphiques -->
<script>
// Graphique des revenus
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Revenus (FCFA)',
            data: <?php echo json_encode($chart_revenue); ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.05)',
            pointBackgroundColor: '#4e73df',
            pointBorderColor: '#4e73df',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#4e73df',
            pointRadius: 3,
            pointHoverRadius: 5,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += new Intl.NumberFormat('fr-FR').format(context.raw) + ' FCFA';
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000) {
                            return (value / 1000000).toFixed(1) + 'M';
                        } else if (value >= 1000) {
                            return (value / 1000).toFixed(0) + 'K';
                        }
                        return value;
                    }
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'nearest'
        }
    }
});

// Graphique des statuts de commande
const ordersCtx = document.getElementById('ordersChart').getContext('2d');
const ordersChart = new Chart(ordersCtx, {
    type: 'doughnut',
    data: {
        labels: [
            <?php 
            $labels = [];
            foreach ($orders_by_status as $status) {
                $labels[] = "'" . ucfirst(str_replace('_', ' ', $status['statut'])) . "'";
            }
            echo implode(', ', $labels);
            ?>
        ],
        datasets: [{
            data: [
                <?php 
                $data = [];
                foreach ($orders_by_status as $status) {
                    $data[] = $status['count'];
                }
                echo implode(', ', $data);
                ?>
            ],
            backgroundColor: [
                <?php 
                $colors = [];
                foreach ($orders_by_status as $status) {
                    $colors[] = "'" . getStatusColor($status['statut']) . "'";
                }
                echo implode(', ', $colors);
                ?>
            ],
            hoverBackgroundColor: [
                <?php 
                $hoverColors = [];
                foreach ($orders_by_status as $status) {
                    $hoverColors[] = "'" . getStatusColor($status['statut']) . "'";
                }
                echo implode(', ', $hoverColors);
                ?>
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.raw + ' commande(s)';
                        return label;
                    }
                }
            }
        },
        cutout: '70%'
    }
});

// Fonctions utilitaires
function downloadChart() {
    const link = document.createElement('a');
    link.download = 'graphique-revenus.png';
    link.href = revenueChart.toBase64Image();
    link.click();
}

function printChart() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Graphique des revenus</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .chart-container { width: 800px; margin: 20px auto; }
            </style>
        </head>
        <body>
            <h1>Graphique des revenus - <?php echo SITE_NAME; ?></h1>
            <p>Généré le : <?php echo date('d/m/Y H:i'); ?></p>
            <div class="chart-container">
                <img src="${revenueChart.toBase64Image()}" style="width: 100%;">
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function exportDashboard() {
    // Récupérer les données du dashboard
    const dashboardData = {
        date_export: new Date().toISOString(),
        periode: '<?php echo $period; ?>',
        stats: {
            revenue: <?php echo $stats['revenue']; ?>,
            total_orders: <?php echo $stats['total_orders']; ?>,
            customers: <?php echo $stats['customers']; ?>,
            products_sold: <?php echo $stats['products_sold']; ?>
        },
        recent_orders: <?php echo json_encode($recent_orders); ?>,
        top_products: <?php echo json_encode($top_products); ?>
    };
    
    // Créer et télécharger le fichier JSON
    const dataStr = JSON.stringify(dashboardData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `dashboard-export-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    // Afficher une notification
    showNotification('success', 'Données exportées avec succès !');
}

// Initialiser DataTables pour le tableau des commandes récentes
$(document).ready(function() {
    $('#recentOrdersTable').DataTable({
        pageLength: 5,
        order: [[2, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
        },
        dom: '<"top"f>rt<"bottom"ilp><"clear">',
        responsive: true
    });
});

// Rafraîchissement automatique toutes les 5 minutes
setTimeout(function() {
    window.location.reload();
}, 300000); // 5 minutes
</script>

<?php require_once 'includes/admin-footer.php'; ?>