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

    public function follow(User $userToFollow): bool {
        // Vérifier que user ne se suit pas
        if ($userToFollow->username === $this->username) {
            return false;
        }

        // Verif si il suit pas déjà ce user
        if ($this->isFollowing($userToFollow)) {
            return false;
        }

        $this->follows[] = $userToFollow;
        $userToFollow->addFollower($this);
        return true;
    }

    public function unfollow(User $userToUnfollow): bool {
        foreach ($this->follows as $key => $user) {
            if ($user->username === $userToUnfollow->username) {
                unset($this->follows[$key]);
                $userToUnfollow->removeFollower($this);
                return true;
            }
        }
        return false;
    }

    public function isFollowing(User $user): bool {
        foreach ($this->follows as $followedUser) {
            if ($followedUser->username === $user->username) {
                return true;
            }
        }
        return false;
    }

    public function removeFollower(User $follower): void {
        foreach ($this->followers as $key => $user) {
            if ($user->username === $follower->username) {
                unset($this->followers[$key]);
                break;
            }
        }
    }

    public function getFollowersCount(): int {
        return count($this->followers);
    }

    public function getFollowsCount(): int {
        return count($this->follows);
    }

}