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
        $dsn = "oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$conf['host']})(PORT={$port}))(CONNECT_DATA=(SID={$conf['database']})))";

        $this->pdo = new PDO(
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

    public function saveTouite(Touite $touite): Touite {
        $this->pdo->beginTransaction();

        try {
            $stmtUser = $this->pdo->prepare(
                "SELECT username FROM utilisateur WHERE username = :username"
            );

            $stmtUser->execute([
                'username' => $touite->user->username
            ]);

            if (!$stmtUser->fetch()) {
                $stmtInsertUser = $this->pdo->prepare(
                    "INSERT INTO utilisateur (username, mdp) VALUES (:username, 'Anonymous')"
                );

                $stmtInsertUser->execute([
                    'username' => $touite->user->username
                ]);
            }

            $stmtTouite = $this->pdo->prepare(
                "INSERT INTO touite (id_touite, username, message, nb_like) VALUES (:id_touite, :username, :message, :nb_like)"
            );

            $stmtTouite->execute([
                'id_touite' => $touite->id,
                'username' => $touite->user->username,
                'message' => $touite->message,
                'nb_like' => $touite->compteurLike
            ]);

            $this->pdo->commit();

            return $touite;
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
            $date = $row['DATE_CREATION'];
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

    public function like(string $touite, string $user, int $change): string {
        $query = "SELECT username, id_touite, vote FROM likes WHERE username = :username AND id_touite = :id_touite";
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            "id_touite" => $touite,
            "username" => $user
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // S'il y a deja qqc
        if ($row) {
            if ($row["VOTE"] == $change) { // Si c'est le meme, on retire le like/delike
                $query = "DELETE FROM likes WHERE username = :username AND id_touite = :id_touite";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([
                    "username" => $user,
                    "id_touite" => $touite
                ]);
                return "Removed";
            } else { // On remplace
                $query = "UPDATE likes SET vote = :vote WHERE username = :username AND id_touite = :id_touite";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([
                    "username" => $user,
                    "id_touite" => $touite,
                    "vote" => $change
                ]);
                return "Replaced";
            }
        } else { // Si ya pas de like, on ajoute le like
            $query = "INSERT INTO likes (username, id_touite, vote) VALUES (:username, :id_touite, :vote)";
            $stmt = $this->pdo->prepare($query);

            $stmt->execute([
                "id_touite" => $touite,
                "username" => $user,
                "vote" => $change,
            ]);
            return "Added";
        }
    }


    public function userExists(string $username): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM utilisateur WHERE username = :username");
        $stmt->execute(['username' => $username]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'] > 0;
    }

    public function getCreationDate(string $username): ?string {
        $stmt = $this->pdo->prepare(
            "SELECT TO_CHAR(date_creation, 'DD/MM/YY') AS DATE_CREATION FROM utilisateur WHERE username = :username"
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['DATE_CREATION'] : null;
    }

    public function isFollowing(string $abonne, string $suivi): bool {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS NB FROM abonnement_utilisateur WHERE abonne = :abonne AND suivi = :suivi"
        );
        $stmt->execute(['abonne' => $abonne, 'suivi' => $suivi]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'] > 0;
    }

    public function follow(string $abonne, string $suivi): bool {
        if ($abonne === $suivi || !$this->userExists($suivi) || $this->isFollowing($abonne, $suivi)) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO abonnement_utilisateur (abonne, suivi) VALUES (:abonne, :suivi)"
            );
            $stmt->execute(['abonne' => $abonne, 'suivi' => $suivi]);
            return true;
        } catch (PDOException $e) {
            // Refus par un trigger (ex : même email)
            return false;
        }
    }

    public function unfollow(string $abonne, string $suivi): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM abonnement_utilisateur WHERE abonne = :abonne AND suivi = :suivi"
        );
        $stmt->execute(['abonne' => $abonne, 'suivi' => $suivi]);
        return $stmt->rowCount() > 0;
    }

    public function countFollowers(string $username): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM abonnement_utilisateur WHERE suivi = :username");
        $stmt->execute(['username' => $username]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'];
    }

    public function countFollows(string $username): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM abonnement_utilisateur WHERE abonne = :username");
        $stmt->execute(['username' => $username]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'];
    }

    // renvoie les user qui suivent un user
    public function findFollowers(string $username): array {
        $stmt = $this->pdo->prepare(
            "SELECT abonne FROM abonnement_utilisateur WHERE suivi = :username ORDER BY abonne"
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // renvoie les user qui sont suivis par un user
    public function findFollows(string $username): array {
        $stmt = $this->pdo->prepare(
            "SELECT suivi FROM abonnement_utilisateur WHERE abonne = :username ORDER BY suivi"
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}