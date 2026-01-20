$(document).ready(function() {
    
    // Gérer l'ajout au panier
    $('.add-to-cart').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const productId = button.data('id');
        const productName = button.data('name');
        const quantity = button.closest('form').find('#quantity') ? 
                         button.closest('form').find('#quantity').val() : 1;
        
        // Désactiver le bouton pendant la requête
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ajout...');
        
        $.ajax({
            url: '../api/ajax-panier.php',
            type: 'POST',
            data: {
                action: 'add',
                product_id: productId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mettre à jour le compteur du panier
                    updateCartCount(response.cart_count);
                    
                    // Afficher une notification
                    showNotification('success', response.message);
                    
                    // Mettre à jour le bouton
                    button.html('<i class="fas fa-check"></i> Ajouté !');
                    setTimeout(() => {
                        button.html('<i class="fas fa-cart-plus"></i> Ajouter au panier');
                        button.prop('disabled', false);
                    }, 2000);
                } else {
                    showNotification('error', response.message);
                    button.html('<i class="fas fa-cart-plus"></i> Ajouter au panier');
                    button.prop('disabled', false);
                }
            },
            error: function() {
                showNotification('error', 'Une erreur est survenue');
                button.html('<i class="fas fa-cart-plus"></i> Ajouter au panier');
                button.prop('disabled', false);
            }
        });
    });
    
    // Mettre à jour la quantité dans le panier
    $(document).on('change', '.cart-quantity', function() {
        const input = $(this);
        const productId = input.data('id');
        const quantity = input.val();
        
        updateCartItem(productId, quantity);
    });
    
    // Supprimer un produit du panier
    $(document).on('click', '.remove-from-cart', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const productId = button.data('id');
        
        if (confirm('Voulez-vous vraiment retirer ce produit du panier ?')) {
            removeCartItem(productId);
        }
    });
    
    // Vider le panier
    $('.clear-cart').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('Voulez-vous vraiment vider votre panier ?')) {
            $.ajax({
                url: '../api/ajax-panier.php',
                type: 'POST',
                data: { action: 'clear' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateCartCount(0);
                        showNotification('success', response.message);
                        // Recharger la page si on est sur la page panier
                        if (window.location.pathname.includes('panier.php')) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    }
                }
            });
        }
    });
    
    // Fonction pour mettre à jour un produit du panier
    function updateCartItem(productId, quantity) {
        $.ajax({
            url: '../api/ajax-panier.php',
            type: 'POST',
            data: {
                action: 'update',
                product_id: productId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateCartCount(response.cart_count);
                    
                    if (quantity > 0) {
                        showNotification('success', response.message);
                    }
                    
                    // Recharger la page si on est sur la page panier
                    if (window.location.pathname.includes('panier.php')) {
                        setTimeout(() => location.reload(), 500);
                    }
                } else {
                    showNotification('error', response.message);
                    // Recharger pour récupérer les bonnes valeurs
                    if (window.location.pathname.includes('panier.php')) {
                        setTimeout(() => location.reload(), 500);
                    }
                }
            }
        });
    }
    
    // Fonction pour supprimer un produit du panier
    function removeCartItem(productId) {
        $.ajax({
            url: '../api/ajax-panier.php',
            type: 'POST',
            data: {
                action: 'remove',
                product_id: productId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateCartCount(response.cart_count);
                    showNotification('success', response.message);
                    
                    // Supprimer la ligne du tableau si on est sur la page panier
                    if (window.location.pathname.includes('panier.php')) {
                        $(`[data-id="${productId}"]`).closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            // Recharger si le panier est vide
                            if ($('.cart-table tbody tr').length === 0) {
                                setTimeout(() => location.reload(), 500);
                            }
                        });
                    }
                } else {
                    showNotification('error', response.message);
                }
            }
        });
    }
    
    // Fonction pour mettre à jour le compteur du panier
    function updateCartCount(count) {
        // Mettre à jour le badge du panier dans la navbar
        const cartBadge = $('.navbar .fa-shopping-cart').siblings('.badge');
        if (count > 0) {
            if (cartBadge.length) {
                cartBadge.text(count);
            } else {
                $('.navbar .fa-shopping-cart').after(
                    `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">${count}</span>`
                );
            }
        } else {
            cartBadge.remove();
        }
        
        // Mettre à jour le compteur dans la page panier si présent
        const cartCountElement = $('.cart-count');
        if (cartCountElement.length) {
            cartCountElement.text(count);
        }
    }
    
    // Fonction pour afficher les notifications
    function showNotification(type, message) {
        // Créer la notification
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        const notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show notification-alert" 
                 role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
                <i class="fas ${icon} me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        // Ajouter à la page
        $('body').append(notification);
        
        // Supprimer automatiquement après 3 secondes
        setTimeout(() => {
            notification.alert('close');
        }, 3000);
    }
    
    // Charger le nombre d'articles dans le panier au chargement de la page
    $.ajax({
        url: '../api/ajax-panier.php',
        type: 'POST',
        data: { action: 'get' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateCartCount(response.cart_count);
            }
        }
    });
});