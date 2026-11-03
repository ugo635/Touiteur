<?php

namespace iutnc\touiteur\render;

use iutnc\touiteur\touite\Touite;

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
            <div class="flex flex-col items-center">
                <p class="line-clamp-2 msg-content">{$message}</p>
                <button class="text-blue-700 cursor-pointer afficher-plus" onclick="afficherPlus('post-{$id}')">Afficher Plus</button>
            </div>
        HTML : <<<HTML
            <div class="flex flex-col items-center">
                <p class="msg-content">{$message}</p>
            </div>
        HTML;

        $boutons = ($selector == 2) ? <<<HTML
            <div class="flex flex-row items-center">
                <div class="score flex flex-row items-center">
                    <div class="like w-5 h-5 m-2 hover:cursor-pointer" onclick="like('{$id}', '{$username}', 1)">
                        <svg class="w-full h-full" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <path class="hover:fill-purple-500" fill="#FF00FF60" d="M34,9c-4.2,0-7.9,2.1-10,5.4C21.9,11.1,18.2,9,14,9C7.4,9,2,14.4,2,21c0,11.9,22,24,22,24s22-12,22-24 C46,14.4,40.6,9,34,9z"/>
                        </svg viewBox="0 0 48 48">
                    </div>
                    {$score}
                    <div class="dislike group w-5 h-5 m-2 hover:cursor-pointer" onclick="like('{$id}', '{$username}', -1)">
                        <svg class="w-full h-full" viewBox="180 90 320 310" xmlns="http://www.w3.org/2000/svg">
                          <g class="group-hover:fill-purple-500" fill="#E24B4A60" stroke="#A32D2D60" stroke-width="3" stroke-linejoin="round">
                            <path transform="rotate(-8 340 380)" d="M340 175C340 140 310 110 270 110C225 110 200 145 200 190C200 270 290 330 340 380L332 350L352 320L322 285L350 245L320 215Z"/>
                            <path transform="rotate(8 340 380)" d="M340 175L320 215L350 245L322 285L352 320L332 350L340 380C390 330 480 270 480 190C480 145 455 110 410 110C370 110 340 140 340 175Z"/>
                          </g>
                        </svg>
                    </div>
                </div>
            </div>
        HTML : "";


        // Le div sous article est la photo de profile (vide pr le moment)
        return <<<HTML
            <article class="flex gap-3 border border-gray-300 rounded-md w-100 p-4" id="post-{$id}" 
            user="{$username}">
                <div class="w-10 h-10 rounded-full bg-gray-200"></div> 
        
                <div>
                    <div>
                        <span class="font-bold">{$username}</span>
                        <span class="text-gray-500">{$date}</span>
                    </div>
        
                    {$msgHtml}
                    {$boutons}
                </div>
            </article>
            <script>
                function afficherPlus(post) {
                    let message = document.querySelector(`#${post} .msg-content`)
                    message.classList.remove("line-clamp-2")
                
                    let btn = document.querySelector(`#${post} .afficher-plus`)
                    btn.remove()
                }
                
                async function like(post, user, change = 1) {
                    // TODO: Faire l'action pr like
                    /*
                    const res = await fetch(`?action=like&post=${post}`, {
                        method: "POST",
                        body: JSON.stringify({
                            user: user
                        })
                    });
                     */
                }
            </script>
        HTML;
    }
}