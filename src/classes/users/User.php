<?php
namespace iutnc\touiteur\users;

use iutnc\touiteur\exception\exception\InvalidPropertyNameException;

class User {
    private String $username;
    private array $touites;
    private array $followers;
    private array $follows;
    private array $tagfollows;
    private array $likes;
    private string $creationDate;

    public function __construct(string $username, string $date) {
        $this->username = $username;
        $this->creationDate = $date;
        $this->touites = [];
        $this->followers = [];
        $this->follows = [];
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
    public function addLike($like): void {
        $this->likes[] = $like;
    }

}