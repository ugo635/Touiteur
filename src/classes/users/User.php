<?php
namespace iutnc\touiteur\users;

use iutnc\touiteur\exception\exception\InvalidPropertyNameException;

class User {
    private array $touites;
    private array $followers;
    private array $follows;
    private array $tagfollows;
    private array $likes;

    public function __construct(private ?string $username=null,private ?string $nom = null, private ?string $prenom = null, private ?string $creationDate = null) {
        $this->touites = [];
        $this->followers = [];
        $this->follows = [];
        $this->tagfollows = [];
        $this->likes = [];

        $this->tagfollows = [];
        $this->likes = [];
    }

    public function __get(string $at): mixed {
        if (property_exists ($this, $at)) return $this->$at;
        else if ($at == "username") return "$this->username";
        else if ($at == "formattedUsername") return strtolower(str_replace(" ", "", $this->username));
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