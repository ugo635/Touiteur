<?php
class User {
    private String $username;
    private array $touites;
    private array $followers;
    private array $follows;
    private String $creationDate;

    public function __construct(String $username) {
        $this->username = $username;
        $this->touites = [];
        $this->followers = [];
        $this->follows = [];
        $this->creationDate = getDate();
    }

    public function __get(string $at):mixed {
        if (property_exists ($this, $at)) return $this->$at;
        throw new Exception ("$at: invalid property");
    }
    
    public function addTouite($touite) : void {
        $this->touites[] = $touite;
    }
    public function addfollowers($follower) : void {
        $this->followers[] = $follower;
    }
    public function addfollows($follow) : void {
        $this->follows[] = $follow;
    }

}