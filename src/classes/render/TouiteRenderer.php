<?php

namespace iutnc\touiteur\render;

use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\bdd\TouiteurRepository;

class TouiteRenderer implements Renderer {
    private Touite $touite;
    public function __construct(Touite $t) {
        $this->touite = $t;
    }

    public function render(int $selector = 1): string {
        $message = $message = preg_replace(
            '/(?<!\w)#(\w+)/u',
            '<a href="?action=voir-tag&tag=$1" class="text-blue-600 hover:underline">#$1</a>',
            $this->touite->message
        );

        $date = $this->touite->getDate();
        $id = $this->touite->id;
        $username = $this->touite->user->username;
        $score = $this->touite->compteurLike;

        $msgHtml = ($selector == 1) ? <<<HTML
            <div class="flex flex-col">
                <p class="line-clamp-2 msg-content">{$message}</p>
                <button class="text-blue-700 cursor-pointer afficher-plus" onclick="afficherPlus('post-{$id}')">Afficher Plus</button>
            </div>
        HTML : <<<HTML
            <div class="flex flex-col">
                <p class="msg-content">{$message}</p>
            </div>
        HTML;

        $moi = $_SESSION["username"] ?? "";

        $estDejaSuivi = TouiteurRepository::getInstance()->isFollowing($moi, $username)
            && $moi !== $username && $username !== "" && $moi !== "";

        $unfollowBtn = <<<HTML
            <button class="follow-btn rounded-full bg-red-700 p-1 cursor-pointer text-white" onclick="follow('{$id}', '{$username}', '{$moi}', false)">
                Unfollow -
            </button>
        HTML;

        $followBtn = <<<HTML
            <button class="follow-btn rounded-full bg-blue-700 p-1 cursor-pointer text-white" onclick="follow('{$id}', '{$username}', '{$moi}', true)">
                Follow +
            </button>
        HTML;

        $boutonFollow = $estDejaSuivi ? $unfollowBtn : $followBtn;
        if ($moi === $username || $moi === "") $boutonFollow = ""; // Pas de btn follow pr soit meme ou si on est pas connecté

        $hidden = $selector == Renderer::COMPACT ? "hidden" : "";
        $boutons = <<<HTML
            <div class="flex flex-row items-center score {$hidden}">
                <div class="flex flex-row items-center">
                    <button class="like w-5 h-5 m-2 hover:cursor-pointer" onclick="like('{$id}', '{$username}', 1)">
                        <svg class="w-full h-full" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <path class="hover:fill-purple-500" fill="#FF00FF60" d="M34,9c-4.2,0-7.9,2.1-10,5.4C21.9,11.1,18.2,9,14,9C7.4,9,2,14.4,2,21c0,11.9,22,24,22,24s22-12,22-24 C46,14.4,40.6,9,34,9z"/>
                        </svg>
                    </button>
                    <span class="score-value">{$score}</span>
                    <button class="dislike group w-5 h-5 m-2 hover:cursor-pointer" onclick="like('{$id}', '{$username}', -1)">
                        <svg class="w-full h-full" viewBox="180 90 320 310" xmlns="http://www.w3.org/2000/svg">
                            <g class="group-hover:fill-purple-500" fill="#E24B4A60" stroke="#A32D2D60" stroke-width="3" stroke-linejoin="round">
                                <path transform="rotate(-8 340 380)" d="M340 175C340 140 310 110 270 110C225 110 200 145 200 190C200 270 290 330 340 380L332 350L352 320L322 285L350 245L320 215Z"/>
                                <path transform="rotate(8 340 380)" d="M340 175L320 215L350 245L322 285L352 320L332 350L340 380C390 330 480 270 480 190C480 145 455 110 410 110C370 110 340 140 340 175Z"/>
                            </g>
                        </svg>
                    </button>
                </div>
            </div>
        HTML;


        // Le div sous article est la photo de profile (vide pr le moment)
        return <<<HTML
            <article class="flex gap-3 border border-gray-300 rounded-md w-100 p-4" id="post-{$id}" 
            user="{$username}">
                <div class="w-10 h-10 min-w-10 rounded-full bg-gray-200"></div> 
        
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <a href="?action=profile&username={$username}" class="font-bold hover:underline" >{$username}</a>
                            <span class="text-gray-500">{$date}</span>
                        </div>
                        <div id="followBtn-{$id}">
                            {$boutonFollow}
                        </div>
                    </div>
        
                    {$msgHtml}
                    {$boutons}
                </div>
            </article>
            <script>
                function afficherPlus(post) {
                    let message = document.querySelector("#" + post + " .msg-content")
                    message.classList.remove("line-clamp-2")
            
                    let btn = document.querySelector("#" + post + " .afficher-plus")
                    btn.remove()
            
                    let score = document.querySelector("#" + post + " .score")
                    score.classList.remove("hidden")
                }
            
                async function like(post, user, change = 1) {
                    const res = await fetch("?action=like", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            post: post.replace("post-", ""),
                            user: user,
                            change: change
                        })
                    });
            
                    if (!res.ok) {
                        console.error("Erreur lors du vote");
                        return;
                    }
            
                    const result = (await res.text()).trim();
            
                    const article = document.querySelector("#post-" + post);
                    const likeBtn = article.querySelector(".like");
                    const dislikeBtn = article.querySelector(".dislike");
                    const score = article.querySelector(".score-value");
            
                    const activeBtn = change === 1 ? likeBtn : dislikeBtn;
                    const inactiveBtn = change === 1 ? dislikeBtn : likeBtn;
            
                    // On change la couleur des boutons et update le score
                    if (result === "Added") {
                        activeBtn.querySelector(change === 1 ? "path" : "g").classList.add("fill-purple-500");
                        score.textContent = parseInt(score.textContent) + change;
                    } else if (result === "Removed") {
                        activeBtn.querySelector(change === 1 ? "path" : "g").classList.remove("fill-purple-500");
                        score.textContent = parseInt(score.textContent) - change;
                    } else if (result === "Replaced") {
                        inactiveBtn.querySelector(change === 1 ? "g" : "path").classList.remove("fill-purple-500");
                        activeBtn.querySelector(change === 1 ? "path" : "g").classList.add("fill-purple-500");
                        score.textContent = parseInt(score.textContent) + change * 2;
                    }
                }
                
                async function follow(post, persQuiVaEtreSuivi, persQuiVaSuivre, ajouterOuPas) {
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
                
                    // Tout est bon donc on retrouve le btn du post et on le change en son contraire
                    // (follow -> unfollow) ou (unfollow -> follow)
                    const btn = document.querySelector("#post-" + post + " .follow-btn");
                    btn.textContent = ajouterOuPas ? "Unfollow -" : "Follow +";
                    btn.classList.toggle("bg-red-700", ajouterOuPas);
                    btn.classList.toggle("bg-blue-700", !ajouterOuPas);
                    btn.onclick = () => follow(post, persQuiVaEtreSuivi, persQuiVaSuivre, !ajouterOuPas);
                }
            </script>
        HTML;
    }
}