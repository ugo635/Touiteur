<?php

namespace iutnc\touiteur\action\api;

use iutnc\touiteur\action\Action;

abstract class ApiAction extends Action {
    public function prefix(): string {
        return "";
    }

    public function suffixe(): string {
        return "";
    }

    abstract public function get() : string;
    abstract public function post() : string;

    public function getData(): mixed {
        return json_decode(file_get_contents("php://input"), true);
    }
}