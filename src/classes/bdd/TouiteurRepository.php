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
            $username = $touite->user->formattedUsername;

            $stmtUser = $this->pdo->prepare(
                "SELECT username FROM utilisateur WHERE username = :username"
            );
            $stmtUser->execute(['username' => $username]);

            if (!$stmtUser->fetch()) {
                throw new UnknownUser("Utilisateur inconnu");
            }

            $stmtTouite = $this->pdo->prepare(
                "INSERT INTO touite (username, message) VALUES (:username, :message)"
            );
            $stmtTouite->execute([
                'username' => $username,
                'message' => $touite->message
            ]);

            // Récup l'id du touite
            $stmtId = $this->pdo->prepare(
                "SELECT MAX(id_touite) FROM touite WHERE username = :username"
            );

            $stmtId->execute(['username' => $username]);
            $touite->setId((string) $stmtId->fetchColumn());

            $this->pdo->commit();

            return $touite;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param string $username
     * @return User|null
     */
    public function findUserByUsername(string $username): ?User {
        $query = "SELECT username,nom, prenom, TO_CHAR(date_creation, 'DD/MM/YY') AS date_creation
              FROM utilisateur WHERE username = :username";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row
            ? new User($row["USERNAME"] ,$row["NOM"], $row["PRENOM"], $row["DATE_CREATION"])
            : null;
    }

    /**
     * @param string $username username au format (Nom Prenom) en minuscule et sans espace
     * @return array
     */
    public function findTouitesByUser(string $username): array {
        $query = "
            SELECT touite.id_touite, message, utilisateur.username, nom, prenom,
                   TO_CHAR(date_creation, 'DD/MM/YY') AS date_creation,
                   TO_CHAR(date_touite, 'DD/MM/YY') AS date_touite,
                   NVL((select sum(likes.vote)from likes where touite.id_touite = likes.id_touite),0) as compteur_like
            FROM touite
            JOIN utilisateur ON touite.username = utilisateur.username
            WHERE touite.username = :username
            ORDER BY touite.date_touite DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['username' => $username]);

        $touites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['USERNAME'], $row['NOM'], $row['PRENOM'], $row['DATE_CREATION']);
            $touites[] = new Touite(
                $row['MESSAGE'],
                $user,
                $row['DATE_TOUITE'],
                (string) $row['ID_TOUITE'],
                (int) $row['COMPTEUR_LIKE']
            );
        }

        return $touites;
    }

    public function deleteTouite(string $idTouite, string $username): bool {
        // Optionnel : vérifier d'abord si le touite appartient bien à l'utilisateur
        $stmtCheck = $this->pdo->prepare("SELECT username FROM touite WHERE id_touite = :id");
        $stmtCheck->execute(['id' => $idTouite]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['USERNAME'] !== $username) {
            return false; // Soit le touite n'existe pas, soit l'utilisateur n'en est pas l'auteur
        }

        // Supprimer d'abord les liens avec les tags (si vous avez une table de liaison touite_tag)
        $stmtTag = $this->pdo->prepare("DELETE FROM touite_tag WHERE id_touite = :id");
        $stmtTag->execute(['id' => $idTouite]);

        // Supprimer les likes associés au touite
        $stmtLikes = $this->pdo->prepare("DELETE FROM likes WHERE id_touite = :id");
        $stmtLikes->execute(['id' => $idTouite]);

        // Enfin, supprimer le touite
        $stmt = $this->pdo->prepare("DELETE FROM touite WHERE id_touite = :id");
        $stmt->execute(['id' => $idTouite]);

        return $stmt->rowCount() > 0;
    }

    public function findAllTouites(): array {
        $query = "
            SELECT id_touite, username, message,
                   TO_CHAR(date_touite, 'DD/MM/YY') AS date_touite,
                   NVL((select sum(likes.vote)from likes where touite.id_touite = likes.id_touite),0) as compteur_like
            FROM touite
            ORDER BY touite.date_touite DESC
        ";

        $stmt = $this->pdo->query($query);

        $touites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['USERNAME']);
            $touites[] = new Touite(
                $row['MESSAGE'],
                $user,
                $row['DATE_TOUITE'],
                (string) $row['ID_TOUITE'],
                (int) $row['COMPTEUR_LIKE']
            );
        }

        return $touites;
    }

    public function checkUser(string $username, string $password): User {
        $query = "SELECT username, nom, prenom, mdp, TO_CHAR(date_creation, 'DD/MM/YY') AS date_creation
                  FROM utilisateur WHERE username = :username";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($password, $row['MDP'])) {
            throw new InvalidPassword("Identifiants invalides");
        }

        return new User($row['USERNAME'], $row['NOM'], $row['PRENOM'], $row['DATE_CREATION']);
    }

    public function createUser(string $username,string $email, string $nom, string $prenom, string $password): User {
        $creationDate = date('d/m/y');

        $query = "INSERT INTO utilisateur (username, nom, prenom, mdp, email, date_creation) VALUES (:username, :nom, :prenom, :password, :email, SYSDATE)";
        $stmt = $this->pdo->prepare($query);

        try {
            $stmt->execute([
                'username' => $username,
                'nom' => $nom,
                'prenom' => $prenom,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $email
            ]);
        } catch (PDOException $e) {
            throw new UnableToCreateUser("Impossible de créer l'utilisateur : " . $e->getMessage());
        }

        return new User($username, $nom, $prenom, $creationDate);
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
    public function findTouiteByLike(string $username): array {
        $query = "
            SELECT t.id_touite, t.username, t.message,
                   TO_CHAR(t.date_touite, 'DD/MM/YY') AS date_touite,
                   NVL((select SUM(likes.vote) from likes  WHERE t.id_touite = likes.id_touite), 0) AS compteur_like
            FROM touite t
            JOIN likes ON t.id_touite = likes.id_touite
            WHERE likes.username = :username AND likes.vote = 1
            ORDER BY t.date_touite DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['username' => $username]);

        $touites = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['USERNAME']);
            $touites[] = new Touite(
                $row['MESSAGE'],
                $user,
                $row['DATE_TOUITE'],
                (string) $row['ID_TOUITE'],
                (int) $row['COMPTEUR_LIKE']
            );
        }

        return $touites;
    }
    public function findTouitesByTag(string $tag): array {
        $query = "
            SELECT t.id_touite, t.username, t.message,
                   TO_CHAR(t.date_touite, 'DD/MM/YY') AS date_touite,
                   NVL((select sum(likes.vote)from likes where touite.id_touite = likes.id_touite),0) as compteur_like
            FROM touite t
            JOIN touite_tag tt ON t.id_touite = tt.id_touite
            JOIN tag ON tt.id_tag = tag.id_tag
            WHERE tag.libelle = :tag
            ORDER BY t.date_touite DESC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['tag' => $tag]);

        $touites = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new User($row['USERNAME']);
            $touites[] = new Touite(
                $row['MESSAGE'],
                $user,
                $row['DATE_TOUITE'],
                (string) $row['ID_TOUITE'],
                (int) $row['COMPTEUR_LIKE']
            );
        }

        return $touites;
    }

    /**
     * detecte les hashtags de sortes a faire une liste de ceux ci pour les ajouté a la base
     * @param string $touite
     * @return void
     */
    public function saveTags(Touite $touite): void {
        $this->pdo->beginTransaction();

        try {
            $stmtCheck = $this->pdo->prepare("SELECT COUNT(id_tag) FROM tag WHERE libelle = :tag");
            $stmtInsert = $this->pdo->prepare("INSERT INTO tag (libelle) VALUES (:tag)");
            $stmtIdTag = $this->pdo->prepare("SELECT id_tag FROM tag WHERE libelle = :tag");
            $stmtLink = $this->pdo->prepare("INSERT INTO touite_tag (id_touite, id_tag) VALUES (?, ?)");

            foreach ($touite->getListeTag() as $tag) {
                $stmtCheck->execute(['tag' => $tag]);
                if ($stmtCheck->fetchColumn() == 0) {
                    $stmtInsert->execute(['tag' => $tag]);
                }

                $stmtIdTag->execute(['tag' => $tag]);
                $idTag = $stmtIdTag->fetchColumn();

                $stmtLink->execute([$touite->id, $idTag]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }




    public function userExists(string $username): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM utilisateur WHERE username = :username");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['NB'] > 0;
    }

    public function TagExists(string $tag): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM tag WHERE libelle = :tag");
        $stmt->execute(['tag' => $tag]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['NB'] > 0;
    }

    public function getCreationDate(string $username): ?string {
        $stmt = $this->pdo->prepare(
            "SELECT TO_CHAR(date_creation, 'DD/MM/YY') AS DATE_CREATION FROM utilisateur WHERE username = :username"
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['DATE_CREATION'] : null;
    }

    public function isFollowing(string $suiveur, string $suivi): bool {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS NB FROM abonnement_utilisateur WHERE abonne = :abonne AND suivi = :suivi"
        );
        $stmt->execute(['abonne' => $suiveur, 'suivi' => $suivi]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'] > 0;
    }

    /**
     * vérifie si un utilisateur suit un tag
     * @param string $suiveur
     * @param string $tagSuivi
     * @return bool
     */
    public function isFollowingTag(string $suiveur, string $tagSuivi): bool {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS NB FROM abonnement_tag a JOIN tag t on t.id_tag=a.id_tag WHERE a.username = :abonne AND t.libelle = :tag"
        );
        $stmt->execute(['abonne' => $suiveur, 'tag' => $tagSuivi]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'] > 0;
    }

    /**
     * Suit un utilisateur
     *
     * @param string $suivi La personne suivi
     * @param string $suiveur La personne qui suit l'autre
     * @return bool Est-ce que la personne a bien été follow, si false, il y a eu une erreur
     */
    public function follow(string $suiveur, string $suivi): bool {
        if ($suiveur === $suivi || !$this->userExists($suivi)
            || $this->isFollowing($suiveur, $suivi) || $suivi == (unserialize($_SESSION["user"])->username ?? "")) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO abonnement_utilisateur (abonne, suivi) VALUES (:abonne, :suivi)"
            );
            $stmt->execute(['abonne' => $suiveur, 'suivi' => $suivi]);
            return true;
        } catch (PDOException $e) {
            // Refus par un trigger (ex : même email)
            return false;
        }
    }

    /**
     * Suit un tag
     * @param string $suiveur
     * @param string $tagSuivi
     * @return bool
     */
    public function followTag(string $suiveur, string $tagSuivi): bool {
        //si le tag existe ou si il est déja follow
        if (!$this->tagExists($tagSuivi) || $this->isFollowingTag($suiveur, $tagSuivi)) {
            return false;
        }

        try{
            $stmtGetTagId = $this->pdo->prepare("SELECT id_tag FROM tag WHERE libelle = :tag");
            $stmtInsert = $this->pdo->prepare("INSERT INTO abonnement_tag (username, id_tag) VALUES (:abonne, :tagid)");
            $stmtGetTagId->execute(['tag' => $tagSuivi]);
            $stmtInsert->execute(['abonne' => $suiveur, 'tagid' => $stmtGetTagId->fetchColumn()]);
            return true;

        }catch (PDOException $e) {
            //erreur de trigger
            return false;
        }
    }

    /**
     * Retire le suivi sur un utilisateur
     *
     * @param string $suivi La personne qui ne sera plus suivi
     * @param string $suiveur La personne qui va retirer son suivi
     * @return bool Est-ce que le follow a bien été retirer
     */
    public function unfollow(string $suiveur, string $suivi): bool {
        $stmt = $this->pdo->prepare(
            "DELETE FROM abonnement_utilisateur WHERE abonne = :abonne AND suivi = :suivi"
        );
        $stmt->execute(['abonne' => $suiveur, 'suivi' => $suivi]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Retire le suivi sur un tag
     * @param string $suiveur
     * @param string $tagSuivi
     * @return bool
     */
    public function unfollowTag(string $suiveur, string $tagSuivi): bool {
        $stmtDelete = $this->pdo->prepare(
            "DELETE FROM abonnement_tag WHERE username = :abonne AND id_tag = (SELECT id_tag FROM tag WHERE libelle = :tag)"
        );
        $stmtDelete->execute(['abonne' => $suiveur, 'tag' => $tagSuivi]);
        return $stmtDelete->rowCount() > 0;
    }

    /**
     * compte le nombre de followers d'un user
     * @param string $username
     * @return int
     */
    public function countFollowers(string $username): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM abonnement_utilisateur WHERE suivi = :username");
        $stmt->execute(['username' => $username]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'];
    }

    /**
     * compte le nombre de followers d'un tag
     * @param string $tag
     * @return int
     */
    public function countTagFollowers(string $tag): int{
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM abonnement_tag ab JOIN tag t ON ab.id_tag=t.id_tag WHERE libelle=:tag");
        $stmt->execute(['tag' => $tag]);

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'];
    }

    /**
     * compte le nombre de compte qu'un user follow
     * @param string $username
     * @return int
     */
    public function countFollows(string $username): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM abonnement_utilisateur WHERE abonne = :username");
        $stmt->execute(['username' => $username]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'];
    }

    /**
     * compte le nombre de tags qu'un user follow
     * @param string $username
     * @return int
     */
    public function countTagFollows(string $username): int{
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS NB FROM abonnement_tag WHERE username=:username");
        $stmt->execute(['username' => $username]);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['NB'];
    }

    /**
     * trouve les user qui suivent un user
     * @param string $username
     * @return array
     */
    public function findFollowers(string $username): array {
        $stmt = $this->pdo->prepare(
            "SELECT abonne FROM abonnement_utilisateur WHERE suivi = :username ORDER BY abonne"
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findTagFollowers(string $tag): array {
        $stmt = $this->pdo->prepare(
            "SELECT username FROM abonnement_tag a JOIN tag t on t.id_tag=a.id_tag WHERE t.libelle=:tag ORDER BY username"
        );
        $stmt->execute(['tag' => $tag]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * renvoi les user qui sont suivis par un user
     * @param string $username
     * @return array
     */
    public function findFollows(string $username): array {
        $stmt = $this->pdo->prepare(
            "SELECT suivi FROM abonnement_utilisateur WHERE abonne = :username ORDER BY suivi"
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findTagFollows(string $username): array {
        $stmt = $this->pdo->prepare(
            "SELECT t.libelle FROM abonnement_tag a 
            JOIN tag t on t.id_tag=a.id_tag 
            WHERE a.username=:username 
            ORDER BY t.libelle"
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}