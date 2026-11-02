<?php

namespace iutnc\touiteur\touite;

use iutnc\touiteur\exception\InvalidPropertyNameException;
use iutnc\touiteur\exception\MessageTooBig;


class Touite {
    private string $message;
    private array $date;
    private string $id;
    private array $like= [];
    private int $CompteurLike = 0;

    public function __construct(string $m) {
        if (strlen($m) > 235) {
            throw new MessageTooBig("Message trop long");
        } else {
            $this->message = $m;
            $this->date = getDate();
            $this->id = uniqid();
            $this->CompteurLike = 0;
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
                $this->CompteurLike++;
            } else {
                $this->like[$userId] = -1;
                $this->CompteurLike--;
            }
        } else {
            if ($isLike) {
                $this->like[$userId] = 1;                $this->CompteurLike++;
            } else {
                $this->like[$userId] = -1;                $this->CompteurLike--;
            }
        }
    }
}

