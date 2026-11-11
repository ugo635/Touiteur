<?php

namespace iutnc\touiteur\action\api;

use iutnc\touiteur\bdd\TouiteurRepository;

class Like extends ApiAction {
    public function post(): string {
        $data = $this->getData();

        $post = $data["post"] ?? null;
        $change = $data["change"] ?? null;
        $user = $data["user"] ?? null;

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