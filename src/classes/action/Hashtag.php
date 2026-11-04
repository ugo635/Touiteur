<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\render\RendererFactory;

class Hashtag extends Action
{
    public function get(): string {
        $tag = $_GET['tag'] ?? null;
        if ($tag === null|| $tag === "") {
            return <<<HTML
        <div class="p-8 text-center">
            <h1 class="text-2xl font-bold mb-6">tag non existant</h1>
        </div>
        HTML;
        }

        //TODO: remplacer val par defaut
        $nbFollowers = 0;


        //recuperation touites a l'hashtag
        try{
            $touites= TouiteurRepository::getInstance()->findTouitesByTag($tag);
        } catch (\Exception $e) {
            //si rien on passe ensuite il sera dis qu'il n'y a pas de touites
        }

        //si vide on dis qu'il n'y en a pas
        $touitesHtml = '';
        if (empty($touites)) {
            $touitesHtml = '<p class="text-gray-500 text-center py-8">Aucun touite pour cet hashtag.</p>';
        } else { //sinon on affiche les touites avec le renderer
            foreach ($touites as $touite) {
                $touitesHtml .= RendererFactory::getRenderer($touite)->render();
            }
        }
        return <<<HTML
        <div class="max-w-2xl mx-auto p-4 mt-4 bg-white shadow-sm rounded-lg border border-gray-200"
             
             <!-- pseudo, nb followers, nb follows, date creation -->
             <div class="flex justify-between mb-4">
                <h1 class="text-xl font-bold">#$tag</h1>
                <div class="flex items-center space-x-4">
                    <!--Bouton followers-->
                    <a href="?action=followers&tag=$tag" class="text-black-500 hover:underline">
                    <span class="font-bold">$nbFollowers</span>
                    <span class="text-gray-500 text-sm">abonnés</span>      
                    </a>
                </div>
             </div>
             
             <!--listes touites-->             
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