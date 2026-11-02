<?php

require_once __DIR__ . '/vendor/autoload.php';

use iutnc\touiteur\dispatch\Dispatcher;
use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\render\RendererFactory;


$dispatcher = new Dispatcher();
$dispatcher->run();