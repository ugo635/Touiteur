<?php

namespace iutnc\touiteur\bdd;

use Exception;
use iutnc\touiteur\exception\exception\InvalidPassword;
use iutnc\touiteur\exception\exception\UnableToCreateUser;
use iutnc\touiteur\exception\exception\UnknownUser;
use iutnc\touiteur\touite\Touite;
use iutnc\touiteur\users\User;
use PDO;
use PDOException;

class TouiteurRepository {
    private PDO $pdo;
    private static ?TouiteurRepository $instance = null;
    private static array $config = [];

    private function __construct(array $conf) {
        $port = $conf['port'] ?? 1521;
        $dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$conf['host']})(PORT={$port}))(CONNECT_DATA=(SID={$conf['database']})))";        $this->pdo = new PDO(
            $dsn,
            $conf['username'],
            $conf['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => true
            ]
        );
    }

    public static function setConfig(string $file): void {
        $conf = parse_ini_file($file);

        if ($conf === false) {
            throw new Exception("Impossible de lire le fichier de configuration : $file");
        }

        self::$config = $conf;
    }

    public static function getInstance(): TouiteurRepository {
        if (is_null(self::$instance)) {
            if (empty(self::$config)) {
                throw new Exception("La configuration de la base doit être initialisée avec setConfig()");
            }

            self::$instance = new TouiteurRepository(self::$config);
        }

        return self::$instance;
    }

    public function saveTouite(Touite $t): Touite {
        $this->pdo->beginTransaction();

        try {
            $stmtUser = $this->pdo->prepare(
                "SELECT username FROM utilisateur WHERE username = :username"
            );

            $stmtUser->execute([
                'username' => $t->user->username
            ]);

            if (!$stmtUser->fetch()) {
                $stmtInsertUser = $this->pdo->prepare(
                    "INSERT INTO utilisateur (username, mdp) VALUES (:username, 'Anonymous')"
                );

                $stmtInsertUser->execute([
                    'username' => $t->user->username
                ]);
            }

            $stmtTouite = $this->pdo->prepare(
                "INSERT INTO touite (username, message, nb_like) VALUES (:username, :message, :nb_like)"
            );

            $stmtTouite->execute([
                'username' => $t->user->username,
                'message' => $t->message,
                'nb_like' => $t->compteurLike
            ]);

            $this->pdo->commit();

            return $t;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function findTouitesByUser(string $username): array {
        $query = "
            SELECT id_touite, message, date_touite, nb_like, username
            FROM touite
            WHERE username = :username
            ORDER BY date_touite DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['username' => $username]);

        $touites = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['USERNAME']);
            $touites[] = new Touite($row['MESSAGE'], $user);
        }

        return $touites;
    }

    public function findAllTouites(): array {
        $query = "
            SELECT id_touite, message, date_touite, nb_like, username
            FROM touite
            ORDER BY date_touite DESC
        ";

        $stmt = $this->pdo->query($query);

        $touites = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['USERNAME']);
            $touites[] = new Touite($row['MESSAGE'], $user);
        }

        return $touites;
    }

    public function checkUser(string $username, string $password): User {
        // Si user & mdp valide, on renvoie user
        $query = "SELECT username, date_creation FROM utilisateur WHERE username = :username AND mdp = :password";
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            'username' => $username,
            'password' => $password
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $date = $row['date_creation'];
            return new User($row['USERNAME'], $date);
        }

        // Si mdp est invalide, on throw InvalidPassword
        $query = "SELECT username FROM utilisateur WHERE username = :username";
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            "username" => $username
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            throw new InvalidPassword("MDP pas valide");
        }

        // Si l'utilisateur n'existe pas, on throw UnknownUser
        throw new UnknownUser("User inconnu");
    }

    public function createUser(string $user, string $password): User {
        $query = "INSERT INTO utilisateur (username, mdp) VALUES (:username, :password)";
        $stmt = $this->pdo->prepare($query);

        try {
            $stmt->execute([
                "username" => $user,
                "password" => $password
            ]);
        } catch (PDOException $e) {
            throw new UnableToCreateUser("Impossible de créer l'utilisateur : " . $e->getMessage());
        }

        return new User($user);
    }

    public function getUserProfile(string $username): ?array {
        $query = "SELECT username, nb_follower, nb_follows, TO_CHAR(date_creation, 'DD/MM/YY') AS date_creation 
              FROM utilisateur 
              WHERE username = :username";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}