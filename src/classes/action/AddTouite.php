<?php
namespace iutnc\touiteur\action;

use iutnc\touiteur\bdd\TouiteurRepository;
use iutnc\touiteur\exception\exception\MessageTooBig;
use iutnc\touiteur\render\RendererFactory;
use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\users\User;
//faire la sauvegarde bdd quand elle sera cree

class AddTouite extends Action {


    private function error(string $msg): string {
        return "<p class='p-2 text-red-600'>" . htmlspecialchars($msg) . "</p>";
    }

    public function get(): string {
        return "<h1 class='text-2xl text-center'>Poster un trouite</h1>" . $this->form();
    }

    public function post(): string {
        if (!isset($_SESSION["user"])) {
            return "Tu dois être connecté pour poster.";
        }
        //permet de retirer les espaces au debut et a la fin
        $message = trim($_POST['message'] ?? '');
        //anti post vide
        if ($message === '') {
            return $this->error("Le message ne peut pas être vide.") . $this->form();
        }
        $repo = TouiteurRepository::getInstance();
        $user = $repo->findUserByUsername(unserialize($_SESSION["user"])->formattedUsername);
        if ($user === null) {
            return $this->error("Utilisateur introuvable.") . $this->form($message);
        }
        try {
            $touite = new Touite($message,$user );
            $user = $repo->findUserByUsername(unserialize($_SESSION["user"])->formattedUsername);
            $touite = new Touite($message,$user);
        } catch (MessageTooBig $e) {
            return $this->error("235 caractères maximum") . $this->form($message);
        }

        //cree un nouveau touite
        $repo->saveTouite($touite);

        //ajoute les tags a la bdd
       // TouiteurRepository::addTags($touite);

        return "<p class='p-2 text-green-600'>Touite publié !</p>"
            . RendererFactory::getRenderer($touite)->render();
    }


    private function form(string $valeur = ''): string {
        $valeur = htmlspecialchars($valeur);
        return <<<HTML
    <form method="post" action="?action=add-touite"
      class="max-w-xl mx-auto mt-10 bg-white rounded-2xl shadow p-5 flex flex-col gap-5">

        <div class="flex gap-3">
            
            <textarea id="message" name="message" maxlength="235" rows="3"
                      placeholder="Quoi de neuf ? Ajoute des #tags"
                      class="w-full resize-none text-lg outline-none placeholder-gray-400">$valeur</textarea>
        </div>

        <div class="flex items-center justify-between border-t pt-3">
            <span id="compteur" class="text-sm text-gray-500">0 / 235</span>
        <button type="submit"
        class="px-5 py-2 bg-gray-800 text-white font-medium rounded-full cursor-pointer">
             Publier
        </button>
        </div>
    </form>

    <script>
        const zone = document.getElementById('message');
        const compteur = document.getElementById('compteur');
        const ajout = () => {
            const c = zone.value.length;
            compteur.textContent = c + ' / 235';
            compteur.className = c > 200 ? 'text-sm text-red-600 font-medium' : 'text-sm text-gray-500';
        };
        zone.addEventListener('input', ajout);
        ajout();
    </script>
    HTML;
    }
}