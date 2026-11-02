<?php

namespace iutnc\touiteur\render;

use iutnc\touiteur\touite\Touite;

class TouiteRenderer implements Renderer {
    private Touite $touite;
    public function __construct(Touite $t) {
        $this->touite = $t;
    }

    public function render(): string {
        $message = $this->touite->message;
        $date = $this->touite->getDate();
        return <<<HTML
            <div class="w-300 h-150">
                <span>
                    $message
                </span>
                <span class="text-gray-600">
                    $date
                </span>
            </div>
        HTML;
    }
}