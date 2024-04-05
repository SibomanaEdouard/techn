<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\GlobalStatus;

class WithdrawMethod extends Model
{
    use GlobalStatus;

    protected $casts = [
        'user_guards' => 'object',
        'currencies' => 'object',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function currency()
    {
        return Currency::find($this->currencies)->pluck('currency_code','id');
    }
}
