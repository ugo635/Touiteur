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


        $repo = TouiteurRepository::getInstance();
        $moi = $_SESSION["username"] ?? "";
        $nbFollowers = $repo->countTagFollowers($tag);

        $touites = [];
        //recuperation touites a l'hashtag
        try{
            $touites= $repo->findTouitesByTag($tag);
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

        $url = urlencode($tag);
        //btn Suivre /ne plus suivre seulement si connecté
        $btn = "";
        if ($moi !== "") {
            $isFollowing = $repo->isFollowingTag($moi, $tag);
            $value = $isFollowing ? "unfollow" : "follow";
            $label = $isFollowing ? "Ne plus suivre" : "Suivre";
            $style = $isFollowing
                ? "bg-white text-black border border-gray-300 hover:text-red-600"
                : "bg-black text-white hover:bg-gray-800";
            $btn = <<<HTML
            <form method="POST" action="?action=voir-tag&tag=$url">
                <button type="submit" name="follow" value="$value"
                        class="px-3 py-1 text-sm rounded-full font-bold cursor-pointer $style">$label</button>
            </form>
            HTML;
        }

        //securise le tag
        $tag = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <div class="max-w-2xl mx-auto p-4 mt-4 bg-white shadow-sm rounded-lg border border-gray-200"
             
             <!-- pseudo, nb followers, date creation -->
             <div class="flex justify-between mb-4">
                <h1 class="text-xl font-bold">#$tag</h1>
                <div class="flex items-center space-x-4">
                    <!--Bouton followers-->
                    <a href="?action=tag-followers&tag=$url" class="text-black-500 hover:underline">
                       <span class="font-bold">$nbFollowers</span>
                       <span class="text-gray-500 text-sm">abonnés</span>      
                    </a>
                    $btn
                </div>
             </div>
             
             <!--listes touites-->             
             <div class="flex items-center flex-col gap-2 py-8">
                $touitesHtml
             </div>
        </div>
        HTML;
    }

    //clic sur suivre / ne plus suivre
    public function post(): string {
        $moi = $_SESSION["username"] ?? "";
        $tag = $_GET['tag'] ?? "";

        if ($moi !== "" && $tag !== "") {
            $repo = TouiteurRepository::getInstance();
            match ($_POST['follow'] ?? "") {
                'follow' => $repo->followTag($moi, $tag),
                'unfollow' => $repo->unfollowTag($moi, $tag),
                default => null,
            };
        }
        // On réaffiche la liste à jour
        return $this->get();
    }


}