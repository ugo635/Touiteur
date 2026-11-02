<?php
namespace iutnc\touiteur\users;

use iutnc\touiteur\exception\InvalidPropertyNameException;

class User {
    private String $username;
    private array $touites;
    private array $followers;
    private array $follows;
    private array $tagfollows;
    private array $creationDate;

    public function __construct(String $username) {
        $this->username = $username;
        $this->touites = [];
        $this->followers = [];
        $this->follows = [];
        $this->creationDate = getDate();
    }

    public function __get(string $at): mixed {
        if (property_exists ($this, $at)) return $this->$at;
        throw new InvalidPropertyNameException("$at: invalid property");
    }
    
    public function addTouite($touite): void {
        $this->touites[] = $touite;
    }
    public function addFollower($follower): void {
        $this->followers[] = $follower;
    }
    public function addFollow($follow): void {
        $this->follows[] = $follow;
    }
    public function addTagFollow($tag): void {
        $this->tagfollows[] = $tag;
    }

}