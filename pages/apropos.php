<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "À propos - " . SITE_NAME;
?>

<?php include '../includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">À propos de nous</h1>
                <p class="lead mb-4">Votre partenaire de confiance pour l'informatique et la technologie depuis 2020.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="produits.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i>Voir nos produits
                    </a>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-envelope me-2"></i>Nous contacter
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="about-hero-image">
                    <i class="fas fa-laptop-code fa-5x text-primary"></i>
                    <div class="floating-shapes">
                        <div class="shape shape-1"></div>
                        <div class="shape shape-2"></div>
                        <div class="shape shape-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Notre histoire -->
<section class="py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="about-image-container">
                    <img src="<?php echo SITE_URL; ?>assets/images/about-company.jpg" 
                         alt="Notre équipe" 
                         class="img-fluid rounded shadow-lg">
                    <div class="experience-badge">
                        <span class="badge-number">5+</span>
                        <span class="badge-text">Années<br>d'expérience</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h2 class="mb-4">Notre histoire</h2>
                <p class="lead mb-4">
                    Fondée en 2020, <strong><?php echo SITE_NAME; ?></strong> est née d'une passion commune 
                    pour la technologie et d'un désir de rendre l'informatique accessible à tous.
                </p>
                <p class="mb-4">
                    Notre mission est simple : fournir des équipements informatiques de qualité à des prix compétitifs, 
                    accompagnés d'un service client exceptionnel. Nous croyons que la technologie doit être un outil 
                    qui facilite la vie, et non une source de complications.
                </p>
                <p class="mb-4">
                    Au fil des années, nous avons construit une relation de confiance avec nos clients, 
                    en nous concentrant sur la qualité des produits, la transparence des prix et l'excellence du service.
                </p>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5 class="mt-3">Garantie 2 ans</h5>
                            <p>Tous nos produits bénéficient d'une garantie de 2 ans</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h5 class="mt-3">Support 24/7</h5>
                            <p>Notre équipe support est disponible 7j/7</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Nos valeurs -->
<section class="py-5 bg-light mb-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Nos valeurs</h2>
            <p class="lead text-muted">Les principes qui guident nos actions au quotidien</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="value-card text-center h-100">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4 class="mt-4 mb-3">Confiance</h4>
                    <p>Nous construisons des relations durables basées sur la transparence et la fiabilité.</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="value-card text-center h-100">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4 class="mt-4 mb-3">Qualité</h4>
                    <p>Nous sélectionnons rigoureusement chaque produit pour garantir excellence et durabilité.</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="value-card text-center h-100">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4 class="mt-4 mb-3">Service Client</h4>
                    <p>Votre satisfaction est notre priorité. Nous sommes là pour vous accompagner à chaque étape.</p>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="value-card text-center h-100">
                    <div class="value-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4 class="mt-4 mb-3">Innovation</h4>
                    <p>Nous restons à la pointe de la technologie pour vous offrir les dernières innovations.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Notre équipe -->
<section class="py-5 mb-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Notre équipe</h2>
            <p class="lead text-muted">Des experts passionnés à votre service</p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="team-card text-center">
                    <div class="team-image mb-3">
                        <img src="<?php echo SITE_URL; ?>assets/images/team/ceo.jpg" 
                             alt="Directeur Général" 
                             class="img-fluid rounded-circle">
                    </div>
                    <h4 class="mb-1">Mouhamed DIOP</h4>
                    <p class="text-primary mb-3">Directeur Général</p>
                    <p class="text-muted">10 ans d'expérience dans le secteur commercial informatique. Passionné par le commerce et la technologie.</p>
                    <div class="social-links mt-3">
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="team-card text-center">
                    <div class="team-image mb-3">
                        <img src="<?php echo SITE_URL; ?>assets/images/team/tech.jpg" 
                             alt="Responsable Technique" 
                             class="img-fluid rounded-circle">
                    </div>
                    <h4 class="mb-1">Khadim FAYE</h4>
                    <p class="text-primary mb-3">Responsable Technique</p>
                    <p class="text-muted">Expert en hardware et configuration, développement de sites web et application. Garantit la qualité technique de chaque produit.</p>
                    <div class="social-links mt-3">
                        <a href="https://www.linkedin.com/in/khadim-faye-89256615a/" class="social-link"><i class="fab fa-linkedin"></i></a>
                        <a href="https://www.facebook.com/ahmed.diaw.7121" class="social-link"><i class="fab fa-facebook"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="team-card text-center">
                    <div class="team-image mb-3">
                        <img src="<?php echo SITE_URL; ?>assets/images/team/support.jpg" 
                             alt="Responsable Support" 
                             class="img-fluid rounded-circle">
                    </div>
                    <h4 class="mb-1">Sokhna TOURE</h4>
                    <p class="text-primary mb-3">Responsable Support Client</p>
                    <p class="text-muted">Défenseur des clients, s'assure que chaque interaction soit une expérience positive.</p>
                    <div class="social-links mt-3">
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistiques -->
<section class="py-5 bg-primary text-white mb-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold mb-2" data-count="1500">0</h3>
                    <p class="mb-0">Clients satisfaits</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold mb-2" data-count="5000">0</h3>
                    <p class="mb-0">Produits vendus</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold mb-2" data-count="30">0</h3>
                    <p class="mb-0">Marques partenaires</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold mb-2" data-count="4">0</h3>
                    <p class="mb-0">Années d'expérience</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="py-5 mb-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Questions fréquentes</h2>
            <p class="lead text-muted">Trouvez rapidement des réponses à vos questions</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Quels sont les délais de livraison ?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Les délais de livraison varient entre 24h et 72h pour les produits en stock. 
                                Pour les commandes spéciales ou produits en rupture, nous vous informons du délai estimé lors de la commande.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Offrez-vous une garantie sur vos produits ?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oui, tous nos produits bénéficient d'une garantie constructeur de 2 ans. 
                                Nous proposons également une assistance technique gratuite pendant la période de garantie.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Puis-je retourner un produit ?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oui, nous offrons une période de retour de 14 jours pour les produits non utilisés et dans leur emballage d'origine. 
                                Les retours sont gratuits pour les produits défectueux.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Proposez-vous un service après-vente ?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Absolument ! Notre service après-vente est disponible par téléphone, email et chat en direct. 
                                Nous proposons également des réparations et mises à niveau pour la plupart de nos produits.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Acceptez-vous les commandes personnalisées ?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oui, nous pouvons configurer des ordinateurs sur mesure selon vos besoins spécifiques. 
                                Contactez notre équipe technique pour discuter de votre projet.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-5">
                    <p class="lead mb-3">Vous avez d'autres questions ?</p>
                    <a href="contact.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-question-circle me-2"></i>Contactez-nous
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 bg-dark text-white mb-5 rounded">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-4">Prêt à faire le bon choix ?</h2>
        <p class="lead mb-4">Rejoignez des milliers de clients satisfaits et découvrez la différence <?php echo SITE_NAME; ?>.</p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="produits.php" class="btn btn-light btn-lg">
                <i class="fas fa-laptop me-2"></i>Voir nos produits
            </a>
            <a href="contact.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-phone me-2"></i>Nous appeler
            </a>
        </div>
    </div>
