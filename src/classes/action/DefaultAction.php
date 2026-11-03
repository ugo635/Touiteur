<?php

namespace iutnc\touiteur\action;

class DefaultAction extends Action {
    public function get(): string {
        return <<<HTML
            <form id="postTouite" method="POST" action="?add-touite">
                <div class="flex flex-col gap-2 w-full max-w-lg mx-auto items-center">
                    <textarea id="touite-content" name="touite" placeholder="Quoi de neuf?" class="w-full border border-gray-300 rounded-md p-3 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    <button type="submit" class="cursor-pointer self-center bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Envoyer</button>
                </div>
            </form>
        HTML;
    }


    public function post(): string {
        return "";
    }
}