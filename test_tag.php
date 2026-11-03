<?php

require_once __DIR__ . '/src/classes/exception/MessageTooBig.php';
require_once __DIR__ . '/src/classes/exception/InvalidPropertyNameException.php';
require_once __DIR__ . '/src/classes/users/User.php';
require_once __DIR__ . '/src/classes/touite/Touite.php';
require_once __DIR__ . '/src/classes/bdd/TouiteurRepository.php';
require_once __DIR__ . '/src/classes/render/Renderer.php';
require_once __DIR__ . '/src/classes/render/TouiteRenderer.php';

use iutnc\touiteur\bdd\TouiteurRepository;

// 1. Initialisation de la connexion BD
try {
    TouiteurRepository::setConfig(__DIR__ . '/config/config.db.ini');
    $repo = TouiteurRepository::getInstance();
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage() . "\n");
}

// 2. Tag recherché
$tagRecherche = 'informatique';

echo "<h1>Touites contenant le tag : #{$tagRecherche}</h1>\n";

// 3. Recherche
$listeTouites = $repo->findTouitesByTag($tagRecherche);

// 4. Affichage
if (empty($listeTouites)) {
    echo "<p>Aucun touite trouvé pour ce tag.</p>\n";
} else {
    echo "<ul>\n";
    foreach ($listeTouites as $touite) {
        echo "<li>" . htmlspecialchars($touite->message) . "</li>\n";
    }
    echo "</ul>\n";
}