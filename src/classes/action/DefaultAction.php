<?php

namespace iutnc\touiteur\action;

class DefaultAction extends Action {
    public function get(): string {
        return <<<HTML

        HTML;
    }


    public function post(): string {
        return "";
    }
}