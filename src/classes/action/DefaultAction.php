<?php

namespace iutnc\touiteur\action;

class DefaultAction extends Action {
    public function get(): string {
        return <<<HTML
        <textarea id='touite' name='touite' placeholder="Touitez!"></textarea>
        <button type='submit'>Envoyer</button>
        
        <br>
        
        HTML;
    }


    public function post(): string {
        return "";
    }
}