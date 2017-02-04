<?php

namespace STS\Services\Logic; 

use STS\Contracts\Repository\User as UserRep;
use STS\Contracts\Repository\Friends as FriendsRep;
use STS\Contracts\Repository\Files as FilesRep;

use STS\Repository\SocialRepository;
use STS\Entities\SocialAccount;
use STS\User; 
use STS\Services\Social\SocialProviderInterface;  
use Validator;

class SocialManager extends BaseManager
{

    protected $friendsRepo;
    protected $userRepo;
    protected $filesRepo;
    protected $socialRepository;
    protected $provider;

    public function __construct(SocialProviderInterface $provider, UserRep $userRep, FriendsRep $friendsRepo, FilesRep $files)
    { 
        $this->provider = $provider;
        $this->userRepo = $userRep;
        $this->filesRepo = $files;
        $this->userRepo = $userRep;
        $this->socialRepository = new SocialRepository($provider->getProviderName());        
    }

    public function validator(array $data, $id = null)
    {
        if ($id) {
            return Validator::make($data, [
                'name' => 'max:255',
                'email' => 'email|max:255|unique:users,email' . $id          
            ]);
        } else {
            return Validator::make($data, [
                'name' => 'required|max:255',
                'email' => 'required|email|max:255|unique:users'        
            ]);
        }
    }

    public function loginOrCreate() {
        $data = $this->provider->getUserData();
        $provider_user_id = $data["provider_user_id"];

        $account = $this->socialRepository->find($provider_user_id);
        if ($account) {
            return $this->userRepo->show($account->user_id);            
        }  else {
            unset($data["provider_user_id"]);
            return $this->create($provider_user_id, $data);
        }
    }

    public function syncFriends($user) {
        $friends = $this->getUserFriends();
        foreach ($friends as $friend) {
            $this->friendsRepo->make($user, $friend);
        }
        return true;
    }


    private function create($provider_user_id, $data) { 
        $v = $this->validator($data);
        if ($v->fails()) {
            $this->setErrors($v->errors());
            return null;
        } else { 
            $data['password'] = null;
            if (isset($data['image'])){
                $img = file_get_contents($data['image']); 
                $data['image'] = $this->filesRepo->createFromData($img, 'jpg', 'image/profile/');
            }
            $user = $this->userRepo->create($data);
            $this->socialRepository->create($user, $provider_user_id);
            $this->syncFriends($user);
            return $user;
        } 
    }

    public function getUserFriends() 
    {
        $list = [];
        $friends = $this->provider->getUserFriends();
        foreach ($friends as $friend) {
            $account = $this->socialRepository->find($friend);
            if ($account) {
                $friend_user = $this->userRepo->show($account->user_id); 
                $list[] = $account->user;
            }                            
        }
        return $list;
    } 
    
}