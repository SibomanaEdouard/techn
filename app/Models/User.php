<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\UserPartials;

class User extends Authenticatable{
    
    use HasApiTokens, UserPartials, Searchable;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','ver_code',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'address' => 'object',
        'kyc_data' => 'object',
        'ver_code_send_at' => 'datetime'
    ];

    public function wallets(){
        return $this->hasMany(Wallet::class, 'user_id')->where('user_type', 'USER')->whereHas('currency', function($q){
            $q->where('status', 1);
        })->with('currency');
    }

    public function qrCode(){
        return $this->hasOne(QRcode::class, 'user_id')->where('user_type', 'USER');
    }

    public function loginLogs()
    {
        return $this->hasMany(UserLogin::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('transactions.id','desc');
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class)->where('status','!=',0);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class)->where('status','!=',0);
    }

    public function fullname(): Attribute
    {
        return new Attribute(
            get: fn () => $this->firstname . ' ' . $this->lastname,
        );
    }
 
    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('status', 1)->where('ev', 1)->where('sv', 1);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', 0);
    }

    public function scopeEmailUnverified($query)
    {
        return $query->where('ev', 0);
    }

    public function scopeMobileUnverified($query)
    {
        return $query->where('sv', 0);
    }

    public function scopeKycUnverified($query)
    {
        return $query->where('kv', 0);
    }

    public function scopeKycPending($query)
    {
        return $query->where('kv', 2);
    }

    public function scopeEmailVerified($query)
    {
        return $query->where('ev', 1);
    }

    public function scopeMobileVerified($query)
    {
        return $query->where('sv', 1);
    }

    public function scopeWithBalance($query)
    { 
        return $query->whereHas('wallets', function($wallet){
            $wallet->where('balance','>', 0);
        });
    }

    public function getDeviceTokens(){
        return $this->hasMany(DeviceToken::class);
    }

    public function deviceTokens(){
        return $this->getDeviceTokens()->where(function ($query){
            $query->where('user_type', 'USER');
        })->get();
    }

}
