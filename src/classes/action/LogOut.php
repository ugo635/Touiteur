<?php

namespace iutnc\touiteur\action;

class Logout extends Action {

    public function get(): string {
        return "<form method='POST' action='?action=logout' class='flex justify-center mt-10'>
                    <button type='submit' class='cursor-pointer'>
                        Se déconnecterrrr
                    </button>
                </form>";
    }

    public function post(): string {
        unset($_SESSION['username']);
        session_regenerate_id(true);

        return "<br><div class='flex justify-center'>
                    <p class='text-green-500 font-medium'>Vous êtes déconnecté.</p>
                </div>";
    }
}