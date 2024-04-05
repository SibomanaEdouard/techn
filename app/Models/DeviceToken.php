<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeviceToken extends Model{
    
    use HasFactory;

    public function scopeForWeb(){
        return $this->where('is_app', 0);
    }

    public function scopeForApp(){
        return $this->where('is_app', 1);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function agent(){
        return  $this->belongsTo(Agent::class,'user_id');
    }

    public function merchant(){
        return  $this->belongsTo(Merchant::class,'user_id');
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

}
