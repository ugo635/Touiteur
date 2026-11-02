<?php

namespace iutnc\touiteur\touite;

use iutnc\touiteur\exception\InvalidPropertyNameException;
use iutnc\touiteur\exception\MessageTooBig;
use function Sodium\add;

class Touite {
    private string $message;
    private array $date;
    private string $id;

    public function __construct(string $m){
        if (strlen($m) > 235) {
            throw new MessageTooBig("Message trop long");
            $this->message = "";
            $this->date = getDate();
        } else {
            $this->message = $m;
            $this->date = getDate();
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

    public function getDate(): array {
        return $this->date;
    }


}

