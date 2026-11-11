<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\render\Renderer;
use iutnc\touiteur\render\RendererFactory;


class Profile extends Action {
    public function get(): string {
        $user = $_GET['username'] ?? "";
        if ($user === null|| $user === "") {
            return <<<HTML
                <div class="p-8 text-center">
                    <h1 class="text-2xl font-bold mb-6 underline text-blue-600"><a href="?action=login">Vous n'etes pas connecter, veuillez vous connectez ici</a></h1>
                </div>
            HTML;
        }

        $user = htmlspecialchars($user, ENT_QUOTES, 'UTF-8');
        
        //cherche l'utilisateur dans la bdd et si il n'existe pas retourne un msg
        $repo = TouiteurRepository::getInstance();
        $profil = $repo->findUserByUsername($user);
        if ($profil === null) return <<<HTML
            <div class="p-8 text-center">
                <h1 class="text-2xl font-bold mb-6 underline text-red-600">L'utilisateur n'existe pas</h1>
            </div>
        HTML;


        $nbFollowers = $repo->countFollowers($user);
        $nbFollows = $repo->countFollows($user);
        $dateCreation = $profil->creationDate;

        //regarde si touites ou likes, touites par defaut
        $tab=$_GET['tab'] ?? "touites";
        $formatTouites=($tab === "touites") ? "border-black-500 text-black-600 font-bold hover:bg-gray-50" : "border-transparent text-gray-500 hover:text-black-500 hover:bg-gray-50";
        $formatLikes = ($tab === 'likes') ? "border-black-500 text-black-600 font-bold hover:bg-gray-50" : "border-transparent text-gray-500 hover:text-black-500 hover:bg-gray-50";

        $moi = $_SESSION["username"] ?? "";

        $estDejaSuivi = $repo->isFollowing($moi, $user) && $moi !== $user && $user !== "" && $moi !== "";

        $unfollowBtn = <<<HTML
                <button class="rounded-full bg-red-700 p-2 cursor-pointer text-white" onclick="follow('$user', '$moi', false)">
                    Unfollow -
                </button>
            HTML;

        $followBtn = <<<HTML
            <button class="rounded-full bg-blue-700 p-2 cursor-pointer text-white" onclick="follow('$user', '$moi', true)">
                Follow +
            </button>
        HTML;

        $boutonFollow = $estDejaSuivi ? $unfollowBtn : $followBtn;
        if ($moi === $user || $moi==="") $boutonFollow = ""; // Pas de btn follow pr soit meme ou si on est pas connécté


        //recuperation touites de l'utilisateur
        try{
            $touites= TouiteurRepository::getInstance()->findTouitesByUser($user);
        } catch (\Exception $e) {
           //si rien on passe ensuite il sera dis qu'il n'y a pas de touites
            echo $e->getMessage();
        }

        //si vide on dis qu'il n'y en a pas
        $touitesHtml = '';
        if (empty($touites)) {
            $touitesHtml = '<p class="text-gray-500 text-center py-8">Aucun touite pour cet utilisateur.</p>';
        } else { //sinon on affiche les touites avec le renderer
            foreach ($touites as $touite) {
                $touitesHtml .= RendererFactory::getRenderer($touite)->render(Renderer::LONG);
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
                    <span id="nb-follow" class="font-bold">$nbFollowers</span>
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
             
             <div id="followBtn">
                $boutonFollow
             </div>
             
             <!--barre touites/likes-->
             <div class="flex border-b border-gray-200 w-full">
                <a href="?action=profile&username=$user&tab=touites" class="flex-1 text-center py-3 border-b-2 transition duration-200 $formatTouites">
                Touites
                </a>
                <a href="?action=profile&username=$user&tab=likes" class="flex-1 text-center py-3 border-b-2 transition duration-200 $formatLikes">
                Likes
                </a>
             </div>
             
             <!--listes touites-->            
             <div class="flex items-center flex-col gap-2 py-8">
                $touitesHtml
             </div>
        </div>
        <script>
            async function follow(persQuiVaEtreSuivi, persQuiVaSuivre, ajouterOuPas) {
                const res = await fetch("?action=follow", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            suivi: persQuiVaEtreSuivi,
                            suiveur: persQuiVaSuivre,
                            ajout: ajouterOuPas
                        })
                    });
            
                    if (!res.ok) { // Ne devrait pas arriver
                        console.error("Erreur lors du follow");
                        return;
                    }
            
                    // Tout est bon donc on fait les changement coté client, on remplace le btn par son contraire
                    // (follow -> unfollow) ou (unfollow -> follow)
                    if (ajouterOuPas) document.querySelector("#followBtn").innerHTML = `$unfollowBtn`;
                    else document.querySelector("#followBtn").innerHTML = `$followBtn`;
                    
                    // On change le nombre d'abonnée pour compter celui qu'on vient d'ajouter
                    const changement = ajouterOuPas ? 1 : -1;
                    document.querySelector("#nb-follow").textContent = parseInt(document.querySelector("#nb-follow").textContent) + changement;
            }
        </script>
        HTML;
    }

    public function post(): string {
        return "";
    }
}