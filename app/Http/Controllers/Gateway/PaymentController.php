<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Agent;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller{

    public function deposit(){ 
        $pageTitle = 'Payment Methods';
        return view($this->activeTemplate . gatewayView('deposit'), compact('pageTitle'));
    }

    public function depositInsert(Request $request){

        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'method_code' => 'required',
            'currency' => 'required',
        ]);

        $user = userGuard()['user'];
        $currency = Currency::enable()->find($request->currency_id);
        
        if(!$currency) {
            $notify[] = ['error', 'Invalid currency'];
            return back()->withNotify($notify);
        }

        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', 1);
        })->where('method_code', $request->method_code)->where('currency', $request->currency)->first();

        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }
        
        if ($gate->min_amount / $currency->rate > $request->amount || $gate->max_amount / $currency->rate < $request->amount) {
            $notify[] = ['error', 'Please follow deposit limit'];
            return back()->withNotify($notify);
        }

        $charge = $gate->fixed_charge + ($request->amount * $gate->percent_charge / 100);
        $payable = $request->amount + $charge;
        $final_amo = $payable;
        $data = new Deposit();
        $data->user_id = $user->id;

        $data->user_type = userGuard()['type'];
        $data->wallet_id = $request->wallet_id;
        $data->currency_id = $request->currency_id;

        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $request->amount;
        $data->charge = $charge;
        $data->rate = 1;
        $data->final_amo = $final_amo;
        $data->btc_amo = 0;
        $data->btc_wallet = "";
        $data->trx = getTrx();
        $data->save();

        session()->put('Track', $data->trx);
        return to_route(strtolower(userGuard()['type']).'.deposit.confirm');
    } 

    public function appDepositConfirm($hash)
    {  
        try {
            $id = decrypt($hash);
        } catch (\Exception $ex) {
            return "Sorry, invalid URL.";
        }
   
        $data = Deposit::where('id', $id)->where('status', 0)->orderBy('id', 'DESC')->firstOrFail();

        if($data->user_type == 'USER'){
            $user = User::findOrFail($data->user_id);
            Auth::login($user);
            logoutAnother('user');
        }
        elseif($data->user_type == 'AGENT'){
            $user = Agent::findOrFail($data->user_id);
            Auth::guard('agent')->login($user);
            logoutAnother('agent');
        }

        session()->put('Track', $data->trx);
        return to_route(strtolower(userGuard()['type']).'.deposit.confirm');
    }

    public function depositConfirm()
    {   
        $track = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->where('status',0)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return to_route(strtolower(userGuard()['type']).'.deposit.manual.confirm');
        }


        $dirName = $deposit->gateway->alias;
        $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);


        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return to_route(gatewayRedirectUrl())->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if(@$data->session){
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';
        return view($this->activeTemplate . $data->view, compact('data', 'pageTitle', 'deposit'));
    }

    public static function userDataUpdate($deposit,$isManual = null)
    { 
        if ($deposit->status == 0 || $deposit->status == 2) {
            $deposit->status = 1;
            $deposit->save();
        
            if($deposit->user_type == 'USER'){
                $user = User::find($deposit->user_id);
                $userType = 'USER';
            }
            elseif($deposit->user_type == 'AGENT'){   
                $user = Agent::find($deposit->user_id);
                $userType = 'AGENT';
            }
        
            $userWallet = Wallet::find($deposit->wallet_id);
            $userWallet->balance += $deposit->amount;
            $userWallet->save();
             
            $transaction = new Transaction();
            $transaction->user_id = $user->id;

            $transaction->user_type = $deposit->user_type;
            $transaction->wallet_id = $userWallet->id;
            $transaction->currency_id = $deposit->currency_id;
            $transaction->before_charge = $deposit->amount;

            $transaction->amount = $deposit->amount;
            $transaction->post_balance = $userWallet->balance;
            $transaction->charge = 0;
            $transaction->trx_type = '+';
            $transaction->details = 'Add money via ' . $deposit->gatewayCurrency()->name;
            $transaction->trx = $deposit->trx;
            $transaction->remark = 'add_money';
            $transaction->save();

            if (!$isManual) {
                $adminNotification = new AdminNotification();
                $adminNotification->user_type = $userType;
                $adminNotification->user_id = $user->id;
                $adminNotification->title = 'Deposit successful via '.$deposit->gatewayCurrency()->name;
                $adminNotification->click_url = urlPath('admin.deposit.successful');
                $adminNotification->save();
            }

            notify($user, $isManual ? 'DEPOSIT_APPROVE' : 'DEPOSIT_COMPLETE', [
                'method_name' => $deposit->gatewayCurrency()->name,
                'method_currency' => $deposit->method_currency,
                'method_amount' => showAmount($deposit->final_amo, getCurrency($deposit->method_currency)),
                'amount' => showAmount($deposit->amount, $deposit->currency),
                'charge' => showAmount($deposit->charge, $deposit->currency),
                'currency' => $deposit->currency->currency_code,
                'rate' => showAmount($deposit->rate),
                'trx' => $deposit->trx,
                'post_balance' => showAmount($userWallet->balance, $deposit->currency)
            ]);

        }
    }

    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', 0)->where('trx', $track)->first();

        if (!$data) {
            return to_route(gatewayRedirectUrl());
        }

        if ($data->method_code > 999) {
            $pageTitle = 'Deposit Confirm';
            $method = $data->gatewayCurrency();
            $gateway = $method->method;
            return view($this->activeTemplate . gatewayView('manual_confirm',true), compact('data', 'pageTitle', 'method', 'gateway'));
        }

        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');
        $data = Deposit::with('gateway')->where('status', 0)->where('trx', $track)->first();
        $userType = null;

        if($data->user_type == 'USER'){
            $user = User::find($data->user_id);
            $userType = 'USER';
        }
        elseif($data->user_type == 'AGENT'){
            $user = Agent::find($data->user_id);
            $userType = 'AGENT';
        }

        if (!$data) {
            return to_route(gatewayRedirectUrl());
        }

        $gatewayCurrency = $data->gatewayCurrency();
        $gateway = $gatewayCurrency->method;
        $formData = $gateway->form->form_data;

        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);

        $data->detail = $userData;
        $data->status = 2; // pending
        $data->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_type = $userType;
        $adminNotification->user_id = $data->getUser->id;
        $adminNotification->title = 'Add money request from '.$user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details',$data->id);
        $adminNotification->save();
     
        notify($data->getUser, 'DEPOSIT_REQUEST', [
            'method_name' => $data->gatewayCurrency()->name,
            'method_currency' => $data->method_currency,
            'method_amount' => showAmount($data->final_amo), getCurrency($data->method_currency),
            'amount' => showAmount($data->amount, $data->convertedCurrency),
            'charge' => showAmount($data->charge, $data->convertedCurrency),
            'rate' => showAmount(1, $data->convertedCurrency),
            'trx' => $data->trx,
            'currency' => $data->convertedCurrency->currency_code,
        ]);
       
        $notify[] = ['success', 'You add money request has been taken'];
        return to_route(strtolower(userGuard()['type']).'.deposit.history')->withNotify($notify);
    }


}
