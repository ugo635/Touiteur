<?php

namespace iutnc\touiteur\action;

abstract class Action {
    protected ?string $http_method = null;
    protected ?string $hostname = null;
    protected ?string $script_name = null;

    public function __construct() {
        $this->http_method = $_SERVER['REQUEST_METHOD'];
        $this->hostname = $_SERVER['HTTP_HOST'];
        $this->script_name = $_SERVER['SCRIPT_NAME'];
    }

    public function prefix(): string {
        $btn = "hover:underline";
        $username = $_SESSION["username"] ?? "";

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
            <head>
                <title>Touiteur</title>
                <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
            </head>
            <body>
            <nav class="flex justify-center gap-2 p-4 bg-white shadow">
                <a href="?action=default" class="$btn">Home</a>
                <a href="?action=profile&username=$username" class="$btn">Profil</a>
                <a href="?action=add-touite" class="$btn">Poster</a>
                <a href="?action=tendances" class="$btn">Tendances</a>
        HTML;

        if (!isset($_SESSION["username"])) {
            $html .= "<a href='?action=login' class='$btn'>Login</a>";
        } else {
            $html .= "<a href='?action=logout' class='$btn'>Déconnexion</a>";
        }
        $html .= "</nav>";
        return $html;
    }

    public function suffixe(): string {
        return "</body></html>";
    }

    public function __invoke(): string {
        return $this->execute();
    }

    public function execute() : string {
        if ($this->http_method == "GET") return $this->get();
        if ($this->http_method == "POST") return $this->post();
        throw new \InvalidArgumentException("Pas valide");
    }
    abstract public function get() : string;
    abstract public function post() : string;

}