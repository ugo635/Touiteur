<?php

namespace iutnc\touiteur\dispatch;

use iutnc\touiteur\action\DefaultAction;

class Dispatcher {
    private ?string $action = null;

    public function __construct() {
        $this->action = $_GET["action"] ?? "default";
    }

    public function run(): void {
        $act = match ($this->action) {
            default => new DefaultAction()
        };

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
            <head>
                <title>Touiteur</title>
                <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
                <script src="../js/touite.js"></script>
            </head>
            <body>
        HTML;
        $this->renderPage($html);
        $this->renderPage($act()); // Appel __invoke() de Action
        $this->renderPage("</body></html>");
    }

    private function renderPage(string $html) {
        echo $html;
    }
}