<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Contact - " . SITE_NAME;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom']);
    $email = sanitize($_POST['email']);
    $telephone = sanitize($_POST['telephone']);
    $sujet = sanitize($_POST['sujet']);
    $message = sanitize($_POST['message']);
    
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom est requis";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide";
    }
    
    if (empty($sujet)) {
        $errors[] = "Le sujet est requis";
    }
    
    if (empty($message)) {
        $errors[] = "Le message est requis";
    }
    
    if (empty($errors)) {
        // Enregistrer dans la base de données
        $stmt = $conn->prepare("INSERT INTO contacts (nom, email, telephone, sujet, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nom, $email, $telephone, $sujet, $message);
        
        if ($stmt->execute()) {
            // Envoyer un email de notification
            $to = SITE_EMAIL;
            $subject = "Nouveau message de contact : " . $sujet;
            $headers = "From: " . $email . "\r\n";
            $headers .= "Reply-To: " . $email . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $email_body = "
            <html>
            <body>
                <h2>Nouveau message de contact</h2>
                <p><strong>Nom :</strong> $nom</p>
                <p><strong>Email :</strong> $email</p>
                <p><strong>Téléphone :</strong> $telephone</p>
                <p><strong>Sujet :</strong> $sujet</p>
                <p><strong>Message :</strong></p>
                <p>$message</p>
            </body>
            </html>
            ";
            
            mail($to, $subject, $email_body, $headers);
            
            $_SESSION['success'] = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
            
            // Réinitialiser le formulaire
            $_POST = [];
        } else {
            $_SESSION['error'] = "Une erreur est survenue lors de l'envoi du message";
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-envelope"></i> Nous contacter</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom complet *</label>
                            <input type="text" class="form-control" id="nom" name="nom" required
                                   value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Adresse email *</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone"
                               value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="sujet" class="form-label">Sujet *</label>
                        <input type="text" class="form-control" id="sujet" name="sujet" required
                               value="<?php echo isset($_POST['sujet']) ? htmlspecialchars($_POST['sujet']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="6" required><?php 
                            echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '';
                        ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="g-recaptcha" data-sitekey="YOUR_RECAPTCHA_SITE_KEY"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Envoyer le message
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informations de contact</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                        <strong>Adresse :</strong><br>
                        Dakar, Sénégal
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-phone text-primary me-2"></i>
                        <strong>Téléphone :</strong><br>
                        +221 77 739 19 93
                    </li>
                    <li class="mb-3">
                        <i class="fas fa-envelope text-primary me-2"></i>
                        <strong>Email :</strong><br>
                        metadiop3@gmail.com
                    </li>
                    <li>
                        <i class="fas fa-clock text-primary me-2"></i>
                        <strong>Horaires :</strong><br>
                        Lundi - Samedi : 9h - 21h<br>
                        Dimanche : 9h - 18h
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-headset"></i> Support</h5>
            </div>
            <div class="card-body">
                <p>Notre équipe de support est disponible pour vous aider :</p>
                <ul>
                    <li>Assistance technique</li>
                    <li>Questions sur les produits</li>
                    <li>Suivi de commande</li>
                    <li>Retours et garanties</li>
                </ul>
                <p class="mb-0">
                    <strong>Email support :</strong><br>
                    <a href="mailto:metadiop3@gmail.com">metadiop3@gmail.com</a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Carte Google Maps (optionnelle) -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-map-marked-alt"></i> Notre localisation</h5>
    </div>
    <div class="card-body">
        <div id="map" style="height: 300px; border-radius: 8px; overflow: hidden;">
            <!-- Intégrer Google Maps ici -->
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3859.146227743578!2d-17.43268292460573!3d14.692363675428113!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xec17390737c8af5%3A0x2a5d2ff7dff1bb38!2sDakar%2C%20Senegal!5e0!3m2!1sen!2s!4v1699999999999!5m2!1sen!2s" 
                    width="100%" 
                    height="300" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</div>

<!-- reCAPTCHA -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<?php include '../includes/footer.php'; ?>