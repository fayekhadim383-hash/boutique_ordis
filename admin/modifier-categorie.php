<?php
require_once 'includes/admin-header.php';

$page_title = "Modifier une catégorie";

// Vérifier si l'ID est présent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de catégorie non spécifié";
    header('Location: categories.php');
    exit();
}

$category_id = intval($_GET['id']);

// Récupérer la catégorie
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

if (!$category) {
    $_SESSION['error'] = "Catégorie non trouvée";
    header('Location: categories.php');
    exit();
}

// Initialiser les variables
$nom = $category['nom'];
$description = $category['description'];
$image_url = $category['image_url'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = isset($_POST['nom']) ? sanitize($_POST['nom']) : $category['nom'];
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : $category['description'];
    
    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom de la catégorie est requis";
    }
    
    if (strlen($nom) > 100) {
        $errors[] = "Le nom ne peut pas dépasser 100 caractères";
    }
    
    // Vérifier si la catégorie existe déjà (sauf celle en cours de modification)
    if (empty($errors) && $nom !== $category['nom']) {
        $check_stmt = $conn->prepare("SELECT id FROM categories WHERE nom = ? AND id != ?");
        $check_stmt->bind_param("si", $nom, $category_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $errors[] = "Une catégorie avec ce nom existe déjà";
        }
        $check_stmt->close();
    }
    
    // Gérer l'upload d'image si nouvelle image fournie
    $current_image_url = $category['image_url'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/categories/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Vérifier le type de fichier
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF, WEBP";
        } else {
            // Vérifier la taille du fichier (max 2MB)
            if ($_FILES['image']['size'] > 2097152) {
                $errors[] = "L'image est trop volumineuse (max 2MB)";
            } else {
                // Générer un nom de fichier unique
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
                    // Supprimer l'ancienne image si elle existe
                    if ($current_image_url && file_exists('../../' . $current_image_url)) {
                        unlink('../../' . $current_image_url);
                    }
                    $image_url = 'uploads/categories/' . $file_name;
                } else {
                    $errors[] = "Erreur lors du téléchargement de l'image";
                }
            }
        }
    } else {
        // Conserver l'image actuelle
        $image_url = $current_image_url;
    }
    
    // Bouton pour supprimer l'image
    if (isset($_POST['delete_image']) && $current_image_url) {
        if (file_exists('../../' . $current_image_url)) {
            unlink('../../' . $current_image_url);
        }
        $image_url = null;
    }
    
    if (empty($errors)) {
        // Générer un nouveau slug si le nom a changé
        if ($nom !== $category['nom']) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom)));
            
            // Vérifier l'unicité du slug
            $slug_counter = 1;
            $original_slug = $slug;
            
            while (true) {
                $check_slug = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                $check_slug->bind_param("si", $slug, $category_id);
                $check_slug->execute();
                $check_slug->store_result();
                
                if ($check_slug->num_rows === 0) {
                    $check_slug->close();
                    break;
                }
                
                $check_slug->close();
                $slug = $original_slug . '-' . $slug_counter;
                $slug_counter++;
            }
        } else {
            $slug = $category['slug'];
        }
        
        // Mettre à jour la catégorie
        $stmt = $conn->prepare("UPDATE categories SET nom = ?, description = ?, slug = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssssi", $nom, $description, $slug, $image_url, $category_id);
        
        if ($stmt->execute()) {
            // Journaliser l'action
            if (isset($_SESSION['user_id'])) {
                $log_stmt = $conn->prepare("
                    INSERT INTO logs (utilisateur_id, action, details, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $action = "modification_categorie";
                $details = "Modification de la catégorie #$category_id: $nom";
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu';
                
                $log_stmt->bind_param("issss", $_SESSION['user_id'], $action, $details, $ip_address, $user_agent);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            $_SESSION['success'] = "Catégorie modifiée avec succès !";
            header('Location: categories.php');
            exit();
        } else {
            $errors[] = "Erreur lors de la modification de la catégorie : " . $stmt->error;
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        echo '<div class="alert alert-danger">';
        echo '<h5><i class="fas fa-exclamation-triangle"></i> Erreurs :</h5>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

// Pour l'affichage, utiliser les données POST si disponibles, sinon celles de la base
$display_data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $category;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Modifier la catégorie</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="categories.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-edit me-2"></i>
            Modification de la catégorie #<?php echo $category['id']; ?> - <?php echo htmlspecialchars($category['nom']); ?>
        </h6>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="categoryForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Nom de la catégorie -->
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom de la catégorie *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                               value="<?php echo htmlspecialchars($display_data['nom']); ?>"
                               maxlength="100">
                        <div class="form-text">Maximum 100 caractères</div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php 
                            echo htmlspecialchars($display_data['description']); 
                        ?></textarea>
                        <div class="form-text">Description détaillée de la catégorie (optionnel)</div>
                    </div>
                    
                    <!-- Slug (lecture seule) -->
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($category['slug']); ?>" readonly>
                        <div class="form-text">Le slug sera régénéré si le nom change</div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Image de la catégorie -->
                    <div class="mb-4">
                        <label for="image" class="form-label">Image de la catégorie</label>
                        
                        <?php if (!empty($category['image_url'])): ?>
                        <div class="text-center mb-3">
                            <img src="../<?php echo $category['image_url']; ?>" 
                                 alt="Image actuelle" 
                                 class="img-fluid rounded border" 
                                 style="max-height: 150px; max-width: 100%;">
                            <div class="mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="delete_image" name="delete_image">
                                    <label class="form-check-label text-danger" for="delete_image">
                                        <i class="fas fa-trash"></i> Supprimer cette image
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="border rounded p-3 text-center mb-2" 
                             style="background-color: #f8f9fa; min-height: 150px;"
                             id="imagePreviewContainer">
                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aperçu de l'image</p>
                            <img id="imagePreview" src="" class="img-fluid d-none" 
                                 style="max-height: 150px; max-width: 100%;">
                        </div>
                        <?php endif; ?>
                        
                        <input type="file" class="form-control" id="image" name="image" 
                               accept="image/*" onchange="previewImage(this, 'imagePreview')">
                        <div class="form-text">Laisser vide pour conserver l'image actuelle</div>
                    </div>
                    
                    <!-- Informations -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Informations</h6>
                        <ul class="mb-0 ps-3">
                            <li>ID: <?php echo $category['id']; ?></li>
                            <li>Créée le: <?php echo date('d/m/Y H:i', strtotime($category['created_at'])); ?></li>
                            <li>Modifiée le: <?php echo date('d/m/Y H:i', strtotime($category['updated_at'])); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Boutons -->
            <div class="d-flex justify-content-between">
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                
                <div class="btn-group">
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                    
                    <a href="../../pages/categorie.php?slug=<?php echo urlencode($category['slug']); ?>" 
                       target="_blank" 
                       class="btn btn-outline-success">
                        <i class="fas fa-eye"></i> Voir sur le site
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Aperçu de l'image
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const container = document.getElementById('imagePreviewContainer');
    
    if (container) {
        const placeholder = container.querySelector('i, p');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                if (placeholder) placeholder.classList.add('d-none');
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = '';
            preview.classList.add('d-none');
            if (placeholder) placeholder.classList.remove('d-none');
        }
    }
}

// Validation du formulaire
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    const nom = document.getElementById('nom').value.trim();
    
    if (nom.length > 100) {
        e.preventDefault();
        alert('Le nom ne peut pas dépasser 100 caractères');
        return false;
    }
    
    if (nom.length === 0) {
        e.preventDefault();
        alert('Le nom de la catégorie est requis');
        return false;
    }
    
    return true;
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>