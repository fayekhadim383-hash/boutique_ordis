<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérification de l'authentification et des permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialisation des variables
$error = '';
$success = '';
$users = [];
$search = '';
$role_filter = '';

// Gestion de la recherche
if (isset($_GET['search'])) {
    $search = htmlspecialchars($_GET['search']);
}

if (isset($_GET['role'])) {
    $role_filter = $_GET['role'];
}

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    // Empêcher la suppression de soi-même
    if ($user_id == $_SESSION['user_id']) {
        $error = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        try {
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $user_id]);
            
            $success = "Utilisateur supprimé avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }
}

// Gestion du changement de statut
if (isset($_GET['toggle_status'])) {
    $user_id = intval($_GET['toggle_status']);
    
    try {
        // Récupérer le statut actuel
        $sql = "SELECT is_active FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $user_id]);
        $current_status = $stmt->fetchColumn();
        
        // Inverser le statut
        $new_status = $current_status ? 0 : 1;
        
        $sql = "UPDATE users SET is_active = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'status' => $new_status,
            'id' => $user_id
        ]);
        
        $action = $new_status ? "activé" : "désactivé";
        $success = "Utilisateur $action avec succès.";
    } catch (PDOException $e) {
        $error = "Erreur lors du changement de statut : " . $e->getMessage();
    }
}

// Récupération des utilisateurs avec filtres
try {
    $sql = "SELECT u.*, COUNT(c.id) as commandes_count 
            FROM users u 
            LEFT JOIN commandes c ON u.id = c.user_id";
    
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(u.username LIKE :search OR u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if (!empty($role_filter) && $role_filter !== 'all') {
        $where[] = "u.role = :role";
        $params['role'] = $role_filter;
    }
    
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des utilisateurs : " . $e->getMessage();
}

// Récupération des statistiques
try {
    $stats_sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
                    SUM(CASE WHEN role = 'moderator' THEN 1 ELSE 0 END) as moderator_count,
                    DATE(created_at) as date,
                    COUNT(*) as daily_registrations
                  FROM users 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";
    
    $stats_stmt = $pdo->query($stats_sql);
    $stats_data = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur statistiques : " . $e->getMessage();
}

// Inclure l'en-tête
include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Messages d'alerte -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- En-tête de page -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestion des Utilisateurs</h1>
        <a href="ajouter-utilisateur.php" class="btn btn-primary">
            <i class="fas fa-user-plus fa-sm"></i> Ajouter un utilisateur
        </a>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Utilisateurs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($users); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300 stat-icon stat-icon-primary"></i>
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
                                Utilisateurs Actifs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $active_count = array_reduce($users, function($carry, $user) {
                                    return $carry + ($user['is_active'] ? 1 : 0);
                                }, 0);
                                echo $active_count;
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300 stat-icon stat-icon-success"></i>
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
                                Administrateurs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $admin_count = array_reduce($users, function($carry, $user) {
                                    return $carry + ($user['role'] === 'admin' ? 1 : 0);
                                }, 0);
                                echo $admin_count;
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-crown fa-2x text-gray-300 stat-icon stat-icon-info"></i>
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
                                Nouveaux (7 jours)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo count($stats_data); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300 stat-icon stat-icon-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Rechercher par nom, email, prénom..." 
                           value="<?php echo $search; ?>">
                </div>
                <div class="col-md-4">
                    <select class="form-control" name="role">
                        <option value="all">Tous les rôles</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                        <option value="moderator" <?php echo $role_filter == 'moderator' ? 'selected' : ''; ?>>Modérateur</option>
                        <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des utilisateurs -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Liste des utilisateurs</h6>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimer
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Exporter
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="usersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Commandes</th>
                            <th>Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Aucun utilisateur trouvé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo $user['email']; ?>">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        switch($user['role']) {
                                            case 'admin': echo 'danger'; break;
                                            case 'moderator': echo 'warning'; break;
                                            default: echo 'primary';
                                        }
                                    ?>">
                                        <?php 
                                        $role_names = [
                                            'admin' => 'Administrateur',
                                            'moderator' => 'Modérateur',
                                            'user' => 'Utilisateur'
                                        ];
                                        echo $role_names[$user['role']] ?? $user['role'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?php echo $user['commandes_count']; ?></span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="voir-utilisateur.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="modifier-utilisateur.php?id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="?toggle_status=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-<?php echo $user['is_active'] ? 'secondary' : 'success'; ?>"
                                               title="<?php echo $user['is_active'] ? 'Désactiver' : 'Activer'; ?>"
                                               onclick="return confirm('Confirmer le changement de statut ?')">
                                                <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <a href="?delete=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               title="Supprimer"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Action non autorisée">
                                                <i class="fas fa-user"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Précédent</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Suivant</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Graphique des inscriptions -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Inscriptions des 7 derniers jours</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="registrationsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition par rôle -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Répartition par rôle</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="rolesChart"></canvas>
                    </div>
                    <div class="mt-4 text-center">
                        <div class="chart-legend justify-content-center">
                            <div class="legend-item">
                                <span class="legend-color" style="background-color:#4e73df"></span>
                                <span>Utilisateurs (<?php echo $user_count ?? 0; ?>)</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color:#1cc88a"></span>
                                <span>Modérateurs (<?php echo $moderator_count ?? 0; ?>)</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color:#e74a3b"></span>
                                <span>Administrateurs (<?php echo $admin_count ?? 0; ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Scripts pour les graphiques -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique des inscriptions
const ctx1 = document.getElementById('registrationsChart').getContext('2d');
const registrationsChart = new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($stats_data, 'date')); ?>,
        datasets: [{
            label: 'Inscriptions',
            data: <?php echo json_encode(array_column($stats_data, 'daily_registrations')); ?>,
            backgroundColor: 'rgba(78, 115, 223, 0.05)',
            borderColor: 'rgba(78, 115, 223, 1)',
            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
            fill: true
        }]
    },
    options: {
        maintainAspectRatio: false,
        scales: {
            x: {
                grid: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Graphique circulaire des rôles
const ctx2 = document.getElementById('rolesChart').getContext('2d');
const rolesChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Utilisateurs', 'Modérateurs', 'Administrateurs'],
        datasets: [{
            data: [
                <?php echo $user_count ?? 0; ?>,
                <?php echo $moderator_count ?? 0; ?>,
                <?php echo $admin_count ?? 0; ?>
            ],
            backgroundColor: [
                '#4e73df',
                '#1cc88a',
                '#e74a3b'
            ],
            hoverBackgroundColor: [
                '#2e59d9',
                '#17a673',
                '#be2617'
            ],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                display: false
            }
        }
    },
});

// Fonction d'export Excel
function exportToExcel() {
    const table = document.getElementById('usersTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Exclure la colonne Actions
            if (j !== 8) {
                row.push(cols[j].innerText);
            }
        }
        
        csv.push(row.join(","));
    }
    
    const csvString = csv.join("\n");
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    
    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, 'utilisateurs.csv');
    } else {
        link.href = URL.createObjectURL(blob);
        link.download = "utilisateurs_" + new Date().toISOString().split('T')[0] + ".csv";
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Confirmation avant suppression
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.btn-danger');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                e.preventDefault();
            }
        });
    });
});
</script>