<?php
require_once 'includes/admin-header.php';

$page_title = "Ajouter un produit";

// Récupérer les catégories
$categories = $conn->query("SELECT * FROM categories ORDER BY nom")->fetch_all(MYSQLI_ASSOC);

// Marques disponibles
$brands = ['Dell', 'HP', 'Lenovo', 'Apple', 'Asus', 'Acer', 'MSI', 'Microsoft', 'Samsung', 'Toshiba'];

// Processeurs disponibles
$processors = [
    'Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9',
    'AMD Ryzen 3', 'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9',
    'Apple M1', 'Apple M2', 'Apple M3'
];

// Initialiser les variables pour éviter les erreurs "Undefined variable"
$nom = $description = $marque = $processeur = $ram = $stockage = $ecran = '';
$carte_graphique = $systeme_exploitation = $poids = $autonomie = $ports = $wifi = $bluetooth = '';
$prix = $prix_promotion = $quantite = $seuil_alerte = '';
$categorie_id = 0;
$est_actif = 1;
$est_en_promotion = 0;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $nom = isset($_POST['nom']) ? sanitize($_POST['nom']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    $categorie_id = isset($_POST['categorie_id']) ? intval($_POST['categorie_id']) : 0;
    $marque = isset($_POST['marque']) ? sanitize($_POST['marque']) : '';
    $processeur = isset($_POST['processeur']) ? sanitize($_POST['processeur']) : '';
    $ram = isset($_POST['ram']) ? sanitize($_POST['ram']) : '';
    $stockage = isset($_POST['stockage']) ? sanitize($_POST['stockage']) : '';
    $ecran = isset($_POST['ecran']) ? sanitize($_POST['ecran']) : '';
    $carte_graphique = isset($_POST['carte_graphique']) ? sanitize($_POST['carte_graphique']) : '';
    $systeme_exploitation = isset($_POST['systeme_exploitation']) ? sanitize($_POST['systeme_exploitation']) : 'Windows 11';
    $poids = isset($_POST['poids']) && $_POST['poids'] !== '' ? floatval($_POST['poids']) : null;
    $autonomie = isset($_POST['autonomie']) ? sanitize($_POST['autonomie']) : '';
    $ports = isset($_POST['ports']) ? sanitize($_POST['ports']) : '';
    $wifi = isset($_POST['wifi']) ? sanitize($_POST['wifi']) : 'Wi-Fi 6';
    $bluetooth = isset($_POST['bluetooth']) ? sanitize($_POST['bluetooth']) : 'Bluetooth 5.2';
    $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
    $prix_promotion = isset($_POST['prix_promotion']) && $_POST['prix_promotion'] !== '' ? floatval($_POST['prix_promotion']) : null;
    $quantite = isset($_POST['quantite']) ? intval($_POST['quantite']) : 0;
    $seuil_alerte = isset($_POST['seuil_alerte']) ? intval($_POST['seuil_alerte']) : 5;
    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
    $est_en_promotion = isset($_POST['est_en_promotion']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom du produit est requis";
    }
    
    if (empty($description)) {
        $errors[] = "La description est requise";
    }
    
    if ($categorie_id <= 0) {
        $errors[] = "Veuillez sélectionner une catégorie";
    }
    
    if (empty($marque)) {
        $errors[] = "La marque est requise";
    }
    
    if (empty($processeur)) {
        $errors[] = "Le processeur est requis";
    }
    
    if (empty($ram)) {
        $errors[] = "La mémoire RAM est requise";
    }
    
    if (empty($stockage)) {
        $errors[] = "Le stockage est requis";
    }
    
    if (empty($ecran)) {
        $errors[] = "L'écran est requis";
    }
    
    if ($prix <= 0) {
        $errors[] = "Le prix doit être supérieur à 0";
    }
    
    if ($quantite < 0) {
        $errors[] = "La quantité ne peut pas être négative";
    }
    
    // Gérer l'upload d'image principale
    $image_url = 'uploads/produits/default.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/produits/';
        
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
                    $image_url = 'uploads/produits/' . $file_name;
                } else {
                    $errors[] = "Erreur lors du téléchargement de l'image";
                }
            }
        }
    }
    
    // Images supplémentaires
    //$image_url2 = null;
    //$image_url3 = null;

    $image_url2 = 'uploads/produits/default.jpg';
    $image_url3 = 'uploads/produits/default.jpg';
    
    // Gérer la deuxième image
    if (empty($errors) && isset($_FILES['image2']) && $_FILES['image2']['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($_FILES['image2']['tmp_name']);
        if (in_array($file_type, $allowed_types)) {
            if ($_FILES['image2']['size'] <= 2097152) {
                $file_extension = strtolower(pathinfo($_FILES['image2']['name'], PATHINFO_EXTENSION));
                $file_name = uniqid() . '_' . time() . '_2.' . $file_extension;
                $upload_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image2']['tmp_name'], $upload_file)) {
                    $image_url2 = 'uploads/produits/' . $file_name;
                }
            }
        }
    }
    
    // Gérer la troisième image
    if (empty($errors) && isset($_FILES['image3']) && $_FILES['image3']['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($_FILES['image3']['tmp_name']);
        if (in_array($file_type, $allowed_types)) {
            if ($_FILES['image3']['size'] <= 2097152) {
                $file_extension = strtolower(pathinfo($_FILES['image3']['name'], PATHINFO_EXTENSION));
                $file_name = uniqid() . '_' . time() . '_3.' . $file_extension;
                $upload_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image3']['tmp_name'], $upload_file)) {
                    $image_url3 = 'uploads/produits/' . $file_name;
                }
            }
        }
    }
    
    // Générer un slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom)));
    
    if (empty($errors)) {
        // Définir des valeurs par défaut pour les champs optionnels s'ils sont vides
        if (empty($autonomie)) $autonomie = null;
        if (empty($ports)) $ports = null;
        if (empty($carte_graphique)) $carte_graphique = null;
        
        // Requête SQL simplifiée pour éviter l'erreur de colonnes NULL
        $stmt = $conn->prepare("
            INSERT INTO produits (
                nom, slug, description, categorie_id, marque, processeur, 
                ram, stockage, ecran, prix, quantite, seuil_alerte, image_url,
                est_actif, est_en_promotion, carte_graphique, systeme_exploitation,
                poids, autonomie, ports, wifi, bluetooth, prix_promotion,
                image_url2, image_url3
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt) {
            // Préparer les paramètres
            $stmt->bind_param(
                "sssisssssdiiissiisssssssss",
                $nom,
                $slug,
                $description,
                $categorie_id,
                $marque,
                $processeur,
                $ram,
                $stockage,
                $ecran,
                $prix,
                $quantite,
                $seuil_alerte,
                $image_url,
                $est_actif,
                $est_en_promotion,
                $carte_graphique,
                $systeme_exploitation,
                $poids,
                $autonomie,
                $ports,
                $wifi,
                $bluetooth,
                $prix_promotion,
                $image_url2,
                $image_url3
            );
            
            if ($stmt->execute()) {
                $product_id = $stmt->insert_id;
                
                $_SESSION['success'] = "Produit ajouté avec succès ! ID: $product_id";
                
                // Redirection selon le bouton cliqué
                if (isset($_POST['save_and_add'])) {
                    // Réinitialiser les variables pour un nouvel ajout
                    $nom = $description = $marque = $processeur = $ram = $stockage = $ecran = '';
                    $carte_graphique = $systeme_exploitation = $poids = $autonomie = $ports = '';
                    $wifi = 'Wi-Fi 6';
                    $bluetooth = 'Bluetooth 5.2';
                    $prix = $prix_promotion = '';
                    $quantite = 0;
                    $seuil_alerte = 5;
                    $categorie_id = 0;
                    $est_actif = 1;
                    $est_en_promotion = 0;
                    
                    $success_message = "Produit ajouté avec succès ! Vous pouvez ajouter un autre produit.";
                } else {
                    header('Location: produits.php');
                    exit();
                }
            } else {
                $errors[] = "Erreur lors de l'ajout du produit : " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Erreur de préparation de la requête : " . $conn->error;
        }
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
    <h1 class="h2">Ajouter un produit</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="produits.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nouveau produit</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data" id="productForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Informations générales -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h5 class="mb-3"><i class="fas fa-info-circle text-primary me-2"></i>Informations générales</h5>
                    
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du produit *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                               value="<?php echo htmlspecialchars($nom); ?>">
                        <div class="form-text">Nom complet du produit</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php 
                            echo htmlspecialchars($description);
                        ?></textarea>
                        <div class="form-text">Description détaillée du produit</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="categorie_id" class="form-label">Catégorie *</label>
                            <select class="form-select" id="categorie_id" name="categorie_id" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                    <?php echo ($categorie_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="marque" class="form-label">Marque *</label>
                            <select class="form-select" id="marque" name="marque" required>
                                <option value="">Sélectionner une marque</option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand; ?>"
                                    <?php echo ($marque == $brand) ? 'selected' : ''; ?>>
                                    <?php echo $brand; ?>
                                </option>
                                <?php endforeach; ?>
                                <option value="autre">Autre...</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" id="autre_marque" 
                                   placeholder="Autre marque">
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <h5 class="mb-3"><i class="fas fa-image text-primary me-2"></i>Images</h5>
                    
                    <!-- Image principale -->
                    <div class="mb-4">
                        <label for="image" class="form-label">Image principale *</label>
                        <div class="border rounded p-3 text-center mb-2" 
                             style="background-color: #f8f9fa; min-height: 200px;"
                             id="imagePreviewContainer">
                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aperçu de l'image</p>
                            <img id="imagePreview" src="" class="img-fluid d-none" 
                                 style="max-height: 150px;">
                        </div>
                        <input type="file" class="form-control" id="image" name="image" 
                               accept="image/*" onchange="previewImage(this, 'imagePreview')">
                        <div class="form-text">Format: JPG, PNG, GIF, WEBP. Max: 2MB</div>
                    </div>
                    
                    <!-- Images supplémentaires -->
                    <div class="row">
                        <div class="col-6">
                            <label for="image2" class="form-label">Image 2</label>
                            <input type="file" class="form-control" id="image2" name="image2" accept="image/*">
                        </div>
                        <div class="col-6">
                            <label for="image3" class="form-label">Image 3</label>
                            <input type="file" class="form-control" id="image3" name="image3" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Caractéristiques techniques -->
            <h5 class="mb-3"><i class="fas fa-microchip text-primary me-2"></i>Caractéristiques techniques</h5>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="processeur" class="form-label">Processeur *</label>
                    <select class="form-select" id="processeur" name="processeur" required>
                        <option value="">Sélectionner un processeur</option>
                        <?php foreach ($processors as $processor): ?>
                        <option value="<?php echo $processor; ?>"
                            <?php echo ($processeur == $processor) ? 'selected' : ''; ?>>
                            <?php echo $processor; ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="autre">Autre...</option>
                    </select>
                    <input type="text" class="form-control mt-2 d-none" id="autre_processeur" 
                           placeholder="Autre processeur">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="ram" class="form-label">Mémoire RAM *</label>
                    <select class="form-select" id="ram" name="ram" required>
                        <option value="">Sélectionner la RAM</option>
                        <option value="4 Go" <?php echo ($ram == '4 Go') ? 'selected' : ''; ?>>4 Go</option>
                        <option value="8 Go" <?php echo ($ram == '8 Go') ? 'selected' : ''; ?>>8 Go</option>
                        <option value="16 Go" <?php echo ($ram == '16 Go') ? 'selected' : ''; ?>>16 Go</option>
                        <option value="32 Go" <?php echo ($ram == '32 Go') ? 'selected' : ''; ?>>32 Go</option>
                        <option value="64 Go" <?php echo ($ram == '64 Go') ? 'selected' : ''; ?>>64 Go</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="stockage" class="form-label">Stockage *</label>
                    <select class="form-select" id="stockage" name="stockage" required>
                        <option value="">Sélectionner le stockage</option>
                        <option value="256 Go SSD" <?php echo ($stockage == '256 Go SSD') ? 'selected' : ''; ?>>256 Go SSD</option>
                        <option value="512 Go SSD" <?php echo ($stockage == '512 Go SSD') ? 'selected' : ''; ?>>512 Go SSD</option>
                        <option value="1 To SSD" <?php echo ($stockage == '1 To SSD') ? 'selected' : ''; ?>>1 To SSD</option>
                        <option value="2 To SSD" <?php echo ($stockage == '2 To SSD') ? 'selected' : ''; ?>>2 To SSD</option>
                        <option value="1 To HDD + 256 Go SSD" <?php echo ($stockage == '1 To HDD + 256 Go SSD') ? 'selected' : ''; ?>>1 To HDD + 256 Go SSD</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="ecran" class="form-label">Écran *</label>
                    <input type="text" class="form-control" id="ecran" name="ecran" required
                           value="<?php echo htmlspecialchars($ecran); ?>"
                           placeholder="Ex: 15.6\" FHD 144Hz">
                    <div class="form-text">Taille, résolution, taux de rafraîchissement</div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="carte_graphique" class="form-label">Carte graphique</label>
                    <input type="text" class="form-control" id="carte_graphique" name="carte_graphique"
                           value="<?php echo htmlspecialchars($carte_graphique); ?>"
                           placeholder="Ex: NVIDIA RTX 4060 8 Go">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="systeme_exploitation" class="form-label">Système d'exploitation</label>
                    <input type="text" class="form-control" id="systeme_exploitation" name="systeme_exploitation"
                           value="<?php echo htmlspecialchars($systeme_exploitation); ?>">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <label for="poids" class="form-label">Poids (kg)</label>
                    <input type="number" class="form-control" id="poids" name="poids" step="0.01"
                           value="<?php echo htmlspecialchars($poids); ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="autonomie" class="form-label">Autonomie</label>
                    <input type="text" class="form-control" id="autonomie" name="autonomie"
                           value="<?php echo htmlspecialchars($autonomie); ?>"
                           placeholder="Ex: 8 heures">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="wifi" class="form-label">Wi-Fi</label>
                    <input type="text" class="form-control" id="wifi" name="wifi"
                           value="<?php echo htmlspecialchars($wifi); ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="bluetooth" class="form-label">Bluetooth</label>
                    <input type="text" class="form-control" id="bluetooth" name="bluetooth"
                           value="<?php echo htmlspecialchars($bluetooth); ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="ports" class="form-label">Ports</label>
                <textarea class="form-control" id="ports" name="ports" rows="2"><?php 
                    echo htmlspecialchars($ports);
                ?></textarea>
                <div class="form-text">Liste des ports (séparés par des virgules)</div>
            </div>
            
            <hr class="my-4">
            
            <!-- Prix et stock -->
            <h5 class="mb-3"><i class="fas fa-dollar-sign text-primary me-2"></i>Prix et stock</h5>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="prix" class="form-label">Prix (FCFA) *</label>
                    <input type="number" class="form-control" id="prix" name="prix" 
                           step="0.01" min="0" required
                           value="<?php echo htmlspecialchars($prix); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="prix_promotion" class="form-label">Prix promotionnel (FCFA)</label>
                    <input type="number" class="form-control" id="prix_promotion" name="prix_promotion"
                           step="0.01" min="0"
                           value="<?php echo htmlspecialchars($prix_promotion); ?>">
                    <div class="form-text">Laissez vide si pas de promotion</div>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="quantite" class="form-label">Quantité *</label>
                    <input type="number" class="form-control" id="quantite" name="quantite" 
                           min="0" required
                           value="<?php echo htmlspecialchars($quantite); ?>">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="seuil_alerte" class="form-label">Seuil d'alerte</label>
                    <input type="number" class="form-control" id="seuil_alerte" name="seuil_alerte" 
                           min="1"
                           value="<?php echo htmlspecialchars($seuil_alerte); ?>">
                    <div class="form-text">Alerte quand stock ≤ ce nombre</div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Options -->
            <h5 class="mb-3"><i class="fas fa-cog text-primary me-2"></i>Options</h5>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="est_actif" name="est_actif" 
                               value="1" <?php echo $est_actif ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="est_actif">
                            <strong>Produit actif</strong>
                        </label>
                        <div class="form-text">Le produit sera visible sur le site</div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="est_en_promotion" name="est_en_promotion" 
                               value="1" <?php echo $est_en_promotion ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="est_en_promotion">
                            <strong>En promotion</strong>
                        </label>
                        <div class="form-text">Afficher le badge "PROMO"</div>
                    </div>
                </div>
            </div>
            
            <!-- Boutons -->
            <div class="d-flex justify-content-between">
                <a href="produits.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                
                <div class="btn-group">
                    <button type="submit" name="save" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                    
                    <button type="submit" name="save_and_add" class="btn btn-success">
                        <i class="fas fa-plus"></i> Enregistrer et ajouter un autre
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

// Gérer "Autre" pour la marque et le processeur
document.addEventListener('DOMContentLoaded', function() {
    const marqueSelect = document.getElementById('marque');
    const autreMarqueInput = document.getElementById('autre_marque');
    const processeurSelect = document.getElementById('processeur');
    const autreProcesseurInput = document.getElementById('autre_processeur');
    
    // Marque
    marqueSelect.addEventListener('change', function() {
        if (this.value === 'autre') {
            autreMarqueInput.classList.remove('d-none');
            autreMarqueInput.required = true;
        } else {
            autreMarqueInput.classList.add('d-none');
            autreMarqueInput.required = false;
        }
    });
    
    // Processeur
    processeurSelect.addEventListener('change', function() {
        if (this.value === 'autre') {
            autreProcesseurInput.classList.remove('d-none');
            autreProcesseurInput.required = true;
        } else {
            autreProcesseurInput.classList.add('d-none');
            autreProcesseurInput.required = false;
        }
    });
    
    // Si "Autre" est sélectionné au chargement
    if (marqueSelect.value === 'autre') {
        autreMarqueInput.classList.remove('d-none');
        autreMarqueInput.required = true;
    }
    
    if (processeurSelect.value === 'autre') {
        autreProcesseurInput.classList.remove('d-none');
        autreProcesseurInput.required = true;
    }
    
    // Validation du formulaire
    document.getElementById('productForm').addEventListener('submit', function(e) {
        // Si "Autre" est sélectionné, copier la valeur dans le select
        if (marqueSelect.value === 'autre' && autreMarqueInput.value) {
            marqueSelect.options[marqueSelect.selectedIndex].text = autreMarqueInput.value;
            marqueSelect.value = autreMarqueInput.value;
        }
        
        if (processeurSelect.value === 'autre' && autreProcesseurInput.value) {
            processeurSelect.options[processeurSelect.selectedIndex].text = autreProcesseurInput.value;
            processeurSelect.value = autreProcesseurInput.value;
        }
        
        // Validation supplémentaire
        const prix = document.getElementById('prix').value;
        if (parseFloat(prix) <= 0) {
            e.preventDefault();
            alert('Le prix doit être supérieur à 0');
            return false;
        }
        
        const quantite = document.getElementById('quantite').value;
        if (parseInt(quantite) < 0) {
            e.preventDefault();
            alert('La quantité ne peut pas être négative');
            return false;
        }
    });
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>