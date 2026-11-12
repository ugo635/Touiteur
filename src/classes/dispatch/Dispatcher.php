<?php

namespace iutnc\touiteur\dispatch;

use iutnc\touiteur\action\AddTouite;
use iutnc\touiteur\action\api\Follow;
use iutnc\touiteur\action\api\Like;
use iutnc\touiteur\action\DefaultAction;
use iutnc\touiteur\action\FollowList;
use iutnc\touiteur\action\Hashtag;
use iutnc\touiteur\action\Login;
use iutnc\touiteur\action\Logout;
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
            'logout' => new Logout(),
            'voir-tag' => new Hashtag(),
            'followers' => new FollowList('followers'),
            'follows' => new FollowList('follows'),
            'follow' => new Follow(),
            default => new DefaultAction(),
        };

        $resAct = $act(); // Appel __invoke() de Action
        $this->renderPage($act->prefix());
        $this->renderPage($resAct); // Affiche plus tard pr que les changement de session soit déjà actif sur la session
        $this->renderPage($act->suffixe());

    }

    private function renderPage(string $html) {
        echo $html;
    }
}