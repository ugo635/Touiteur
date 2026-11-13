<?php

namespace iutnc\touiteur\action;

// gere la deconnexion
class Logout extends Action {

    //affiche le formulaire de deconnexion
    public function get(): string {
        return "<form method='POST' action='?action=logout' class='flex justify-center mt-10'>
                    <button type='submit' class='cursor-pointer'>
                        Se déconnecter
                    </button>
                </form>";
    }

    //deconnexion
    public function post(): string {
        // supprime les donnees de session
        unset($_SESSION['username'],$_SESSION['user']);
        session_regenerate_id(true);

        return "<br><div class='flex justify-center'>
                    <p class='text-green-500 font-medium'>Vous êtes déconnecté.</p>
                </div>";
    }
}