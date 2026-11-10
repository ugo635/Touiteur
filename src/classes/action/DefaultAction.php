<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\render\RendererFactory;


class DefaultAction extends Action {
    public function get(): string {
            //recuperation touites
            try{
                $touites= TouiteurRepository::getInstance()->findAllTouites();
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
        
             <!--listes touites-->             
             <div class="flex items-center flex-col gap-2 py-8">
                $touitesHtml
             </div>
        HTML;
    }


    public function post(): string {
        return "";
    }
}