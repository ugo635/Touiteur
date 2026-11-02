<?php

namespace iutnc\touiteur\action;

class DefaultAction extends Action {
    public function get(): string {
        return "Bienvenue!";
    }

    public function post(): string {
        return "";
    }
}