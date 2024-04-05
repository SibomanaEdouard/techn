<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserWithdrawMethod extends Model{

    use HasFactory;

    protected $appends = ['min_limit', 'max_limit']; 

    protected $casts = [
        'user_data' => 'object'

    ];

    public function withdrawMethod(){
        return $this->belongsTo(WithdrawMethod::class, 'method_id');
    }

    public function currency(){
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function scopeMyWithdrawMethod(){   
        $guard = userGuard()['guard'];
        return $this->where('user_type', userGuard()['type'])->where('user_id', userGuard()['user']->id)->whereHas('withdrawMethod', function ($query) use ($guard){
            $query->where('status', 1)->whereJsonContains('user_guards', "$guard");
        });
    }

    public function minLimit(): Attribute{
        return new Attribute( 
            get:function(){
               return showAmount($this->withdrawMethod->min_limit / $this->currency->rate, $this->currency);
            }
        );
    }

    public function maxLimit(): Attribute{
        return new Attribute( 
            get:function(){
               return showAmount($this->withdrawMethod->max_limit / $this->currency->rate, $this->currency);
            }
        );
    }

}
