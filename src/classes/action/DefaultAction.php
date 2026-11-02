<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\render\RendererFactory;
use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\users\User;

class DefaultAction extends Action {
    public function get(): string {
        $user = new User("ugo635");
        $touite = new Touite($user, "Hello World");

        return "Bienvenue!" . RendererFactory::getRenderer($touite)->render();
    }

    public function post(): string {
        return "";
    }
}