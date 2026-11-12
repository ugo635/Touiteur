<?php

namespace iutnc\touiteur\action\api;

use iutnc\touiteur\bdd\TouiteurRepository;

class Follow extends ApiAction {

    public function get(): string {
        return "";
    }

    public function post(): string {
        $suiveur = $_SESSION["username"] ?? null;
        if ($suiveur === null) {
            http_response_code(401);
            return "Non connecte";
        }

        $data = $this->getData();
        $suivi = strtolower(str_replace(" ", "", $data["suivi"] ?? null));
        $ajout = $data["ajout"] ?? null;

        $repo = TouiteurRepository::getInstance();

        $res = false;
        if ($ajout) {
            $res = $repo->follow($suiveur, $suivi);
        } else {
            $res = $repo->unfollow($suiveur, $suivi);
        }

        if (!$res) {
            http_response_code(400);
            return "Requete Invalide";
        }

        return ""; // Pas besoin de réponse particulière
    }
}