<?php

namespace iutnc\touiteur\bdd;

use PDO;
use Exception;
use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\users\User;

class TouiteurRepository
{
    private PDO $pdo;
    private static ?TouiteurRepository $instance = null;
    private static array $config = [];

    private function __construct(array $conf)
    {
        $port = $conf['port'] ?? 3306;
        $dsn = "{$conf['driver']}:host={$conf['host']};port={$port};dbname={$conf['database']};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $conf['username'], $conf['password'], [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }

    public static function setConfig(string $file): void
    {
        $conf = parse_ini_file($file);
        if ($conf === false) {
            throw new Exception("Impossible de lire le fichier de configuration : $file");
        }
        self::$config = $conf;
    }

    public static function getInstance(): TouiteurRepository
    {
        if (is_null(self::$instance)) {
            if (empty(self::$config)) {
                throw new Exception("La configuration de la base doit être initialisée avec setConfig()");
            }
            self::$instance = new TouiteurRepository(self::$config);
        }
        return self::$instance;
    }

    public function findTouitesByTag(string $tag): array
    {
        // Jointure avec la table user pour récupérer les infos de l'auteur
        $query = "SELECT t.id, t.message, t.date_publi, t.score_like, t.user_id, u.nom, u.prenom, u.email
                  FROM touite t
                  LEFT JOIN user u ON t.user_id = u.id
                  WHERE t.message LIKE :pattern 
                  ORDER BY t.date_publi DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['pattern' => '%#' . $tag . '%']);

        $touites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Création de l'utilisateur associé
            // Adaptez les paramètres selon le constructeur de votre classe User
            $user = new User($row['nom'] ?? 'Anonyme',
                $row['prenom'] ?? '', $row['email'] ?? '');

            // Instanciation avec le message et l'objet User
            $t = new Touite($row['message'], $user);

            if (in_array(strtolower($tag), array_map('strtolower', $t->getListeTag()))) {
                $touites[] = $t;
            }
        }

        return $touites;
    }
}