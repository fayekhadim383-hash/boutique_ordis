<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Connexion - " . SITE_NAME;

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect(SITE_URL);
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($email) || empty($password)) {
        $errors[] = "Tous les champs sont requis";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, nom, email, password, role FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Mettre à jour la dernière connexion
                $update_stmt = $conn->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                $_SESSION['success'] = "Connexion réussie ! Bienvenue " . $user['nom'];
                
                // Rediriger vers la page d'accueil
                redirect(SITE_URL);
            } else {
                $errors[] = "Mot de passe incorrect";
            }
        } else {
            $errors[] = "Aucun compte trouvé avec cet email";
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
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt"></i> Connexion</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="text-end">
                            <small><a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Mot de passe oublié ?</a></small>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">Se connecter</button>
                    </div>
                    
                    <div class="text-center">
                        <p>Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Boutons de connexion rapide pour admin (démo) -->
        <div class="card mt-3">
            <div class="card-body">
                <h6>Accès démo :</h6>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="fillDemoCredentials('admin')">
                        <i class="fas fa-user-shield"></i> Connexion Admin
                    </button>
                    <button class="btn btn-outline-secondary" onclick="fillDemoCredentials('client')">
                        <i class="fas fa-user"></i> Connexion Client
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mot de passe oublié -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Réinitialisation du mot de passe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Entrez votre adresse email pour recevoir un lien de réinitialisation :</p>
                <form id="forgotPasswordForm">
                    <div class="mb-3">
                        <input type="email" class="form-control" placeholder="Votre email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Envoyer le lien</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemoCredentials(type) {
    if (type === 'admin') {
        document.getElementById('email').value = 'admin@pcpro.com';
        document.getElementById('password').value = 'admin123';
    } else {
        document.getElementById('email').value = 'client@pcpro.com';
        document.getElementById('password').value = 'client123';
    }
}
</script>

<?php include '../includes/footer.php'; ?>