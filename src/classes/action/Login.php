<?php

namespace iutnc\touiteur\action;

class Login extends Action {

    public function get(): string {
        return <<<HTML
            <form method="POST" id="login" class="flex flex-col items-center gap-4" action="?action=login">
                <fieldset class="flex flex-col items-center border border-gray-400 p-4 rounded">
                    <legend class="px-2 font-medium">Username</legend>
                    <input type="text" name="user" class="border border-gray-300 rounded px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500">
                </fieldset>
                <fieldset class="flex flex-col items-center border border-gray-400 p-4 rounded">
                    <legend class="px-2 font-medium">Password</legend>
                    <input type="password" name="password" class="border border-gray-300 rounded px-2 py-1 outline-none focus:ring-2 focus:ring-blue-500">
                </fieldset>
                
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 border border-blue-600 
                hover:border-blue-700 rounded-4xl p-3 outline-none cursor-pointer">Se connecter</button>
            </form>
        HTML;

    }

    public function post(): string {
        $user = $_POST["user"] ?? "";
        $password = $_POST["password"] ?? "";

        if ($user === "" || $password === "") {
            return $this->get() . "<br><div class='flex justify-center'><p class='text-red-500 font-medium'>Veuillez remplir tous les champs.</p></div>";
        }


        // TODO: Check si connecté avec la BD
        header("Location: ?action=default");
        exit;
    }
}