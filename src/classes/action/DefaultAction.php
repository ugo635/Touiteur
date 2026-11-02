<?php

namespace iutnc\touiteur\action;

class DefaultAction extends Action {
    public function get(): string {
        return <<<HTML
        <a href=''> <button type='Home'>Accueil</button></a>
        
        <br>
        <textarea id='touite' name='touite' placeholder="Touitez!"></textarea>
        <button type='submit'>Envoyer</button>
        
        <br>
        
        HTML;
    }


    public function post(): string {
        return "";
    }
}