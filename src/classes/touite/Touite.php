<?php

namespace iutnc\touiteur\touite;

use iutnc\touiteur\exception\exception\InvalidPropertyNameException;
use iutnc\touiteur\exception\exception\MessageTooBig;
use iutnc\touiteur\users\User;


class Touite {
    private string $message;
    private array $date;
    private string $id;
    private array $like= [];
    private int $compteurLike = 0;
    private ?User $user ;

    public function __construct(string $m, ?User $u = null, ?string $date = null, ?string $id = null,?int $like = 0) {
        if (strlen($m) > 235) {
            throw new MessageTooBig("Message trop long");
        }

        $this->user = $u;
        $this->message = $m;
        $timestamp = $date !== null ? strtotime($date) : false;
        $this->date = getdate($timestamp !== false ? $timestamp : time()); // Date de la BDD si fournie, sinon mtn (nouveau touite)
        $this->id = $id ?? uniqid(); // id de la BDD si fourni, sinon id temporaire (touite pas encore sauvegardé)
        $this->compteurLike =$like;
    }

    public function setId(string $id): void {
        $this->id = $id;
    }

    public function __get(string $attr): mixed
    {
        if (property_exists($this, $attr)) return $this->$attr;

        if ($attr == "tags") {
            return $this->getListeTag();
        }

        if ($attr == "msg" || $attr == "message") {
            return $this->message;
        }

        throw new InvalidPropertyNameException("$attr: Invalid property");
    }

    public function getListeTag(): array {
        preg_match_all('/#(\w+)/', $this->message, $m);
        return array_unique($m[1]); //array_unique enleve les doublon
    }

    public function getDate(): string {
        return $this->date['mday'] . "/" . $this->date['mon'] . "/" . $this->date['year'];
    }

    public function addNote(string $userId, bool $isLike): void {
        if (isset($this->like[$userId])) {
            if ($isLike) {
                $this->like[$userId] = 1;
                $this->compteurLike++;
            } else {
                $this->like[$userId] = -1;
                $this->compteurLike--;
            }
        } else {
            if ($isLike) {
                $this->like[$userId] = 1;
                $this->compteurLike++;
            } else {
                $this->like[$userId] = -1;
                $this->compteurLike--;
            }
        }
    }
}

