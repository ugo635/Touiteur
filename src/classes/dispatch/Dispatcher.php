<?php

namespace iutnc\touiteur\dispatch;

use iutnc\touiteur\action\DefaultAction;
use iutnc\touiteur\action\AddTouite;// a faire classe
use iutnc\touiteur\action\ProfileTouite;//a faire classe

class Dispatcher {
    private ?string $action = null;

    public function __construct() {
        $this->action = $_GET["action"] ?? "default";
    }

    public function run(): void {
        $act = match ($this->action) {
            default => new DefaultAction(),
            'add-touite'=> new AddTouite(),
            'profile' => new ProfileTouite(),

        };

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
            <head>
                <title>Touiteur</title>
                <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
            </head>
            <body>
        HTML;
        $this->renderPage($html);
        $this->renderPage($this->renderNav());
        $this->renderPage($act()); // Appel __invoke() de Action
        $this->renderPage("</body></html>");

    }

    private function renderPage(string $html) {
        echo $html;
    }

    private function renderNav() :string{
        $btn = "";
        return <<<HTML
        <nav class="flex justify-center gap-2 p-4 bg-white shadow">
            <a href="?action=default" class="$btn">Home</a>
            <a href="?action=profile" class="$btn">Afficher</a>
            <a href="?action=add-touite" class="$btn">Poster</a>
        </nav>
           
        HTML;

    }
}