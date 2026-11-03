<?php

namespace iutnc\touiteur\action\api;

use iutnc\touiteur\action\Action;

abstract class ApiAction extends Action {

    public function get(): string {
        return "{}";
    }

    public abstract function post(): string;
}