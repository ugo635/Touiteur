<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\render\RendererFactory;


class Profile extends Action{
    public function get(): string {
        $user = $_GET['username'] ?? null;
        //TODO: vérification de si utilisateur existe
        if ($user === null|| $user === "") {
            return <<<HTML
        <div class="p-8 text-center">
            <h1 class="text-2xl font-bold mb-6">Compte non existant</h1>
        </div>
        HTML;
        }
        $user = htmlspecialchars($user, ENT_QUOTES, 'UTF-8');

        //TODO: remplacer val par defaut
        $nbFollowers = 0;
        $nbFollows = 0;
        $dateCreation = "dd/mm/yyyy";

        //regarde si touites ou likes, touites par defaut
        $tab=$_GET['tab'] ?? "touites";
        $formatTouites=($tab === "touites") ? "border-black-500 text-black-600 font-bold hover:bg-gray-50" : "border-transparent text-gray-500 hover:text-black-500 hover:bg-gray-50";
        $formatLikes = ($tab === 'likes') ? "border-black-500 text-black-600 font-bold hover:bg-gray-50" : "border-transparent text-gray-500 hover:text-black-500 hover:bg-gray-50";

        //recuperation touites de l'utilisateur
        try{
            $touites= TouiteurRepository::getInstance()->findTouitesByUsername($user);
        } catch (\Exception $e) {
           //si rien on passe ensuite il sera dis qu'il n'y a pas de touites
        }

        //si vide on dis qu'il n'y en a pas
        $touitesHtml = '';
        if (empty($touites)) {
            $touitesHtml = '<p class="text-gray-500 text-center py-8">Aucun touite pour cet utilisateur.</p>';
        } else { //sinon on affiche les touites avec le renderer
            foreach ($touites as $touite) {
                $touitesHtml .= RendererFactory::getRenderer($touite)->render();
            }
        }
        return <<<HTML
        <div class="max-w-2xl mx-auto p-4 mt-4 bg-white shadow-sm rounded-lg border border-gray-200"
             
             <!-- pseudo, nb followers, nb follows, date creation -->
             <div class="flex justify-between mb-4">
                <h1 class="text-xl font-bold">@$user</h1>
                <div class="flex items-center space-x-4">
                    <!--Bouton followers-->
                    <a href="?action=followers&user=$user" class="text-black-500 hover:underline">
                    <span class="font-bold">$nbFollowers</span>
                    <span class="text-gray-500 text-sm">abonnés</span>      
                    </a>
                    
                    <!--Bouton follows-->
                    <a href="?action=follows&user=$user" class="text-black-500 hover:underline">
                        <span class="font-bold">$nbFollows</span>
                        <span class="text-gray-500 text-sm">abonnements</span>      
                    </a>
                </div>
             </div>
             
             <!--date creation-->
             <p class="text-gray-500 mb-4">$dateCreation</p>    
             
             <!--barre touites/likes-->
             <div class="flex border-b border-gray-200 w-full">
                <a href="?action=profile&username=$user&tab=touites" class="flex-1 text-center py-3 border-b-2 transition duration-200 $formatTouites">
                Touites
                </a>
                <a href="?action=profile&username=$user&tab=likes" class="flex-1 text-center py-3 border-b-2 transition duration-200 $formatLikes">
                Likes
                </a>
             </div>
             
             <div class="flex items-center flex-col gap-2 py-8">
                $touitesHtml
             </div>
        </div>
        HTML;
    }

    public function post(): string {
        return "";
    }
}