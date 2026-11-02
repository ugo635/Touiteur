<?php

namespace iutnc\touiteur\action;

class DefaultAction extends Action {
    public function get(): string {
        $home = "<a href='' <button type='button'>Home</button>";
        return $home;
    }


    public function post(): string {
        return "";
    }
}