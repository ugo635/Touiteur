<?php

require_once __DIR__ . '/src/classes/exception/MessageTooBig.php';
require_once __DIR__ . '/src/classes/exception/InvalidPropertyNameException.php';
require_once __DIR__ . '/src/classes/users/User.php';
require_once __DIR__ . '/src/classes/touite/Touite.php';
require_once __DIR__ . '/src/classes/bdd/TouiteurRepository.php';
require_once __DIR__ . '/src/classes/render/Renderer.php';
require_once __DIR__ . '/src/classes/render/TouiteRenderer.php';
require_once __DIR__ . '/src/classes/render/RendererFactory.php';

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\render\RendererFactory;
use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\users\User;

try {
    TouiteurRepository::setConfig(__DIR__ . '/config/config.db.ini');
    $repo = TouiteurRepository::getInstance();
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage() . "\n");
}

$tagRecherche = 'informatique';

// Optionnel : création et sauvegarde d'un exemple pour s'assurer que la base contient des données
$auteur = new User("Alice");
$nouveauTouite = new Touite("Je développe la persistance pour le projet en #informatique et #web !", $auteur);
$repo->saveTouite($nouveauTouite);

// Récupération des touites portant le tag
$listeTouites = $repo->findTouitesByTag($tagRecherche);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Recherche par Tag</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="p-6 bg-gray-50 flex flex-col items-center">
<h1 class="text-2xl font-bold mb-4">Touites avec le tag : #<?= htmlspecialchars($tagRecherche) ?></h1>

<div class="space-y-4">
    <?php if (empty($listeTouites)): ?>
        <p class="text-gray-500">Aucun touite trouvé pour ce tag.</p>
    <?php else: ?>
        <?php foreach ($listeTouites as $touite): ?>
            <?= RendererFactory::getRenderer($touite)->render() ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>