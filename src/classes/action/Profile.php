<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;

class Profile extends Action {
    public function get(): string {
        $user = $_GET['username'] ?? null;
        //TODO: vérification de si utilisateur existe
        if ($user === null|| $user === "") {
            return <<<HTML
        <div class="p-8 text-center">
            <h1 class="text-2xl font-bold mb-6 underline text-blue-600"><a href="?action=login">Vous n'etes pas connecter, veuillez vous connectez ici</a></h1>
        </div>
        HTML;
        }
        $user = htmlspecialchars($user, ENT_QUOTES, 'UTF-8');

        $repo = TouiteurRepository::getInstance();
        $nbFollowers = $repo->countFollowers($_GET['username']);
        $nbFollows = $repo->countFollows($_GET['username']);

        $dateCreation = unserialize($_SESSION['user'])->creationDate;

        //regarde si touites ou likes, touites par defaut
        $tab=$_GET['tab'] ?? "touites";
        $formatTouites=($tab === "touites") ? "border-black-500 text-black-600 font-bold hover:bg-gray-50" : "border-transparent text-gray-500 hover:text-black-500 hover:bg-gray-50";
        $formatLikes = ($tab === 'likes') ? "border-black-500 text-black-600 font-bold hover:bg-gray-50" : "border-transparent text-gray-500 hover:text-black-500 hover:bg-gray-50";

        $moi = unserialize($_SESSION["user"])->username;

        $estDejaSuivi = $repo->isFollowing($user, $moi);

        $followBtn = <<<HTML
                <div>
                    <button id="followBtn" class="rounded-full bg-red-700 p-2 cursor-pointer text-white" onclick="follow('$user', '$moi', false)">
                        Unfollow -
                    </button>
                 </div>
            HTML;

        $unfollowBtn = <<<HTML
            <div>
                <button id="followBtn" class="rounded-full bg-blue-700 p-2 cursor-pointer text-white" onclick="follow('$user', '$moi', true)">
                    Follow +
                </button>
             </div>
        HTML;

        $boutonFollow = $estDejaSuivi ? $followBtn : $unfollowBtn;


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
             
             $boutonFollow
             
             <!--barre touites/likes-->
             <div class="flex border-b border-gray-200 w-full">
                <a href="?action=profile&username=$user&tab=touites" class="flex-1 text-center py-3 border-b-2 transition duration-200 $formatTouites">
                Touites
                </a>
                <a href="?action=profile&username=$user&tab=likes" class="flex-1 text-center py-3 border-b-2 transition duration-200 $formatLikes">
                Likes
                </a>
             </div>
             
             <!--TODO: liste des touites de l'utilisateur / des likes de l'utilisateur--> 
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
                    document.querySelector("#followBtn").replaceWith('$unfollowBtn');
                    
                    // On change le nombre d'abonnée pour compter celui qu'on vient d'ajouter
                    const changement = ajouterOuPas ? 1 : -1;
                    document.querySelector("#nb-follow").textContent = document.querySelector("#nb-follow").textContent + changement;
            }
        </script>
        HTML;
    }

    public function post(): string {
        return "";
    }
}