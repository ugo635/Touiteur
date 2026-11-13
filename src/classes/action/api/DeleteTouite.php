<?php

namespace iutnc\touiteur\action\api;

use iutnc\touiteur\bdd\TouiteurRepository;

class DeleteTouite extends ApiAction {
    public function post(): string {
        $user = $_SESSION["username"] ?? null;
        if ($user === null) {
            http_response_code(401);
            return "Non connecté";
        }
        $data = $this->getData();
        $idTouite = $data["id_touite"] ?? null;

        if (!$idTouite) {
            http_response_code(400);
            return "Requête invalide";
        }

        $repo = TouiteurRepository::getInstance();
        $success = $repo->deleteTouite($idTouite, $user);

        if (!$success) {
            http_response_code(403);
            return "Non autorisé ou touite introuvable";
        }

        return "Supprimer";
    }

    public function get(): string {
        return "";
    }
}