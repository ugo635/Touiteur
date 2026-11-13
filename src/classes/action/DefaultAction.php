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
                        $touites = $repo->findPourtoi($username);

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
                    <div class="flex justify-center mt-2">
                        <input id="tagSearch" placeholder="Rechercher un tag" class="w-40 h-5 border border-gray-500 rounded-sm p-3" >
                    </div>
                     <!--listes touites-->
                     <div class="flex items-center flex-col gap-2 py-4">
                        $touitesHtml
                     </div>
                    <script>
                        document.querySelector("#tagSearch").addEventListener("keypress", (event) => {
                            if (event.key === "Enter") {
                                const val = document.querySelector("#tagSearch").value.replaceAll("#", "").toLowerCase()
                                window.location.href = ("?action=voir-tag&tag=" + val)
                            }
                        });
                    </script>
                HTML;
            }

            public function post(): string
            {
                return $this->get();
            }
        }