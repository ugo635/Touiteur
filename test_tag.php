<?php

require_once __DIR__ . '/src/classes/touite/Touite.php';
require_once __DIR__ . '/src/classes/bdd/TouiteurBdd.php'; // adaptez selon le nom choisi
require_once __DIR__ . '/src/classes/render/Renderer.php';
require_once __DIR__ . '/src/classes/render/TouiteRenderer.php'; // si vous l'utilisez pour l'affichage

use iutnc\touiteur\bdd\TouiteurBdd;
use iutnc\touiteur\render\TouiteRenderer;

// 1. Initialisation de la connexion BD
try {
    TouiteurBdd::setConfig(__DIR__ . '/config/config.db.ini');
    $repo = TouiteurBdd::getInstance();
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// 2. Définition du tag à rechercher
$tagRecherche = 'informatique'; // Remplacer par le tag souhaité

echo "<h1>Touites contenant le tag : #{$tagRecherche}</h1>";

// 3. Récupération des résultats via le Repository
$listeTouites = $repo->findTouitesByTag($tagRecherche);

// 4. Affichage des messages
if (empty($listeTouites)) {
    echo "<p>Aucun touite trouvé pour ce tag.</p>";
} else {
    echo "<ul>";
    foreach ($listeTouites as $touite) {
        // Affichage direct du message :
        echo "<li>" . htmlspecialchars($touite->message) . "</li>";

        // Ou via votre renderer si vous préférez :
        // $renderer = new TouiteRenderer($touite);
        // echo $renderer->render(1);
    }
    echo "</ul>";
}