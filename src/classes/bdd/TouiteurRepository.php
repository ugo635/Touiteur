<?php

namespace iutnc\touiteur\bdd;

use PDO;
use Exception;
use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\users\User;

class TouiteurRepository {
    private PDO $pdo;
    private static ?TouiteurRepository $instance = null;
    private static array $config = [];

    private function __construct(array $conf)
    {
        $port = $conf['port'] ?? 1521;
        $dsn = "{$conf['driver']}:host={$conf['host']};port={$port};dbname={$conf['database']};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $conf['username'], $conf['password'], [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_STRINGIFY_FETCHES => false,]);
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

    /**
     * Enregistre un touite, persiste son auteur si nécessaire et lie ses hashtags (Fonc. 6 & 8)
     */
    public function saveTouite(Touite $t): Touite
    {
        // 1. Vérifier si l'utilisateur existe déjà en base, sinon le créer
        $stmtUser = $this->pdo->prepare("SELECT id FROM user WHERE id = :id");
        $stmtUser->execute(['id' => $t->user->username]);
        if (!$stmtUser->fetch()) {
            $stmtInsertUser = $this->pdo->prepare("INSERT INTO user (id, email, passwd, nom, prenom) VALUES (:id, :email, '', :nom, '')");
            $stmtInsertUser->execute(['id' => $t->user->username, 'email' => $t->user->username . '@touiteur.app', 'nom' => $t->user->username]);
        }

        // 2. Insérer le touite
        $queryTouite = "INSERT INTO touite (id, message, date_publi, user_id, score_like) 
                        VALUES (:id, :message, NOW(), :user_id, :score_like)";
        $stmtTouite = $this->pdo->prepare($queryTouite);
        $stmtTouite->execute(['id' => $t->id, 'message' => $t->message, 'user_id' => $t->user->username, 'score_like' => $t->compteurLike]);

        // 3. Extraire et lier les tags (Fonctionnalité 8)
        $tags = $t->getListeTag();
        foreach ($tags as $tagLibelle) {
            $tagLibelle = strtolower($tagLibelle);

            // Récupérer l'ID du tag ou le créer
            $stmtTag = $this->pdo->prepare("SELECT id FROM tag WHERE libelle = :libelle");
            $stmtTag->execute(['libelle' => $tagLibelle]);
            $tagId = $stmtTag->fetchColumn();

            if (!$tagId) {
                $stmtInsertTag = $this->pdo->prepare("INSERT INTO tag (libelle) VALUES (:libelle)");
                $stmtInsertTag->execute(['libelle' => $tagLibelle]);
                $tagId = (int)$this->pdo->lastInsertId();
            }

            // Associer le touite au tag
            $stmtLiaison = $this->pdo->prepare("INSERT IGNORE INTO touite2tag (touite_id, tag_id) VALUES (:touite_id, :tag_id)");
            $stmtLiaison->execute(['touite_id' => $t->id, 'tag_id' => $tagId]);
        }

        return $t;
    }

    /**
     * Récupère tous les touites associés à un tag donné (Fonctionnalité 5)
     * @return Touite[]
     */
    public function findTouitesByTag(string $tag): array
    {
        $query = "SELECT t.id, t.message, t.date_publi, t.score_like, t.user_id 
                  FROM touite t
                  INNER JOIN touite2tag t2t ON t.id = t2t.touite_id
                  INNER JOIN tag tg ON t2t.tag_id = tg.id
                  WHERE tg.libelle = :tag
                  ORDER BY t.date_publi DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['tag' => strtolower($tag)]);

        $touites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['user_id']);
            $touites[] = new Touite($row['message'], $user);
        }

        return $touites;
    }
}