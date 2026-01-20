<?php
require_once 'includes/admin-header.php';

$page_title = "Ajouter une catégorie";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = isset($_POST['nom']) ? sanitize($_POST['nom']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    
    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom de la catégorie est requis";
    }
    
    if (strlen($nom) > 100) {
        $errors[] = "Le nom ne peut pas dépasser 100 caractères";
    }
    
    // Vérifier si la catégorie existe déjà
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT id FROM categories WHERE nom = ?");
        $check_stmt->bind_param("s", $nom);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $errors[] = "Une catégorie avec ce nom existe déjà";
        }
        $check_stmt->close();
    }
    
    // Gérer l'upload d'image si fournie
    $image_url = null;
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
                    $image_url = 'uploads/categories/' . $file_name;
                } else {
                    $errors[] = "Erreur lors du téléchargement de l'image";
                }
            }
        }
    }
    
    if (empty($errors)) {
        // Générer un slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom)));
        
        // Vérifier l'unicité du slug
        $slug_counter = 1;
        $original_slug = $slug;
        
        while (true) {
            $check_slug = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
            $check_slug->bind_param("s", $slug);
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
        
        // Insérer la catégorie
        $stmt = $conn->prepare("INSERT INTO categories (nom, description, slug, image_url) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nom, $description, $slug, $image_url);
        
        if ($stmt->execute()) {
            $category_id = $stmt->insert_id;
            
            // Journaliser l'action
            if (isset($_SESSION['user_id'])) {
                $log_stmt = $conn->prepare("
                    INSERT INTO logs (utilisateur_id, action, details, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $action = "ajout_categorie";
                $details = "Ajout de la catégorie #$category_id: $nom";
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu';
                
                $log_stmt->bind_param("issss", $_SESSION['user_id'], $action, $details, $ip_address, $user_agent);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            $_SESSION['success'] = "Catégorie ajoutée avec succès !";
            
            // Redirection selon le bouton cliqué
            if (isset($_POST['save_and_add'])) {
                // Réinitialiser les variables pour un nouvel ajout
                $nom = $description = '';
                $success_message = "Catégorie ajoutée avec succès ! Vous pouvez ajouter une autre catégorie.";
            } else {
                header('Location: categories.php');
                exit();
            }
        } else {
            $errors[] = "Erreur lors de l'ajout de la catégorie : " . $stmt->error;
        }
        $stmt->close();
    }
}

// Afficher les erreurs ou le succès
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

if (isset($success_message)) {
    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($success_message);
    echo '</div>';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Ajouter une catégorie</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="categories.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nouvelle catégorie</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data" id="categoryForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Nom de la catégorie -->
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom de la catégorie *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                               value="<?php echo isset($nom) ? htmlspecialchars($nom) : ''; ?>"
                               maxlength="100">
                        <div class="form-text">Maximum 100 caractères</div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php 
                            echo isset($description) ? htmlspecialchars($description) : '';
                        ?></textarea>
                        <div class="form-text">Description détaillée de la catégorie (optionnel)</div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Image de la catégorie -->
                    <div class="mb-4">
                        <label for="image" class="form-label">Image de la catégorie</label>
                        <div class="border rounded p-3 text-center mb-2" 
                             style="background-color: #f8f9fa; min-height: 200px;"
                             id="imagePreviewContainer">
                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aperçu de l'image</p>
                            <img id="imagePreview" src="" class="img-fluid d-none" 
                                 style="max-height: 150px; max-width: 100%;">
                        </div>
                        <input type="file" class="form-control" id="image" name="image" 
                               accept="image/*" onchange="previewImage(this, 'imagePreview')">
                        <div class="form-text">Format: JPG, PNG, GIF, WEBP. Max: 2MB</div>
                    </div>
                    
                    <!-- Informations -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Informations</h6>
                        <ul class="mb-0 ps-3">
                            <li>Le slug sera généré automatiquement</li>
                            <li>La catégorie sera visible sur le site immédiatement</li>
                            <li>L'image est facultative</li>
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
                    <button type="submit" name="save" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    
                    <button type="submit" name="save_and_add" class="btn btn-success">
                        <i class="fas fa-plus"></i> Enregistrer et ajouter une autre
                    </button>
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