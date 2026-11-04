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

    public function __construct(string $m, ?User $u = null) {
        if (strlen($m) > 235) {
            throw new MessageTooBig("Message trop long");
        } else {
            $this->user = $u;
            $this->message = $m;
            $this->date = getDate();
            $this->id = uniqid();
            $this->compteurLike = 0;
        }

    }

    public function __get(string $attr): mixed
    {
        if (property_exists($this, $attr)) return $this->$attr;

        if ($attr == "tags") {
            return $this->getListeTag();
        }

        throw new InvalidPropertyNameException("$attr: Invalid property");
    }

    public function getListeTag(): array {
        preg_match_all('/#(\w+)/', $this->message, $m);
        return $m[1];
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

