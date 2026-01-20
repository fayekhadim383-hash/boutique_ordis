    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-laptop"></i>Elite Informatique</h5>
                    <p>Votre boutique spécialisée en vente d'ordinateurs portables. Qualité, performance et prix compétitifs.</p>
                    <div class="social-icons">
                        <a href="https://www.facebook.com/Eliteinformatiques" class="text-white me-3" target="_blank"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="https://www.instagram.com/eliteinformatiques?igsh=amR1MDJ3YnpjYTJ4" class="text-white me-3" target="_blank"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <h5>Navigation</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-white-50 text-decoration-none">Accueil</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/produits.php" class="text-white-50 text-decoration-none">Produits</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/apropos.php" class="text-white-50 text-decoration-none">À propos</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/contact.php" class="text-white-50 text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Livraison</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Retours</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Garantie</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Dakar, Sénégal</li>
                        <li><i class="fas fa-phone me-2"></i> +221 77 739 19 93</li>
                        <li><i class="fas fa-envelope me-2"></i> metadiop3@gmail.com</li>
                        <li><i class="fas fa-clock me-2"></i> Lun - Sam: 9h - 21h</li>
                    </ul>
                </div>
            </div>
            
            <hr class="bg-white">
            
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Elite Inormatique. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-end">
                    <img src="<?php echo SITE_URL; ?>assets/images/paiement-methods.png" alt="Méthodes de paiement" height="30">
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>assets/js/panier.js"></script>
    
    <!-- Stripe.js pour paiements par carte -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <?php if (basename($_SERVER['PHP_SELF']) == 'checkout.php'): ?>
        <script src="<?php echo SITE_URL; ?>assets/js/checkout.js"></script>
    <?php endif; ?>
</body>
</html>