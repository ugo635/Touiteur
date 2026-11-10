<?php

namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\exception\exception\InvalidPassword;
use iutnc\touiteur\exception\exception\UnableToCreateUser;
use iutnc\touiteur\exception\exception\UnknownUser;

class Login extends Action {

    public function get(): string {
        return <<<HTML
            <form method="POST" id="login" class="flex flex-col items-center gap-4" action="?action=login">
                <fieldset class="flex flex-col items-center border border-gray-400 p-4 rounded">
                    <legend class="px-2 font-medium">Email</legend>
                    <input type="email" name="email" class="border border-gray-300 rounded px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500">
                </fieldset>
                <fieldset class="flex flex-col items-center border border-gray-400 p-4 rounded">
                    <legend class="px-2 font-medium">Nom</legend>
                    <input type="text" name="nom" class="border border-gray-300 rounded px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500">
                </fieldset>
                <fieldset class="flex flex-col items-center border border-gray-400 p-4 rounded">
                    <legend class="px-2 font-medium">Prenom</legend>
                    <input type="text" name="prenom" class="border border-gray-300 rounded px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500">
                </fieldset>
                <fieldset class="flex flex-col items-center border border-gray-400 p-4 rounded">
                    <legend class="px-2 font-medium">Password</legend>
                    <input type="password" name="password" class="border border-gray-300 rounded px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500">
                </fieldset>
                
                <div>
                    <button type="submit" name="submit" value="login" class="bg-blue-500 hover:bg-blue-600 border border-blue-600 
                    hover:border-blue-700 rounded-4xl p-3 outline-none cursor-pointer">Se connecter</button>
                    <button type="submit" name="submit" value="register" class="bg-blue-500 hover:bg-blue-600 border border-blue-600 
                    hover:border-blue-700 rounded-4xl p-3 outline-none cursor-pointer">S'inscrire</button>
                </div>
            </form>
        HTML;

    }

    public function post(): string {
        $nom = $_POST["nom"] ?? "";
        $prenom = $_POST["prenom"] ?? "";
        $password = $_POST["password"] ?? "";
        $email = $_POST["email"] ?? "";
        $type = $_POST["submit"] ?? "";

        if ($nom === "" || $prenom === "" || $password === "" || $email === "") {
            return $this->get() . "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Veuillez remplir tous les champs.</p></div>";
        }

        if ($type == "login") {
            try {
                $user = TouiteurRepository::getInstance()->checkUser($email, $nom, $prenom, $password);
                $_SESSION["user"] = serialize($user);
                return "<br><div class='flex justify-center'><p class='text-green-500 font-medium'>Vous etes connecter!</p></div>";
            } catch (InvalidPassword $e) {
                return $this->get() .
                    "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Mot de passe invalide, reassayer</p></div>";
            } catch (UnknownUser $e) {
                return $this->get() . "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Utilisateur inconnu</p></div>";
            }
        } else if ($type == "register") {
            try {
                $user = TouiteurRepository::getInstance()->createUser($email, $nom, $prenom, $password);
                $_SESSION["user"] = serialize($user);
                return "<br><div class='flex justify-center'><p class='text-green-500 font-medium'>Utilisateur créer avec succès</p></div>";
            } catch (UnableToCreateUser $e) {
                return $this->get() . "<br><div class='flex justify-center'>
                    <p class='text-red-500 font-medium'>L'utilisateur existe déjà, essayer de vous connecter</p></div>";
            }
        }

        return "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Erreur Serveur</p></div>";
    }
}