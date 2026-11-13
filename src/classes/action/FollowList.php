<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;

class FollowList extends Action {
    private string $type;
    // "followers" ou "follows"

    public function __construct(string $type) {
        parent::__construct();
        $this->type = $type;
    }

    // Renvoie le username de l'utilisateur connecté, ou null
    private function currentUsername(): ?string {
        return $_SESSION["username"] ?? null;
    }

    public function get(): string{
        $repo = TouiteurRepository::getInstance();

        if ($this->type === "tag-followers") {
            $tag = $_GET['tag'] ?? "";
            if ($tag === "" || !$repo->TagExists($tag)) {
                return "<div class='p-8 text-center text-red-500 font-medium'>Tag introuvable.</div>";
            }
            $users = $repo->findTagFollowers($tag);
            $safeTagOuUser = "#" . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
            $title = "Abonnés de $safeTagOuUser";
            $urlUserOuTag = "?action=voir-tag&tag=". urlencode($tag);
            $action = "?action=tag-followers&tag=". urlencode($tag);
        } else {
            $username = $_GET['user'] ?? "";
            if ($username === "" || !$repo->userExists($username)) {
                return "<div class='p-8 text-center text-red-500 font-medium'>Utilisateur introuvable.</div>";
            }

            // Choix de la liste selon le type
            $users = $this->type === "followers"
                ? $repo->findFollowers($username)   // ceux qui suivent $username
                : $repo->findFollows($username);    // ceux que $username suit

            $title = $this->type === "followers" ? "Abonnés" : "Abonnements";
            $safeTagOuUser = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $urlUserOuTag = "?action=profile&username=".urlencode($username);
            $action = "?action={$this->type}&user=" . urlencode($username);
        }
        $current = $this->currentUsername();

        $items = "";
        foreach ($users as $u) {
            $safe = htmlspecialchars($u, ENT_QUOTES, 'UTF-8');
            $url = urlencode($u);

            // Bouton Suivre / Ne plus suivre (pas pour soi-même)
            $btn = "";
            if ($current !== null && $current !== $u) {
                $isFollowing = $repo->isFollowing($current, $u);
                $value = $isFollowing ? "unfollow" : "follow";
                $label = $isFollowing ? "Ne plus suivre" : "Suivre";
                $style = $isFollowing
                    ? "bg-white text-black border border-gray-300 hover:text-red-600"
                    : "bg-black text-white hover:bg-gray-800";

                $btn = <<<HTML
                    <form method="POST" action="?action=$this->type&user=$username">
                        <input type="hidden" name="target" value="$safe">
                        <button type="submit" name="follow" value="$value"
                                class="px-3 py-1 text-sm rounded-full font-bold cursor-pointer $style">$label</button>
                    </form>
                HTML;
            }

            $items .= <<<HTML
                <li class="flex justify-between items-center py-2 border-b border-gray-100">
                    <a href="?action=profile&username=$url" class="font-bold hover:underline">@$safe</a>
                    $btn
                </li>
            HTML;
        }

        if ($items === "") {
            $items = "<li class='py-2 text-gray-500'>Aucun utilisateur.</li>";
        }

        return <<<HTML
        <div class="max-w-2xl mx-auto p-4 mt-4 bg-white shadow-sm rounded-lg border border-gray-200">
            <a href="$urlUserOuTag" class="text-gray-500 hover:underline">← $safeTagOuUser</a>
            <h1 class="text-xl font-bold my-4">$title</h1>
            <ul>$items</ul>
        </div>
        HTML;
    }

    // Clic sur un bouton Suivre / Ne plus suivre dans la liste
    public function post(): string {
        $current = $this->currentUsername();
        $target = $_POST['target'] ?? "";
        $user = $_GET["user"] ?? "";

        if ($current !== null && $target !== "") {
            $repo = TouiteurRepository::getInstance();
            match ($_POST['follow'] ?? "") {
                'follow' => $repo->follow($current, $target),
                'unfollow' => $repo->unfollow($current, $target),
                default => null,
            };
        }

        // On réaffiche la liste à jour
        header("Location: ?action=$this->type&user=$user");
        return $this->get();
    }
}