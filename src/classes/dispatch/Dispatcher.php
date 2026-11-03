<?php

namespace iutnc\touiteur\dispatch;

use iutnc\touiteur\action\DefaultAction;
use iutnc\touiteur\action\AddTouite;// a faire classe
use iutnc\touiteur\action\Profile;//a faire classe

class Dispatcher {
    private ?string $action = null;

    public function __construct() {
        $this->action = $_GET["action"] ?? "default";
    }

    public function run(): void {
        $act = match ($this->action) {
            'add-touite'=> new AddTouite(),
            'profile' => new Profile(),
            default => new DefaultAction(),

        };

        $this->renderPage($act->prefix());
        $this->renderPage($act()); // Appel __invoke() de Action
        $this->renderPage($act->suffixe());

    }

    private function renderPage(string $html) {
        echo $html;
    }

}