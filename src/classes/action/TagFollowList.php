<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;

class TagFollowList extends Action {

    public function get(): string {
        $username = $_GET['user'] ?? "";
        $repo = TouiteurRepository::getInstance();

        if ($username === "" || !$repo->userExists($username)) {
            return "<div class='p-8 text-center text-red-500 font-medium'>Utilisateur introuvable.</div>";
        }

        $current = $_SESSION["username"] ?? null;
        $safeUser = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $userUrl = urlencode($username);

        $items = "";
        foreach ($repo->findTagFollows($username) as $tag) {
            $safe = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
            $url = urlencode($tag);

            // Bouton Suivre / Ne plus suivre (si connecté), selon ce que MOI je suis
            $btn = "";
            if ($current !== null) {
                $isFollowing = $repo->isFollowingTag($current, $tag);
                $value = $isFollowing ? "unfollow" : "follow";
                $label = $isFollowing ? "Ne plus suivre" : "Suivre";
                $style = $isFollowing
                    ? "bg-white text-black border border-gray-300 hover:text-red-600"
                    : "bg-black text-white hover:bg-gray-800";

                $btn = <<<HTML
                <form method="POST" action="?action=tag-follows&user=$userUrl">
                    <input type="hidden" name="target" value="$safe">
                    <button type="submit" name="follow" value="$value"
                            class="px-3 py-1 text-sm rounded-full font-bold cursor-pointer $style">$label</button>
                </form>
                HTML;
            }

            $items .= <<<HTML
            <li class="flex justify-between items-center py-2 border-b border-gray-100">
                <a href="?action=voir-tag&tag=$url" class="font-bold text-blue-600 hover:underline">#$safe</a>
                $btn
            </li>
            HTML;
        }

        if ($items === "") {
            $items = "<li class='py-2 text-gray-500'>Aucun tag suivi.</li>";
        }

        return <<<HTML
        <div class="max-w-2xl mx-auto p-4 mt-4 bg-white shadow-sm rounded-lg border border-gray-200">
            <a href="?action=profile&username=$userUrl" class="text-gray-500 hover:underline">← @$safeUser</a>
            <h1 class="text-xl font-bold my-4">Tags suivis</h1>
            <ul>$items</ul>
        </div>
        HTML;
    }

    //clic sur suivre / ne plus suivre
    public function post(): string {
        $current = $_SESSION["username"] ?? null;
        $target = $_POST['target'] ?? "";

        if ($current !== null && $target !== "") {
            $repo = TouiteurRepository::getInstance();
            match ($_POST['follow'] ?? "") {
                'follow' => $repo->followTag($current, $target),
                'unfollow' => $repo->unfollowTag($current, $target),
                default => null,
            };
        }

        // On réaffiche la liste à jour
        return $this->get();
    }
}