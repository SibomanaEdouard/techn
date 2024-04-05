<?php

namespace App\Http\Controllers;

use App\Models\Currency;

class CronController extends Controller{

    public function fiatRate(){

        $general = gs();
        $general->cron_run = [
            'fiat_cron'=>now(),
            'crypto_cron'=>@$general->cron_run->crypto_cron,
        ];
        $general->save();

        $accessKey   = $general->fiat_currency_api;
        $baseCurrency = defaultCurrency();

        $curl = curl_init();

        $fiats  = Currency::where('currency_type', 1)->pluck('currency_code')->toArray();
        $fiats = implode(',', $fiats);

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.apilayer.com/exchangerates_data/latest?symbols=$fiats&base=$baseCurrency",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: text/plain",
                "apikey: $accessKey"
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ));
          
        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response);
        $rates = @$response->rates;

        if(!@$rates){
            echo @$response->message;
        }

        foreach (@$rates ?? [] as $currencyCode => $rate) { 
            $currency = Currency::where('currency_code', $currencyCode)->first();
            $currency->rate = 1/$rate;
            $currency->save();
        }

        echo "<br/>Executed...";
    }

    public function cryptoRate(){

        $general = gs();
        $general->cron_run = [
            'fiat_cron'=>@$general->cron_run->fiat_cron,
            'crypto_cron'=>now(),
        ];
        $general->save();

        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest';
        $cryptos = Currency::where('currency_type', 2)->pluck('currency_code')->toArray();
        $cryptos = implode(',', $cryptos);
        
        $parameters = [
            'symbol' => $cryptos,
            'convert' => defaultCurrency(),
        ];
    
        $headers = [
            'Accepts: application/json',
            'X-CMC_PRO_API_KEY:' . trim($general->crypto_currency_api),
        ];

        $qs      = http_build_query($parameters); // query string encode the parameters
        $request = "{$url}?{$qs}"; // create the request URL
        $curl    = curl_init(); // Get cURL resource

        // Set cURL options
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $request, // set the request URL
            CURLOPT_HTTPHEADER     => $headers, // set the headers
            CURLOPT_RETURNTRANSFER => 1, // ask for raw response instead of bool
        ));

        $response = curl_exec($curl); // Send the request, save the response
        curl_close($curl); // Close request

        $response = json_decode($response);
  
        if(!@$response->data){
            echo 'error';
        }

        $coins = @$response->data ?? [];

        foreach (@$coins as $coin) {
            $currency = Currency::where('currency_code', $coin->symbol)->first();

            if ($currency) {
                $defaultCurrency = defaultCurrency();
                $currency->rate = $coin->quote->$defaultCurrency->price;
                $currency->save();
            }
        }

        echo "<br/>Executed...";
    }

    public function all(){

        set_time_limit(0);
        ini_set('max_execution_time', 0);

        $this->fiatRate();
        $this->cryptoRate();

        //For manual run from admin panel
        if(url()->previous() == route('admin.dashboard')){
            $notify[] = ['success', 'Manually cron run successfully'];
            return back()->withNotify($notify);
        }

        return "<br/>Executed all...";
    }

}
