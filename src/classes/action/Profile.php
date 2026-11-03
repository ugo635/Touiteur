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

        //TODO: remplacer val par defaut
        $nbFollowers = 0;
        $nbFollows = 0;
        $dateCreation = getDate();

        $tab=$_GET['tab'] ?? "touites";
        $formatTouites=($tab === "touites") ? "border-blue-500 text-blue-600 font-bold" : "border-transparent text-gray-500 hover:text-blue-500 hover:bg-gray-50";
        $styleTabLikes = ($tab === 'likes') ? "border-blue-500 text-blue-600 font-bold" : "border-transparent text-gray-500 hover:text-blue-500 hover:bg-gray-50";

        return <<<HTML
        <div class="max-w-2xl mx-auto p-4 mt-4 bg-white shadow-sm rounded-lg border border-gray-200"
             
             <!-- pseudo, nb followers, nb follows, date creation -->
             <div class="flex justify-between items-center mb-4">
                <h1 class="text-xl font-bold">@$user</h1>
                <div class="flex items-center space-x-4">
                    <!--Bouton followers-->
                    <a href="?action=followers&user=$user" class="text-blue-500 hover:underline">
                    <span class="font-bold text-gray-900 group-hover:text-blue-500">$nbFollowers</span>
                        <span class="text-gray-500 text-sm">abonnés</span>
                        
                    </a>
                    <!--Bouton follows-->
                    <a href="?action=follows&user=$user" class="text-blue-500 hover:underline">$nbFollows follows</a>
             <!--TODO: liste des touites de l'utilisateur--> 
             
        </div>
        HTML;
    }

    public function post(): string {
        return "";
    }
}