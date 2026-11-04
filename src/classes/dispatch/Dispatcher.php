<?php

namespace iutnc\touiteur\dispatch;

use iutnc\touiteur\action\api\Like;
use iutnc\touiteur\action\DefaultAction;
use iutnc\touiteur\action\AddTouite;
use iutnc\touiteur\action\Login;
use iutnc\touiteur\action\Profile;

class Dispatcher {
    private ?string $action = null;

    public function __construct() {
        $this->action = $_GET["action"] ?? "default";
    }

    public function run(): void {
        $act = match ($this->action) {
            'like' => new Like(),
            'login' => new Login(),
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