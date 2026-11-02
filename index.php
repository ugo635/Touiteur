<?php

require_once __DIR__ . '/vendor/autoload.php';

use iutnc\touiteur\dispatch\Dispatcher;

$dispatcher = new Dispatcher();
$dispatcher->run();