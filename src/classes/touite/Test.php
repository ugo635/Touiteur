<?php

use iutnc\touiteur\touite\Touite;

require_once "Touite.php";

try {
    $touit = new Touite("Bonjour tout le monde #php #test");

    echo "Message : " . $touit->message . PHP_EOL;

    echo "Date : ";
    print_r($touit->getDate());

    echo "Tags : ";
    print_r($touit->getListeTag());

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . PHP_EOL;
}

