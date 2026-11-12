<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\exception\exception\InvalidPassword;
use iutnc\touiteur\exception\exception\UnableToCreateUser;
use iutnc\touiteur\exception\exception\UnknownUser;
use iutnc\touiteur\users\User;

class Login extends Action {

    public function get(): string {
        $mode = ($_POST["submit"] ?? "login") === "register" ? "register" : "login";
        $username = $this->filtre("username");
        $email = $this->filtre("email");
        $nom = $this->filtre("nom");
        $prenom = $this->filtre("prenom");
        $input = "w-full border border-gray-300 rounded px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500";
        $label = "flex flex-col gap-1 w-full font-medium";

        return <<<HTML
            <form method="POST" id="login-form" data-mode="{$mode}" action="?action=login"
                  class="max-w-md mx-auto p-4 mt-4 bg-white shadow-sm rounded-lg border border-gray-200 flex flex-col items-center gap-4">

                <div class="flex border-b border-gray-200 w-full">
                    <button type="button" data-mode="login" onclick="setMode('login')"
                            class="tab flex-1 text-center py-3 border-b-2 transition duration-200 cursor-pointer">Connexion</button>
                    <button type="button" data-mode="register" onclick="setMode('register')"
                            class="tab flex-1 text-center py-3 border-b-2 transition duration-200 cursor-pointer">Inscription</button>
                </div>

                <input type="hidden" name="submit" id="login-mode" value="{$mode}">

                <label class="{$label}">Nom d'utilisateur
                    <input type="text" name="username" value="{$username}" required autofocus class="{$input}">
                </label>

                <label class="{$label}">Mot de passe
                    <input type="password" name="password" required class="{$input}">
                </label>

                <div id="register-part" class="flex flex-col items-center gap-4 w-full">
                    <label class="{$label}">Email
                        <input type="email" name="email" value="{$email}" required class="{$input}">
                    </label>
                    <div class="flex gap-4 w-full">
                        <label class="{$label} flex-1 min-w-0">Prenom
                            <input type="text" name="prenom" value="{$prenom}" required class="{$input}">
                        </label>
                        <label class="{$label} flex-1 min-w-0">Nom
                            <input type="text" name="nom" value="{$nom}" required class="{$input}">
                        </label>
                    </div>
                </div>

                <button type="submit" id="submit-btn"
                        class="bg-blue-500 hover:bg-blue-600 border border-blue-600 hover:border-blue-700 rounded-4xl p-3 outline-none cursor-pointer"></button>
            </form>
            <script>
                function setMode(mode) {
                    const form = document.querySelector("#login-form");
                    const inscription = mode === "register";

                    form.querySelector("#login-mode").value = mode;
                    form.querySelector("#register-part").classList.toggle("hidden", !inscription);
                    // Les champs désactivés ne sont ni validés ni envoyés
                    form.querySelectorAll("#register-part input").forEach(i => i.disabled = !inscription);
                    form.querySelector("#submit-btn").textContent = inscription ? "S'inscrire" : "Se connecter";

                    form.querySelectorAll(".tab").forEach(tab => {
                        const actif = tab.dataset.mode === mode;
                        tab.classList.toggle("border-blue-600", actif);
                        tab.classList.toggle("text-blue-600", actif);
                        tab.classList.toggle("font-bold", actif);
                        tab.classList.toggle("border-transparent", !actif);
                        tab.classList.toggle("text-gray-500", !actif);
                    });
                }

                setMode(document.querySelector("#login-form").dataset.mode);
            </script>
        HTML;
    }

    /**
     * Récup une valeur de $_POST avec tout retirer pr éviter les injection XSS
     */
    public static function filtre(string $key): string {
        return htmlspecialchars($_POST[$key] ?? "", ENT_QUOTES, 'UTF-8');
    }

    public function post(): string {
        $username = strtolower(trim($_POST["username"] ?? ""));
        $nom = trim($_POST["nom"] ?? "");
        $prenom = trim($_POST["prenom"] ?? "");
        $password = $_POST["password"] ?? "";
        $email = trim($_POST["email"] ?? "");
        $type = $_POST["submit"] ?? "";

        if ($type === "login") {
            $incomplet = $username === "" || $password === "";
        } else {
            $incomplet = $username === "" || $nom === "" || $prenom === "" || $password === "" || $email === "";
        }

        if ($incomplet) {
            return $this->get() . "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Veuillez remplir tous les champs.</p></div>";
        }

        if ($type == "login") {
            try {
                $user = TouiteurRepository::getInstance()->checkUser($username, $password);
                $this->openSession($user);
                return "<br><div class='flex justify-center'><p class='text-green-500 font-medium'>Vous etes connecter!</p></div>";
            } catch (InvalidPassword $e) {
                return $this->get() .
                    "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Mot de passe invalide, reassayer</p></div>";
            } catch (UnknownUser $e) {
                return $this->get() . "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Utilisateur inconnu</p></div>";
            }
        } else if ($type == "register") {
            try {
                $user = TouiteurRepository::getInstance()->createUser($username, $email, $nom, $prenom, $password);
                $this->openSession($user);
                return "<br><div class='flex justify-center'><p class='text-green-500 font-medium'>Utilisateur créer avec succès</p></div>";
            } catch (UnableToCreateUser $e) {
                return $this->get() . "<br><div class='flex justify-center'>
                    <p class='text-red-500 font-medium'>L'utilisateur existe déjà, essayer de vous connecter</p></div>";
            }
        }

        return "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Erreur Serveur</p></div>";
    }

    /**
     * Ouvre une session pour l'utilisateur qui se connecte
     * @param User $user
     * @return void
     */
    private function openSession(User $user): void {
        session_regenerate_id(true);
        $_SESSION["user"] = serialize($user);
        $_SESSION["username"] = $user->username;
    }
}