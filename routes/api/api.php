<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/ 

Route::namespace('Api')->name('api.')->group(function(){

    Route::controller('AppController')->group(function(){
        Route::get('general-setting', 'generalSetting')->name('general.setting');
        Route::get('module-setting', 'moduleSetting')->name('module.setting');
        Route::get('get-countries', 'getCountries')->name('get.countries');
        Route::get('language/{code?}', 'language')->name('language');
        Route::get('policy-pages', 'policyPages')->name('policy.pages');
    });

    include('user.php');
    include('agent.php');
    include('merchant.php');
});