</section>

<style>
/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--primary-color), #6610f2);
    color: white;
    padding: 100px 0;
    border-radius: var(--border-radius);
    margin-top: 2rem;
}

/* About Image Container */
.about-image-container {
    position: relative;
    overflow: hidden;
    border-radius: 15px;
}

.experience-badge {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background: var(--primary-color);
    color: white;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.badge-number {
    display: block;
    font-size: 2.5rem;
    font-weight: bold;
    line-height: 1;
}

.badge-text {
    display: block;
    font-size: 0.9rem;
    line-height: 1.2;
}

/* Feature Cards */
.feature-card {
    padding: 25px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    text-align: center;
    height: 100%;
    transition: transform 0.3s;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.feature-icon i {
    font-size: 2rem;
    color: var(--primary-color);
}

/* Value Cards */
.value-card {
    padding: 30px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.value-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.value-icon {
    width: 80px;
    height: 80px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.value-icon i {
    font-size: 2.5rem;
    color: white;
}

/* Team Cards */
.team-card {
    padding: 30px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.team-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.team-image img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border: 5px solid var(--primary-color);
}

.social-links {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.social-link {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    text-decoration: none;
    transition: all 0.3s;
}

.social-link:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-3px);
}

/* Statistics */
.stat-item h3 {
    font-size: 3.5rem;
}

@media (max-width: 768px) {
    .stat-item h3 {
        font-size: 2.5rem;
    }
}

/* FAQ */
.accordion-button {
    font-weight: 600;
    padding: 20px;
    background-color: #f8f9fa;
    border: none;
}

.accordion-button:not(.collapsed) {
    background-color: var(--primary-color);
    color: white;
}

.accordion-body {
    padding: 20px;
    background-color: #f8f9fa;
}

/* Floating shapes */
.floating-shapes {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.shape {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.shape-1 {
    width: 50px;
    height: 50px;
    top: 20%;
    left: 10%;
    animation: float 6s infinite ease-in-out;
}

.shape-2 {
    width: 30px;
    height: 30px;
    top: 60%;
    right: 15%;
    animation: float 8s infinite ease-in-out 1s;
}

.shape-3 {
    width: 40px;
    height: 40px;
    bottom: 20%;
    left: 20%;
    animation: float 7s infinite ease-in-out 2s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(180deg);
    }
}

.about-hero-image {
    position: relative;
    display: inline-block;
}

.about-hero-image i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .hero-section {
        padding: 60px 0;
        text-align: center;
    }
    
    .hero-section h1 {
        font-size: 2.5rem;
    }
    
    .experience-badge {
        bottom: 10px;
        right: 10px;
        padding: 10px;
    }
    
    .badge-number {
        font-size: 2rem;
    }
}
</style>

<script>
// Counter animation for statistics
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('[data-count]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-count'));
                const duration = 2000; // 2 seconds
                const step = target / (duration / 16); // 60fps
                let current = 0;
                
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        counter.textContent = target + (counter.getAttribute('data-count') > 1000 ? '+' : '+');
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current);
                    }
                }, 16);
                
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => {
        observer.observe(counter);
    });
    
    // FAQ accordion enhancement
    const faqButtons = document.querySelectorAll('.accordion-button');
    faqButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('.fas');
            if (icon) {
                if (this.classList.contains('collapsed')) {
                    icon.classList.remove('fa-plus');
                    icon.classList.add('fa-minus');
                } else {
                    icon.classList.remove('fa-minus');
                    icon.classList.add('fa-plus');
                }
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>