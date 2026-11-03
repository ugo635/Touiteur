<?php

namespace iutnc\touiteur\action;

class Profile extends Action{
    public function get(): string {
        $user = $_GET['username'] ?? null;
        //TODO: vérification de si utilisateur existe
        if ($user === null|| $user === "") {
            return <<<HTML
        <div class="p-8 text-center">
            <h1 class="text-2xl font-bold mb-6">Vous n'etes pas connecté</h1>
        </div>
        HTML;
        }
        $user = htmlspecialchars($user, ENT_QUOTES, 'UTF-8');
        return <<<HTML
        <div class="p-8 text-center">
            <h1 class="text-2xl font-bold mb-6">@$user</h1>
             <!--TODO: liste des touites de l'utilisateur--> 
        </div>
        HTML;
    }

    public function post(): string {
        return "";
    }
}