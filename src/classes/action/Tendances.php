<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;

class Tendances extends Action {

    public function get(): string {
        try{
            $tags = TouiteurRepository::getInstance()->findTendances();
        } catch (\Exception $e) {
            //si rien on passe ensuite il sera dis qu'il n'y a pas de touites
        }

        //si vide on dis qu'il n'y en a pas
        $tagsHtml = "<div class='flex flex-col w-80'>";
        if (empty($tags)) {
            $tagsHtml = '<p class="text-gray-500 text-center py-8">Aucun tags.</p>';
        } else { //sinon on affiche les touites avec le renderer
            foreach ($tags as $tag) {
                $nomTag = $tag["tag"];
                $nbTag = $tag["nb"];
                $tagsHtml .= <<<HTML
                    <div class="flex justify-between gap-4 p-2 border rounded-sm m-2">
                        <a href="?action=voir-tag&tag=$nomTag" class="text-blue-600">#$nomTag</a>
                        <span>$nbTag followers</span>
                    </div>
                HTML;

            }
        }

        $tagsHtml .= "</div>";

        return <<<HTML
             <!--listes touites-->             
             <div class="flex items-center flex-col gap-2 py-8">
                $tagsHtml
             </div>
        HTML;
    }

    public function post(): string {
        return "";
    }
}