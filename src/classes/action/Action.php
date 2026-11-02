<?php

namespace iutnc\touiteur\action;

abstract class Action {
    protected ?string $http_method = null;
    protected ?string $hostname = null;
    protected ?string $script_name = null;

    public function __construct() {
        $this->http_method = $_SERVER['REQUEST_METHOD'];
        $this->hostname = $_SERVER['HTTP_HOST'];
        $this->script_name = $_SERVER['SCRIPT_NAME'];
    }

    public function __invoke(): string {
        return $this->execute();
    }

    public function execute() : string {
        if ($this->http_method == "GET") return $this->get();
        if ($this->http_method == "POST") return $this->post();
        throw new \InvalidArgumentException("Pas valide");
    }
    abstract public function get() : string;
    abstract public function post() : string;

}