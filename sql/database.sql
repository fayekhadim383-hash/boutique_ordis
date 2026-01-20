-- Création de la base de données
CREATE DATABASE IF NOT EXISTS boutique_ordis 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE boutique_ordis;

-- Table des catégories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    slug VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des produits
CREATE TABLE produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categorie_id INT,
    nom VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    description TEXT,
    marque VARCHAR(100),
    processeur VARCHAR(100),
    ram VARCHAR(50),
    stockage VARCHAR(100),
    ecran VARCHAR(100),
    carte_graphique VARCHAR(100),
    systeme_exploitation VARCHAR(100) DEFAULT 'Windows 11',
    poids DECIMAL(5,2),
    autonomie VARCHAR(50),
    ports VARCHAR(255),
    wifi VARCHAR(50) DEFAULT 'Wi-Fi 6',
    bluetooth VARCHAR(20) DEFAULT 'Bluetooth 5.2',
    prix DECIMAL(10,2) NOT NULL,
    prix_promotion DECIMAL(10,2),
    quantite INT DEFAULT 0,
    seuil_alerte INT DEFAULT 5,
    image_url VARCHAR(500) DEFAULT 'assets/images/produits/default.jpg',
    image_url2 VARCHAR(500),
    image_url3 VARCHAR(500),
    est_actif BOOLEAN DEFAULT TRUE,
    est_en_promotion BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_categorie (categorie_id),
    INDEX idx_marque (marque),
    INDEX idx_prix (prix),
    INDEX idx_actif (est_actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des utilisateurs
CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(200) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    pays VARCHAR(100) DEFAULT 'Sénégal',
    role ENUM('client', 'admin') DEFAULT 'client',
    est_actif BOOLEAN DEFAULT TRUE,
    derniere_connexion TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des commandes
CREATE TABLE commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT,
    numero_commande VARCHAR(50) UNIQUE NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    statut ENUM('en_attente', 'payee', 'expediee', 'livree', 'annulee') DEFAULT 'en_attente',
    methode_paiement ENUM('carte_visa', 'wave', 'orange_money', 'paypal', 'especes'),
    id_transaction VARCHAR(100),
    adresse_livraison TEXT NOT NULL,
    telephone_livraison VARCHAR(20),
    notes TEXT,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_paiement TIMESTAMP NULL,
    date_expedition TIMESTAMP NULL,
    date_livraison TIMESTAMP NULL,
    frais_livraison DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_utilisateur (utilisateur_id),
    INDEX idx_statut (statut),
    INDEX idx_date (date_commande),
    INDEX idx_numero (numero_commande)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des détails de commande
CREATE TABLE commande_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT,
    produit_id INT,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL,
    INDEX idx_commande (commande_id),
    INDEX idx_produit (produit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des avis
CREATE TABLE avis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    produit_id INT,
    utilisateur_id INT,
    note INT CHECK (note >= 1 AND note <= 5),
    commentaire TEXT,
    est_approuve BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_produit (produit_id),
    INDEX idx_utilisateur (utilisateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des coupons
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('pourcentage', 'montant_fixe') DEFAULT 'pourcentage',
    valeur DECIMAL(10,2) NOT NULL,
    montant_minimum DECIMAL(10,2) DEFAULT 0,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    utilisations_max INT DEFAULT 1,
    utilisations_actuelles INT DEFAULT 0,
    est_actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_actif (est_actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des coupons utilisés
CREATE TABLE coupons_utilises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    coupon_id INT,
    utilisateur_id INT,
    commande_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    INDEX idx_coupon (coupon_id),
    INDEX idx_utilisateur (utilisateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des wishlists
CREATE TABLE wishlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT,
    produit_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (utilisateur_id, produit_id),
    INDEX idx_utilisateur (utilisateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des contacts
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(200) NOT NULL,
    telephone VARCHAR(20),
    sujet VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    statut ENUM('nouveau', 'en_cours', 'resolu') DEFAULT 'nouveau',
    reponse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des paramètres du site
CREATE TABLE parametres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cle VARCHAR(100) UNIQUE NOT NULL,
    valeur TEXT,
    type VARCHAR(50) DEFAULT 'texte',
    categorie VARCHAR(50) DEFAULT 'general',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cle (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des logs
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Triggers

-- Mettre à jour le stock après une commande
DELIMITER $$
CREATE TRIGGER after_insert_commande_detail
AFTER INSERT ON commande_details
FOR EACH ROW
BEGIN
    UPDATE produits 
    SET quantite = quantite - NEW.quantite,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.produit_id;
END$$
DELIMITER ;

-- Mettre à jour le stock après annulation d'une commande
DELIMITER $$
CREATE TRIGGER after_delete_commande_detail
AFTER DELETE ON commande_details
FOR EACH ROW
BEGIN
    UPDATE produits 
    SET quantite = quantite + OLD.quantite,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.produit_id;
END$$
DELIMITER ;

-- Générer automatiquement le numéro de commande
DELIMITER $$
CREATE TRIGGER before_insert_commande
BEFORE INSERT ON commandes
FOR EACH ROW
BEGIN
    IF NEW.numero_commande IS NULL THEN
        SET NEW.numero_commande = CONCAT(
            'CMD-',
            DATE_FORMAT(NOW(), '%Y%m%d'),
            '-',
            LPAD(FLOOR(RAND() * 10000), 4, '0')
        );
    END IF;
END$$
DELIMITER ;

-- Insertion des données de test

-- Catégories
INSERT INTO categories (nom, description, slug) VALUES
('Gaming', 'Ordinateurs portables gaming pour les joueurs exigeants', 'gaming'),
('Bureautique', 'Ordinateurs portables pour le travail et les études', 'bureautique'),
('Ultraportables', 'Ordinateurs portables légers et fins pour la mobilité', 'ultraportables'),
('Création', 'Ordinateurs portables pour la création de contenu et design', 'creation'),
('Étudiants', 'Ordinateurs portables abordables pour les étudiants', 'etudiants');

-- Produits
INSERT INTO produits (categorie_id, nom, slug, description, marque, processeur, ram, stockage, ecran, carte_graphique, prix, quantite, image_url) VALUES
(1, 'MSI Katana 15', 'msi-katana-15', 'Ordinateur portable gaming avec processeur Intel Core i7 et RTX 4060', 'MSI', 'Intel Core i7-13620H', '16 Go DDR5', '1 To SSD', '15.6" FHD 144Hz', 'NVIDIA RTX 4060 8 Go', 1499999, 10, 'assets/images/produits/msi-katana.jpg'),
(1, 'Asus ROG Strix G16', 'asus-rog-strix-g16', 'Puissant ordinateur gaming avec écran QHD 165Hz', 'Asus', 'Intel Core i9-13980HX', '32 Go DDR5', '2 To SSD', '16" QHD 165Hz', 'NVIDIA RTX 4070 8 Go', 2199999, 5, 'assets/images/produits/asus-rog.jpg'),
(2, 'Dell Latitude 5440', 'dell-latitude-5440', 'Ordinateur portable professionnel pour entreprises', 'Dell', 'Intel Core i5-1335U', '8 Go DDR4', '512 Go SSD', '14" FHD', 'Intel Iris Xe', 899999, 15, 'assets/images/produits/dell-latitude.jpg'),
(2, 'HP EliteBook 840 G9', 'hp-elitebook-840-g9', 'Ultrabook professionnel avec sécurité renforcée', 'HP', 'Intel Core i7-1265U', '16 Go DDR4', '1 To SSD', '14" FHD', 'Intel Iris Xe', 1299999, 8, 'assets/images/produits/hp-elitebook.jpg'),
(3, 'Apple MacBook Air M2', 'apple-macbook-air-m2', 'Ultraportable Apple avec puce M2 et autonomie exceptionnelle', 'Apple', 'Apple M2', '8 Go', '256 Go SSD', '13.6" Liquid Retina', 'Apple M2 8-core', 1499999, 12, 'assets/images/produits/macbook-air.jpg'),
(3, 'Microsoft Surface Laptop 5', 'microsoft-surface-laptop-5', 'Ordinateur portable élégant avec écran tactile PixelSense', 'Microsoft', 'Intel Core i7-1255U', '16 Go LPDDR5', '512 Go SSD', '13.5" PixelSense', 'Intel Iris Xe', 1399999, 6, 'assets/images/produits/surface-laptop.jpg'),
(4, 'Lenovo ThinkPad P1', 'lenovo-thinkpad-p1', 'Station de travail mobile pour professionnels créatifs', 'Lenovo', 'Intel Core i7-13700H', '32 Go DDR5', '1 To SSD', '16" OLED 4K', 'NVIDIA RTX A2000 8 Go', 2799999, 3, 'assets/images/produits/thinkpad-p1.jpg'),
(5, 'Acer Aspire 5', 'acer-aspire-5', 'Ordinateur portable polyvalent et abordable pour étudiants', 'Acer', 'AMD Ryzen 5 5500U', '8 Go DDR4', '512 Go SSD', '15.6" FHD', 'AMD Radeon Graphics', 599999, 20, 'assets/images/produits/acer-aspire.jpg');

-- Utilisateurs (mot de passe: admin123 pour admin et client123 pour client)
INSERT INTO utilisateurs (nom, email, password, telephone, adresse, role) VALUES
('Admin Principal', 'admin@pcpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '771234567', 'Dakar, Plateau', 'admin'),
('Client Test', 'client@pcpro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '772345678', 'Dakar, Sacré Coeur', 'client'),
('Marie Diop', 'marie.diop@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '773456789', 'Dakar, Ouakam', 'client');

-- Commandes de test
INSERT INTO commandes (utilisateur_id, numero_commande, total, statut, methode_paiement, adresse_livraison, telephone_livraison) VALUES
(2, 'CMD-20231201-0001', 1499999, 'livree', 'carte_visa', 'Dakar, Sacré Coeur', '772345678'),
(3, 'CMD-20231205-0002', 899999, 'expediee', 'wave', 'Dakar, Ouakam', '773456789');

-- Détails des commandes
INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total) VALUES
(1, 1, 1, 1499999, 1499999),
(2, 3, 1, 899999, 899999);

-- Paramètres du site
INSERT INTO parametres (cle, valeur, type, categorie, description) VALUES
('site_name', 'PC Pro', 'texte', 'general', 'Nom du site'),
('site_email', 'contact@pcpro.com', 'texte', 'general', 'Email de contact'),
('currency', 'FCFA', 'texte', 'general', 'Devise utilisée'),
('tax_rate', '0.18', 'decimal', 'general', 'Taux de TVA'),
('shipping_fee', '0', 'decimal', 'livraison', 'Frais de livraison'),
('min_order_amount', '0', 'decimal', 'commande', 'Montant minimum de commande'),
('paypal_enabled', '1', 'boolean', 'paiement', 'Activer PayPal'),
('stripe_enabled', '1', 'boolean', 'paiement', 'Activer Stripe'),
('wave_enabled', '1', 'boolean', 'paiement', 'Activer Wave'),
('orange_money_enabled', '1', 'boolean', 'paiement', 'Activer Orange Money'),
('maintenance_mode', '0', 'boolean', 'general', 'Mode maintenance');

-- Coupons de réduction
INSERT INTO coupons (code, type, valeur, montant_minimum, date_debut, date_fin, utilisations_max, est_actif) VALUES
('WELCOME10', 'pourcentage', 10, 500000, '2023-01-01', '2024-12-31', 1000, 1),
('GAMING20', 'pourcentage', 20, 1000000, '2023-01-01', '2024-12-31', 500, 1),
('FIXE5000', 'montant_fixe', 5000, 200000, '2023-01-01', '2024-12-31', 1000, 1);

-- Avis de produits
INSERT INTO avis (produit_id, utilisateur_id, note, commentaire, est_approuve) VALUES
(1, 2, 5, 'Excellent ordinateur gaming, très performant pour tous les jeux récents!', 1),
(1, 3, 4, 'Très bon produit, juste un peu bruyant quand on pousse les performances', 1),
(3, 2, 5, 'Parfait pour le travail, très rapide et fiable', 1);

-- Contacts
INSERT INTO contacts (nom, email, telephone, sujet, message, statut) VALUES
('Jean Dupont', 'jean.dupont@email.com', '774567890', 'Demande d''informations', 'Bonjour, je voudrais savoir si vous avez le modèle X en stock.', 'resolu'),
('Sophie Martin', 'sophie.martin@email.com', '775678901', 'Problème de livraison', 'Ma commande n''est pas encore arrivée alors que la date estimée est dépassée.', 'en_cours');