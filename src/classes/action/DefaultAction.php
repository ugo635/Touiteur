<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\render\RendererFactory;


class DefaultAction extends Action {
    public function get(): string {

                $touites = [];
                $connecte = isset($_SESSION['user']);

                try {
                    $repo = TouiteurRepository::getInstance();

                    if ($connecte) {
                        // Connecté : touites des personnes / tags suivis
                        $username = unserialize($_SESSION['user'])->username;
                        $touites = $repo->findFeed($username);
                    } else {
                        // Pas connecté : tous les touites
                        $touites = $repo->findAllTouites();
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur, on affiche le message "aucun touite"
                }

                $touitesHtml = '';
                if (empty($touites)) {
                    $message = $connecte ? "Aucun touite dans votre fil. Abonnez-vous à des utilisateurs ou à des tags !" : "Aucun touite pour le moment.";
                    $touitesHtml = "<p class=\"text-gray-500 text-center py-8\">$message</p>";
                } else {
                    foreach ($touites as $touite) {
                        $touitesHtml .= RendererFactory::getRenderer($touite)->render();
                    }
                }

                return <<<HTML
             <!--listes touites-->
             <div class="flex items-center flex-col gap-2 py-8">
                $touitesHtml
             </div>
        HTML;
            }

            public function post(): string
            {
                return $this->get();
            }
        }