<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Inscription - " . SITE_NAME;

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect(SITE_URL);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telephone = sanitize($_POST['telephone']);
    $adresse = sanitize($_POST['adresse']);
    
    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom est requis";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = "Cet email est déjà utilisé";
    }
    $stmt->close();
    
    // Si pas d'erreurs, créer le compte
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, password, telephone, adresse) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nom, $email, $hashed_password, $telephone, $adresse);
        
        if ($stmt->execute()) {
            // Connecter automatiquement l'utilisateur
            $user_id = $stmt->insert_id;
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'client';
            
            $_SESSION['success'] = "Inscription réussie ! Bienvenue $nom.";
            
            // Rediriger vers la page d'accueil
            redirect(SITE_URL);
        } else {
            $errors[] = "Une erreur est survenue lors de l'inscription";
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-plus"></i> Créer un compte</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom complet *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required 
                               value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Minimum 6 caractères</small>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone"
                               value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="conditions" required>
                        <label class="form-check-label" for="conditions">
                            J'accepte les <a href="#" data-bs-toggle="modal" data-bs-target="#conditionsModal">conditions d'utilisation</a> *
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">S'inscrire</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p>Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Conditions d'utilisation -->
<div class="modal fade" id="conditionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Conditions d'utilisation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Acceptation des conditions</h6>
                <p>En utilisant ce site, vous acceptez ces conditions d'utilisation.</p>
                
                <h6>2. Compte utilisateur</h6>
                <p>Vous êtes responsable de la confidentialité de votre compte et mot de passe.</p>
                
                <h6>3. Commandes et paiements</h6>
                <p>Toutes les commandes sont soumises à disponibilité et confirmation des prix.</p>
                
                <h6>4. Livraison</h6>
                <p>Les délais de livraison sont indicatifs et peuvent varier.</p>
                
                <h6>5. Retours et remboursements</h6>
                <p>Les retours sont acceptés dans les 30 jours suivant la réception.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>