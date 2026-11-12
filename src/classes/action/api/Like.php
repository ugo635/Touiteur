<?php

namespace iutnc\touiteur\action\api;

use iutnc\touiteur\bdd\TouiteurRepository;

class Like extends ApiAction {
    public function post(): string {
        $user = $_SESSION["username"] ?? null;
        if ($user === null) {
            http_response_code(401);
            return "Non connecte";
        }


        $data = $this->getData();

        $post = $data["post"] ?? null;
        $change = $data["change"] ?? null;

        if (!$post || !in_array($change, [1, -1])) {
            http_response_code(400);
            return "Requete invalide";
        }

        $repo = TouiteurRepository::getInstance();
        return $repo->like($post, $user, $change);
    }

    public function get(): string {
        return "";
    }

}