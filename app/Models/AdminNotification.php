<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class AdminNotification extends Model{

    public function user(){
    	return $this->belongsTo(User::class);
    }

    public function agent(){
    	return $this->belongsTo(Agent::class, 'user_id');
    }

    public function merchant(){
    	return $this->belongsTo(Merchant::class, 'user_id', 'id');
    } 

    public function getUser(): Attribute{ 
        return new Attribute(
            get:function(){
                if ($this->user_type == 'USER'){
                    $user = $this->user;
                }
                elseif($this->user_type == 'AGENT'){
                    $user = $this->agent;
                }
                elseif($this->user_type == 'MERCHANT'){
                    $user = $this->merchant;
                }

                return @$user;
            },
        );
    }

    public function getUserImage(): Attribute{ 
        return new Attribute(
            get:function(){ 
                $userType = strtolower($this->user_type); 
                if($userType){
                    return getImage(getFilePath(@$userType.'Profile').'/'.@$this->getUser->image, getFileSize(@$userType.'Profile'));
                }
            },
        );
    }


    
}
