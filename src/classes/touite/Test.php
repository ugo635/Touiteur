<?php

use iutnc\touiteur\touite\Touite;

require_once "../exception/MessageTooBig.php";
require_once "../exception/InvalidPropertyNameException.php";
require_once "Touite.php";

try {
    $touit = new Touite("Bonjour tout le monde #php #test");

    echo "=== Test création Touite ===" . PHP_EOL;

    echo "Message : " . $touit->message . PHP_EOL;
    echo "ID : " . $touit->id . PHP_EOL;

    echo "Date : ";
    print_r($touit->getDate());

    echo "Tags avec getListeTag() : ";
    print_r($touit->getListeTag());

    echo "Tags avec __get : ";
    print_r($touit->tags);

    echo PHP_EOL . "=== Test likes / dislikes ===" . PHP_EOL;

    $touit->addNote("user1", true);
    $touit->addNote("user2", true);
    $touit->addNote("user3", false);

    echo "Likes enregistrés : ";
    print_r($touit->like);

    echo "Compteur likes : " . $touit->CompteurLike . PHP_EOL;

    echo PHP_EOL . "=== Test message trop long ===" . PHP_EOL;

    $messageTropLong = str_repeat("a", 236);
    $touitTropLong = new Touite($messageTropLong);

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . PHP_EOL;
}

