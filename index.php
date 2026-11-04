<?php

require_once __DIR__ . '/vendor/autoload.php';

use iutnc\touiteur\dispatch\Dispatcher;
use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\render\RendererFactory;

use iutnc\touiteur\bdd\TouiteurRepository;

session_start();
TouiteurRepository::setConfig(__DIR__ . '/config/config.db.ini');

$dispatcher = new Dispatcher();
$dispatcher->run();