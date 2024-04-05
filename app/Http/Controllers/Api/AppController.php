<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Frontend;
use App\Models\GeneralSetting;
use App\Models\Language;
use App\Models\ModuleSetting;

class AppController extends Controller{

    public function generalSetting(){

        $general = GeneralSetting::first();
        $notify[] = 'General setting data';
        
        return response()->json([
            'remark'=>'general_setting',
            'status'=>'success',
            'message'=>['success'=>$notify], 
            'data'=>[
                'general_setting'=>$general,
            ],
        ]);
    }

    public function moduleSetting(){
        
        $notify[] = 'Module setting data';

        $userModules = ModuleSetting::where('user_type', 'USER')->get();
        $agentModules = ModuleSetting::where('user_type', 'AGENT')->get();
        $merchantModules = ModuleSetting::where('user_type', 'MERCHANT')->get();

        return response()->json([
            'remark'=>'module_setting',
            'status'=>'success',
            'message'=>['success'=>$notify], 
            'data'=>[
                'module_setting'=>[
                    'user'=>$userModules,
                    'agent'=>$agentModules,
                    'merchant'=>$merchantModules,
                ],
            ],
        ]);
    }

    public function getCountries(){

        $c = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $notify[] = 'Get country';

        foreach($c as $k => $country){
            $countries[] = [
                'country'=>$country->country,
                'dial_code'=>$country->dial_code,
                'country_code'=>$k,
            ];
        }

        return response()->json([
            'remark'=>'country_data',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'countries'=>$countries,
            ],
        ]);
    }

    public function language($code = 'en'){

        $language = Language::where('code', $code)->first();
        if (!$language) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>['Language not found']],
            ]);
        }

        $languages = Language::get();
        $path        = base_path() . "/resources/lang/$code.json";
        $fileContent = file_get_contents($path);

        $data = [
            'languages' => $languages,
            'file'      => $fileContent,
        ];

        return response()->json([
            'remark'=>'language',
            'status'=>'success',
            'message'=>['success'=>['Language']],
            'data'=>[
                'data'=>$data
            ]
        ]);
    } 

    public function policyPages(){
  
        $notify[] = 'Policy Pages';
        $policyPages = Frontend::where('data_keys', 'policy_pages.element')->get();

        return response()->json([
            'remark'=>'policy_pages',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'policy_pages'=>$policyPages
            ]
        ]);
    }
}
 
