<?php
// 500.php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur serveur - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container text-center py-5">
        <i class="fas fa-server fa-5x text-danger mb-4"></i>
        <h1 class="display-1 fw-bold">500</h1>
        <h2 class="mb-4">Erreur serveur</h2>
        <p class="lead mb-4">Une erreur interne s'est produite. Veuillez réessayer plus tard.</p>
        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-home"></i> Retour à l'accueil
        </a>
    </div>
</body>
</html>