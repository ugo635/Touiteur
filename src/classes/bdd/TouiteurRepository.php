<?php

namespace iutnc\touiteur\repository;

use PDO;
use Exception;
use DateTime;
use iutnc\touiteur\touite\Touite;

class TouiteRepository {
    private PDO $pdo;
    private static ?TouiteRepository $instance = null;
    private static array $config = [];

    private function __construct(array $conf) {
        $dsn = "{$conf['driver']}:host={$conf['host']};
                                  dbname={$conf['database']};
                                  charset=utf8mb4";
        $this->pdo = new PDO($dsn, $conf['username'], $conf['password'], [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    }

    public static function setConfig(string $file): void {
        $conf = parse_ini_file($file);
        if ($conf === false) {
            throw new Exception("Impossible de lire le fichier de configuration : $file");
        }
        self::$config = $conf;
    }

    public static function getInstance(): TouiteRepository {
        if (is_null(self::$instance)) {
            if (empty(self::$config)) {
                throw new Exception("La configuration de la base doit être initialisée avec setConfig()");
            }
            self::$instance = new TouiteRepository(self::$config);
        }
        return self::$instance;
    }

    /**
     * Sauvegarde un touite en base de données
     */
    public function save(Touite $t): Touite {
        $query = "INSERT INTO touite (id, message, date_publi, score_like) 
                  VALUES (:id, :message, :date_publi, :score_like)";

        $stmt = $this->pdo->prepare($query);

        // Conversion du tableau getdate() ou date courante au format SQL Y-m-d H:i:s
        $dateArray = $t->getDate();
        $formattedDate = date('Y-m-d H:i:s', $dateArray[0] ?? time());

        $stmt->execute([
            'id' => $t->id,
            'message' => $t->message,
            'date_publi' => $formattedDate,
            'score_like' => $t->CompteurLike
        ]);

        return $t;
    }

    public function findTouitesByTag(string $tag): array {
        // Recherche sécurisée avec requête préparée et LIKE
        $query = "SELECT id, message, date_publi, score_like 
              FROM touite 
              WHERE message LIKE :pattern 
              ORDER BY date_publi DESC";

        $stmt = $this->pdo->prepare($query);
        // On cherche le tag précédé d'un dièse
        $stmt->execute(['pattern' => '%#' . $tag . '%']);

        $touites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Recréation de l'entité Touite à partir de la ligne SQL
            $t = new Touite($row['message']);

            // On s'assure que le tag correspond bien exactement via la méthode de l'entité
            if (in_array(strtolower($tag), array_map('strtolower', $t->getListeTag()))) {
                $touites[] = $t;
            }
        }

        return $touites;
    }
}