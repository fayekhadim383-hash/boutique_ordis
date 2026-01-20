<?php
// Démarrer le buffer de sortie
ob_start();

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration et connexion à la base de données
require_once __DIR__ . '/../includes/config.php';

// Vérifier l'accès admin
require_once 'admin-auth.php';

// Définir le titre de la page si non défini
if (!isset($page_title)) {
    $page_title = "Administration";
}

// Définir le chemin racine du site
$root_url = dirname(dirname($_SERVER['PHP_SELF']));
$root_url = str_replace('\\', '/', $root_url);
if ($root_url == '/') {
    $root_url = '';
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Administration - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $root_url; ?>/assets/css/admin.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $root_url; ?>/assets/images/favicon.ico">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #0d6efd;
        }
        
        body {
            font-size: .875rem;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background: linear-gradient(180deg, #212529 0%, #343a40 100%);
            width: var(--sidebar-width);
            transition: all 0.3s;
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            font-weight: 500;
            color: #adb5bd;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
            min-width: 20px;
        }
        
        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
            color: #6c757d;
            padding: 0 1rem;
            margin-top: 1rem;
        }
        
        main {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .075);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, .125);
        }
        
        .stat-card {
            border-radius: 10px;
            overflow: hidden;
            color: white;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-card .card-footer {
            background-color: rgba(0, 0, 0, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
        }
        
        .table th {
            font-weight: 600;
            border-top: none;
        }
        
        .badge-admin {
            padding: 0.35em 0.65em;
            font-weight: 500;
        }
        
        .product-img-admin {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            main {
                margin-left: 0;
            }
            
            .sidebar.show {
                width: var(--sidebar-width);
            }
        }

        /*-------------------------------------*/
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        
        .product-img-admin {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 4px;
        }
        
        .badge {
            font-size: 0.75em;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Style pour les images dans les formulaires */
        .image-preview-container {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Navigation supérieure -->
    <nav class="navbar navbar-dark bg-dark fixed-top flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
            <i class="fas fa-cog me-2"></i>
            <span class="d-none d-md-inline">Admin - <?php echo SITE_NAME; ?></span>
            <span class="d-md-none">Admin</span>
        </a>
        
        <button class="navbar-toggler d-md-none position-absolute d-md-none" 
                type="button" 
                style="right: 10px; top: 10px;"
                onclick="toggleSidebar()">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['user_nom']); ?>
                    <span class="badge bg-warning ms-1">Admin</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="<?php echo $root_url; ?>/index.php" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>Voir le site
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?php echo $root_url; ?>/pages/compte.php">
                            <i class="fas fa-user me-2"></i>Mon profil
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo $root_url; ?>/pages/deconnexion.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
    
    <!-- Sidebar -->
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <?php 
                    // Inclure la sidebar avec le bon chemin
                    include 'admin-sidebar.php'; 
                    ?>
                </div>
            </nav>
            
            <!-- Contenu principal -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['warning'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>