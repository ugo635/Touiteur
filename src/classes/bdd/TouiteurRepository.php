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
                throw new UnknownUser("Utilisateur inconnu");
            }

            $stmtTouite = $this->pdo->prepare(
                "INSERT INTO touite (username, message) VALUES (:username, :message)"
            );

            $stmtTouite->execute([
                'username' => $touite->user->username,
                'message' => $touite->message
            ]);

            $this->pdo->commit();

            return $touite;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function findUserByUsername(string $username): ?User {
        $query = "SELECT username, nom, prenom, TO_CHAR(date_creation, 'DD/MM/YY') AS date_creation
              FROM utilisateur WHERE username = :username";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row
            ? new User($row["USERNAME"], $row["NOM"], $row["PRENOM"], $row["DATE_CREATION"])
            : null;
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
            SELECT touite.*
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

    public function checkUser(string $email, string $password): User {
        $query = "SELECT username, nom, prenom, TO_CHAR(date_creation, 'DD/MM/YY') AS date_creation FROM utilisateur WHERE email = :email AND mdp = :password";
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            'email' => $email,
            'password' => $password
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new User($row["USERNAME"],$row["NOM"], $row["PRENOM"], $row["DATE_CREATION"]);
        }

        $query = "SELECT email FROM utilisateur WHERE email = :email";
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            'email' => $email
        ]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new InvalidPassword("MDP pas valide");
        }

        throw new UnknownUser("Utilisateur inconnu");
    }

    public function createUser(string $email, string $nom, string $prenom, string $password): User {
        $username = strtolower($prenom . $nom);
        $creationDate = date('d/m/y');

        $query = "INSERT INTO utilisateur (username, nom, prenom, mdp, email, date_creation) VALUES (:username, :nom, :prenom, :password, :email, SYSDATE)";
        $stmt = $this->pdo->prepare($query);

        try {
            $stmt->execute([
                'username' => $username,
                'nom' => $nom,
                'prenom' => $prenom,
                'password' => $password,
                'email' => $email
            ]);
        } catch (PDOException $e) {
            throw new UnableToCreateUser("Impossible de créer l'utilisateur : " . $e->getMessage());
        }

        return new User($username,$nom, $prenom, $creationDate);
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

    public function findTouitesByTag(string $tag): array {
        $query = "
            SELECT touite.*
            FROM touite
            JOIN touite_tag t ON t.id_touite = touite.id_touite
            WHERE t.nom_tag = :tag
            ORDER BY date_touite DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['tag' => $tag]);

        $touites = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['USERNAME']);
            $touites[] = new Touite($row['MESSAGE'], $user);
        }

        return $touites;
    }

    /**
     * detecte les hashtags de sortes a faire une liste de ceux ci pour les ajouté a la base
     * @param string $touite
     * @return void
     */
    public function saveTags(string $touite): void {
        foreach ($touite->getListeTag() as $tag){
            $qInsertTag = "INSERT INTO tag (libelle) VALUES (:tag)";
            $qIdTag = "SELECT id_tag FROM tag WHERE libelle = :tag";
            $qIdTouite = "SELECT id_touite FROM touite WHERE message = :touite";
            $qInsertTabTouite = "INSERT INTO touite_tag (id_touite, id_tag) VALUES (?, ?)";
            //insert dans tag
            $stmt1 = $this->pdo->prepare($qInsertTag);
            $stmt1->execute(['tag' => $tag]);

            //get id_tag
            $stmt2 = $this->pdo->prepare($qIdTag);
            $stmt2->execute(['tag' => $tag]);

            //get id_touite
            $stmt3 = $this->pdo->prepare($qIdTouite);
            $stmt3=execute(['touite' => $touite]);

            //insert dans touite_tag
            $stmt3 = $this->pdo->prepare($qInsertTabTouite);
            $stmt3->execute([$stmt3, $stmt2->fetchColumn()]);
        }

    }


}