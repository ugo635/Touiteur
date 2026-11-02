<?php

namespace iutnc\touiteur\render;

use iutnc\touiteur\touite\Touite;

class TouiteRenderer implements Renderer {
    private Touite $touite;
    public function __construct(Touite $t) {
        $this->touite = $t;
    }

    public function render(): string {
        return <<<HTML
            <div class="w-300 h-150">
                <span>
                    $this->touite->message
                </span>
                <span class="text-gray-600">
                    $this->touite->date
                </span>
            </div>
        HTML;
    }
}