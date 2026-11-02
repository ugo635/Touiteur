<?php

namespace iutnc\touiteur\render;

use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\users\User;

class TouiteRenderer implements Renderer {
    private Touite $touite;
    public function __construct(Touite $t) {
        $this->touite = $t;
    }

    public function render(): string {
        $message = $this->touite->message;
        $date = $this->touite->getDate();
        $username = $this->touite->user->username;
        return <<<HTML
            <article class="flex gap-3 border border-gray-300 rounded-md w-150 p-4">
                <div class="w-10 h-10 rounded-full bg-gray-200"></div>
        
                <div>
                    <div>
                        <span class="font-bold">{$username}</span>
                        <span class="text-gray-500">{$date}</span>
                    </div>
        
                    <p>{$message}</p>
                </div>
            </article>
        HTML;
    }
}