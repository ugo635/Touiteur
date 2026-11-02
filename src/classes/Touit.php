<?php

class Touit {
    private string $message;
    private array $date;

    public function __construct(string $m) {
        if ($this->message.size > 235){
            throw new InvalidArgumentException("Message trop long");
            $this->message = "";
            $this->date = getDate();
        } else {
            $this->message = $m;
            $this->date = getDate();
        }

    }

}