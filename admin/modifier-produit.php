<?php
require_once 'includes/admin-header.php';

$page_title = "Modifier un produit";

// Vérifier si l'ID est présent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de produit non spécifié";
    header('Location: produits.php');
    exit();
}

$product_id = intval($_GET['id']);

// Récupérer le produit
$stmt = $conn->prepare("SELECT * FROM produits WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['error'] = "Produit non trouvé";
    header('Location: produits.php');
    exit();
}

// Récupérer les catégories
$categories = $conn->query("SELECT * FROM categories ORDER BY nom")->fetch_all(MYSQLI_ASSOC);

// Initialiser les variables
$nom = $description = $marque = $processeur = $ram = $stockage = $ecran = '';
$carte_graphique = $systeme_exploitation = $poids = $autonomie = $ports = $wifi = $bluetooth = '';
$prix = $prix_promotion = $quantite = $seuil_alerte = '';
$categorie_id = 0;
$est_actif = 1;
$est_en_promotion = 0;

// Initialiser avec les valeurs du produit
if ($product) {
    $nom = $product['nom'];
    $description = $product['description'];
    $categorie_id = $product['categorie_id'];
    $marque = $product['marque'];
    $processeur = $product['processeur'];
    $ram = $product['ram'];
    $stockage = $product['stockage'];
    $ecran = $product['ecran'];
    $carte_graphique = $product['carte_graphique'];
    $systeme_exploitation = $product['systeme_exploitation'];
    $poids = $product['poids'];
    $autonomie = $product['autonomie'];
    $ports = $product['ports'];
    $wifi = $product['wifi'];
    $bluetooth = $product['bluetooth'];
    $prix = $product['prix'];
    $prix_promotion = $product['prix_promotion'];
    $quantite = $product['quantite'];
    $seuil_alerte = $product['seuil_alerte'];
    $est_actif = $product['est_actif'];
    $est_en_promotion = $product['est_en_promotion'];
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $nom = isset($_POST['nom']) ? sanitize($_POST['nom']) : $product['nom'];
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : $product['description'];
    $categorie_id = isset($_POST['categorie_id']) ? intval($_POST['categorie_id']) : $product['categorie_id'];
    $marque = isset($_POST['marque']) ? sanitize($_POST['marque']) : $product['marque'];
    $processeur = isset($_POST['processeur']) ? sanitize($_POST['processeur']) : $product['processeur'];
    $ram = isset($_POST['ram']) ? sanitize($_POST['ram']) : $product['ram'];
    $stockage = isset($_POST['stockage']) ? sanitize($_POST['stockage']) : $product['stockage'];
    $ecran = isset($_POST['ecran']) ? sanitize($_POST['ecran']) : $product['ecran'];
    $carte_graphique = isset($_POST['carte_graphique']) ? sanitize($_POST['carte_graphique']) : $product['carte_graphique'];
    $systeme_exploitation = isset($_POST['systeme_exploitation']) ? sanitize($_POST['systeme_exploitation']) : $product['systeme_exploitation'];
    $poids = isset($_POST['poids']) && $_POST['poids'] !== '' ? floatval($_POST['poids']) : $product['poids'];
    $autonomie = isset($_POST['autonomie']) ? sanitize($_POST['autonomie']) : $product['autonomie'];
    $ports = isset($_POST['ports']) ? sanitize($_POST['ports']) : $product['ports'];
    $wifi = isset($_POST['wifi']) ? sanitize($_POST['wifi']) : $product['wifi'];
    $bluetooth = isset($_POST['bluetooth']) ? sanitize($_POST['bluetooth']) : $product['bluetooth'];
    $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : $product['prix'];
    $prix_promotion = isset($_POST['prix_promotion']) && $_POST['prix_promotion'] !== '' ? floatval($_POST['prix_promotion']) : $product['prix_promotion'];
    $quantite = isset($_POST['quantite']) ? intval($_POST['quantite']) : $product['quantite'];
    $seuil_alerte = isset($_POST['seuil_alerte']) ? intval($_POST['seuil_alerte']) : $product['seuil_alerte'];
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
    
    if ($prix <= 0) {
        $errors[] = "Le prix doit être supérieur à 0";
    }
    
    if ($quantite < 0) {
        $errors[] = "La quantité ne peut pas être négative";
    }
    
    // Gérer l'upload d'image principale si nouvelle image
    $image_url = $product['image_url'];
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
            // Générer un nom de fichier unique
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid() . '_' . time() . '.' . $file_extension;
            $upload_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
                // Supprimer l'ancienne image si ce n'est pas l'image par défaut
                if ($image_url !== 'assets/images/produits/default.jpg' && file_exists('../../' . $image_url)) {
                    unlink('../../' . $image_url);
                }
                $image_url = 'uploads/produits/' . $file_name;
            } else {
                $errors[] = "Erreur lors du téléchargement de l'image";
            }
        }
    }
    
    // Images supplémentaires
    $image_url2 = $product['image_url2'];
    $image_url3 = $product['image_url3'];
    
    // Gérer la deuxième image
    if (isset($_FILES['image2']) && $_FILES['image2']['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($_FILES['image2']['tmp_name']);
        if (in_array($file_type, $allowed_types)) {
            $file_extension = strtolower(pathinfo($_FILES['image2']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid() . '_' . time() . '_2.' . $file_extension;
            $upload_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image2']['tmp_name'], $upload_file)) {
                // Supprimer l'ancienne image
                if ($image_url2 && file_exists('../../' . $image_url2)) {
                    unlink('../../' . $image_url2);
                }
                $image_url2 = 'uploads/produits/' . $file_name;
            }
        }
    }
    
    // Gérer la troisième image
    if (isset($_FILES['image3']) && $_FILES['image3']['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($_FILES['image3']['tmp_name']);
        if (in_array($file_type, $allowed_types)) {
            $file_extension = strtolower(pathinfo($_FILES['image3']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid() . '_' . time() . '_3.' . $file_extension;
            $upload_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image3']['tmp_name'], $upload_file)) {
                // Supprimer l'ancienne image
                if ($image_url3 && file_exists('../../' . $image_url3)) {
                    unlink('../../' . $image_url3);
                }
                $image_url3 = 'uploads/produits/' . $file_name;
            }
        }
    }
    
    // Bouton pour supprimer les images
    if (isset($_POST['delete_image2']) && $image_url2) {
        if (file_exists('../../' . $image_url2)) {
            unlink('../../' . $image_url2);
        }
        $image_url2 = null;
    }
    
    if (isset($_POST['delete_image3']) && $image_url3) {
        if (file_exists('../../' . $image_url3)) {
            unlink('../../' . $image_url3);
        }
        $image_url3 = null;
    }
    
    if (empty($errors)) {
        // Préparer les valeurs NULL pour les champs optionnels
        if (empty($carte_graphique)) $carte_graphique = null;
        if (empty($systeme_exploitation)) $systeme_exploitation = null;
        if (empty($poids) || $poids == 0) $poids = null;
        if (empty($autonomie)) $autonomie = null;
        if (empty($ports)) $ports = null;
        if (empty($wifi)) $wifi = null;
        if (empty($bluetooth)) $bluetooth = null;
        if (empty($prix_promotion) || $prix_promotion == 0) $prix_promotion = null;
        
        // CORRECTION CRITIQUE : Compter le bon nombre de paramètres
        // La chaîne de types doit avoir exactement le même nombre de caractères que de variables
        
        // Mettre à jour le produit - CORRIGER LA REQUÊTE
        $stmt = $conn->prepare("
            UPDATE produits SET
                nom = ?, 
                description = ?, 
                categorie_id = ?, 
                marque = ?, 
                processeur = ?,
                ram = ?, 
                stockage = ?, 
                ecran = ?, 
                carte_graphique = ?, 
                systeme_exploitation = ?,
                poids = ?, 
                autonomie = ?, 
                ports = ?, 
                wifi = ?, 
                bluetooth = ?, 
                prix = ?, 
                prix_promotion = ?, 
                quantite = ?, 
                seuil_alerte = ?, 
                image_url = ?, 
                image_url2 = ?, 
                image_url3 = ?, 
                est_actif = ?, 
                est_en_promotion = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        if ($stmt) {
            // CORRECTION : La chaîne de types doit correspondre au nombre de paramètres
            // 25 '?' dans la requête = 25 paramètres
            
            $stmt->bind_param(
                "ssisssssssdssssddiiissssi", // 25 caractères pour 25 paramètres
                $nom,
                $description,
                $categorie_id,
                $marque,
                $processeur,
                $ram,
                $stockage,
                $ecran,
                $carte_graphique,
                $systeme_exploitation,
                $poids,
                $autonomie,
                $ports,
                $wifi,
                $bluetooth,
                $prix,
                $prix_promotion,
                $quantite,
                $seuil_alerte,
                $image_url,
                $image_url2,
                $image_url3,
                $est_actif,
                $est_en_promotion,
                $product_id  // Le dernier paramètre : WHERE id = ?
            );
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Produit modifié avec succès !";
                header('Location: produits.php');
                exit();
            } else {
                $errors[] = "Erreur lors de la modification du produit : " . $stmt->error;
                error_log("Erreur SQL: " . $stmt->error);
            }
            
            $stmt->close();
        } else {
            $errors[] = "Erreur de préparation de la requête : " . $conn->error;
            error_log("Erreur préparation: " . $conn->error);
        }
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
$display_data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $product;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Modifier le produit</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="produits.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="fas fa-edit me-2"></i>
            Modification du produit #<?php echo $product['id']; ?> - <?php echo htmlspecialchars($product['nom']); ?>
        </h6>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="productForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Informations générales -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h5 class="mb-3"><i class="fas fa-info-circle text-primary me-2"></i>Informations générales</h5>
                    
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du produit *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                               value="<?php echo htmlspecialchars($display_data['nom']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php 
                            echo htmlspecialchars($display_data['description']); 
                        ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="categorie_id" class="form-label">Catégorie *</label>
                            <select class="form-select" id="categorie_id" name="categorie_id" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                    <?php echo ($display_data['categorie_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="marque" class="form-label">Marque *</label>
                            <input type="text" class="form-control" id="marque" name="marque" required
                                   value="<?php echo htmlspecialchars($display_data['marque']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <h5 class="mb-3"><i class="fas fa-image text-primary me-2"></i>Images</h5>
                    
                    <!-- Image principale -->
                    <div class="mb-4">
                        <label for="image" class="form-label">Image principale</label>
                        <div class="text-center mb-3">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="../<?php echo $product['image_url']; ?>" 
                                     alt="Image actuelle" 
                                     class="img-fluid rounded border" 
                                     style="max-height: 150px; max-width: 100%;">
                                <div class="mt-2 text-muted small">
                                    Image actuelle
                                </div>
                            <?php else: ?>
                                <div class="border rounded p-4 text-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                    <p class="mt-2">Aucune image</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Laisser vide pour conserver l'image actuelle</div>
                    </div>
                    
                    <!-- Images supplémentaires -->
                    <div class="mb-3">
                        <label class="form-label">Image 2</label>
                        <?php if (!empty($product['image_url2'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <img src="../<?php echo $product['image_url2']; ?>" 
                                 alt="Image 2" 
                                 class="img-thumbnail me-2" 
                                 style="width: 60px; height: 60px; object-fit: contain;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="delete_image2" name="delete_image2">
                                <label class="form-check-label text-danger" for="delete_image2">
                                    Supprimer cette image
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image2" name="image2" accept="image/*">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Image 3</label>
                        <?php if (!empty($product['image_url3'])): ?>
                        <div class="d-flex align-items-center mb-2">
                            <img src="../<?php echo $product['image_url3']; ?>" 
                                 alt="Image 3" 
                                 class="img-thumbnail me-2" 
                                 style="width: 60px; height: 60px; object-fit: contain;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="delete_image3" name="delete_image3">
                                <label class="form-check-label text-danger" for="delete_image3">
                                    Supprimer cette image
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image3" name="image3" accept="image/*">
                    </div>
                </div>
            </div>
            
            <!-- Caractéristiques techniques -->
            <h5 class="mb-3"><i class="fas fa-microchip text-primary me-2"></i>Caractéristiques techniques</h5>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="processeur" class="form-label">Processeur *</label>
                    <input type="text" class="form-control" id="processeur" name="processeur" required
                           value="<?php echo htmlspecialchars($display_data['processeur']); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="ram" class="form-label">Mémoire RAM *</label>
                    <input type="text" class="form-control" id="ram" name="ram" required
                           value="<?php echo htmlspecialchars($display_data['ram']); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="stockage" class="form-label">Stockage *</label>
                    <input type="text" class="form-control" id="stockage" name="stockage" required
                           value="<?php echo htmlspecialchars($display_data['stockage']); ?>">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="ecran" class="form-label">Écran *</label>
                    <input type="text" class="form-control" id="ecran" name="ecran" required
                           value="<?php echo htmlspecialchars($display_data['ecran']); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="carte_graphique" class="form-label">Carte graphique</label>
                    <input type="text" class="form-control" id="carte_graphique" name="carte_graphique"
                           value="<?php echo htmlspecialchars($display_data['carte_graphique']); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="systeme_exploitation" class="form-label">Système d'exploitation</label>
                    <input type="text" class="form-control" id="systeme_exploitation" name="systeme_exploitation"
                           value="<?php echo htmlspecialchars($display_data['systeme_exploitation']); ?>">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <label for="poids" class="form-label">Poids (kg)</label>
                    <input type="number" class="form-control" id="poids" name="poids" step="0.01"
                           value="<?php echo htmlspecialchars($display_data['poids']); ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="autonomie" class="form-label">Autonomie</label>
                    <input type="text" class="form-control" id="autonomie" name="autonomie"
                           value="<?php echo htmlspecialchars($display_data['autonomie']); ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="wifi" class="form-label">Wi-Fi</label>
                    <input type="text" class="form-control" id="wifi" name="wifi"
                           value="<?php echo htmlspecialchars($display_data['wifi']); ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="bluetooth" class="form-label">Bluetooth</label>
                    <input type="text" class="form-control" id="bluetooth" name="bluetooth"
                           value="<?php echo htmlspecialchars($display_data['bluetooth']); ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="ports" class="form-label">Ports</label>
                <textarea class="form-control" id="ports" name="ports" rows="2"><?php 
                    echo htmlspecialchars($display_data['ports']); 
                ?></textarea>
            </div>
            
            <!-- Prix et stock -->
            <h5 class="mb-3"><i class="fas fa-dollar-sign text-primary me-2"></i>Prix et stock</h5>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="prix" class="form-label">Prix (FCFA) *</label>
                    <input type="number" class="form-control" id="prix" name="prix" 
                           step="0.01" min="0" required
                           value="<?php echo htmlspecialchars($display_data['prix']); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="prix_promotion" class="form-label">Prix promotionnel (FCFA)</label>
                    <input type="number" class="form-control" id="prix_promotion" name="prix_promotion"
                           step="0.01" min="0"
                           value="<?php echo htmlspecialchars($display_data['prix_promotion']); ?>">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="quantite" class="form-label">Quantité *</label>
                    <input type="number" class="form-control" id="quantite" name="quantite" 
                           min="0" required
                           value="<?php echo htmlspecialchars($display_data['quantite']); ?>">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="seuil_alerte" class="form-label">Seuil d'alerte</label>
                    <input type="number" class="form-control" id="seuil_alerte" name="seuil_alerte" 
                           min="1"
                           value="<?php echo htmlspecialchars($display_data['seuil_alerte']); ?>">
                </div>
            </div>
            
            <!-- Options -->
            <h5 class="mb-3"><i class="fas fa-cog text-primary me-2"></i>Options</h5>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="est_actif" name="est_actif" 
                               value="1" <?php echo ($display_data['est_actif'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="est_actif">
                            <strong>Produit actif</strong>
                        </label>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="est_en_promotion" name="est_en_promotion" 
                               value="1" <?php echo ($display_data['est_en_promotion'] == 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="est_en_promotion">
                            <strong>En promotion</strong>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Boutons -->
            <div class="d-flex justify-content-between">
                <a href="produits.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                
                <div class="btn-group">
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                    
                    <a href="../../pages/produit-details.php?id=<?php echo $product_id; ?>" 
                       target="_blank" 
                       class="btn btn-outline-success">
                        <i class="fas fa-eye"></i> Voir sur le site
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>