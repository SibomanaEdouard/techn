<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\NotificationLog;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ManageGuardController extends Controller{    

    private $type;
    private $model;
    private $guardType; 

    function __construct(Request $request) {

        $request = @$request->route()->action;
        
        $this->guardType = @$request['guardType'];
        $this->model = ucfirst(substr($this->guardType, 0, -1));
        $this->type = strtoupper($this->model);
        $this->model = "App\\Models\\$this->model";

    }
 
    public function allUsers()
    {   
        $pageTitle = 'All '.ucfirst($this->guardType);
        $users = $this->getData();
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function activeUsers()
    {
        $pageTitle = 'Active '.ucfirst($this->guardType);
        $users = $this->getData('active');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function bannedUsers()
    {
        $pageTitle = 'Banned '.ucfirst($this->guardType);
        $users = $this->getData('banned');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function emailUnverifiedUsers()
    {
        $pageTitle = 'Email Unverified '.ucfirst($this->guardType);
        $users = $this->getData('emailUnverified');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function kycUnverifiedUsers()
    {
        $pageTitle = 'KYC Unverified '.ucfirst($this->guardType);
        $users = $this->getData('kycUnverified');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function kycPendingUsers()
    {
        $pageTitle = 'KYC Unverified '.ucfirst($this->guardType);
        $users = $this->getData('kycPending');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function emailVerifiedUsers()
    {
        $pageTitle = 'Email Verified '.ucfirst($this->guardType);
        $users = $this->getData('emailVerified');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function mobileUnverifiedUsers()
    {
        $pageTitle = 'Mobile Unverified '.ucfirst($this->guardType);
        $users = $this->getData('mobileUnverified');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function mobileVerifiedUsers()
    {
        $pageTitle = 'Mobile Verified '.ucfirst($this->guardType);
        $users = $this->getData('mobileVerified');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    public function usersWithBalance()
    {
        $pageTitle = ucfirst($this->guardType).' with Balance';
        $users = $this->getData('withBalance');
        return view("admin.$this->guardType.list", compact('pageTitle', 'users'));
    }

    protected function getData($scope = null){

        if ($scope) {
            $users = $this->model::$scope();
        }else{
            $users = $this->model::query();
        }

        return $users->searchable(['username', 'email'])->orderBy('id','desc')->paginate(getPaginate());
    }

    public function detail($id)
    {       
        $user = $this->model::findOrFail($id);
        $pageTitle = ucfirst($this->guardType).' Detail - '.$user->username;
        $type = $this->type;

        $totalDeposit = Deposit::where('user_id',$user->id)->where('deposits.status',1)->where('user_type', $type)
            ->leftjoin('currencies', 'currencies.id', '=', 'deposits.currency_id')
            ->selectRaw("SUM(amount * currencies.rate) as amount")
        ->first('amount')->amount ?? 0;

        $totalWithdrawals = Withdrawal::where('user_id',$user->id)->where('withdrawals.status',1)->where('user_type', $type)
            ->leftjoin('currencies', 'currencies.id', '=', 'withdrawals.currency_id')
            ->selectRaw("SUM(amount * currencies.rate) as amount")
        ->first('amount')->amount ?? 0;

        $totalTransaction = Transaction::where('user_id',$user->id)->where('user_type', $type)->count();
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $wallets = Wallet::where('user_id',$user->id)->where('user_type', $type)->with('currency')->get();

        $totalMoneyIn = 0;
        $totalMoneyOut = 0;
        $totalGetPaid = 0;

        if($type == 'USER'){
            $totalMoneyOut = Transaction::where('user_id',$user->id)->where('user_type', 'USER')->where('remark','money_out')
                ->leftjoin('currencies', 'currencies.id', '=', 'transactions.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
            ->first('amount')->amount ?? 0;
        }
        elseif($type == 'AGENT'){
            $totalMoneyIn = Transaction::where('user_id',$user->id)->where('user_type', 'AGENT')->where('remark','money_in')
                ->leftjoin('currencies', 'currencies.id', '=', 'transactions.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
            ->first('amount')->amount ?? 0;
        }
        elseif($type == 'MERCHANT'){
            $totalGetPaid = Transaction::where('user_id', $user->id)->where('user_type','MERCHANT')->where('remark', 'merchant_payment')
                ->leftjoin('currencies', 'currencies.id', '=', 'transactions.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
            ->first('amount')->amount ?? 0;
        }

        return view("admin.$this->guardType.detail", compact('pageTitle', 'user','totalDeposit','totalWithdrawals','totalTransaction','countries', 'wallets', 'totalMoneyOut', 'totalMoneyIn', 'totalGetPaid'));
    }

    public function kycDetails($id)
    {  
        $pageTitle = 'KYC Details';
        $user = $this->model::findOrFail($id);
        return view("admin.$this->guardType.kyc_detail", compact('pageTitle','user'));
    }

    public function kycApprove($id)
    {  
        $user = $this->model::findOrFail($id);
        $user->kv = 1;
        $user->save();

        notify($user,'KYC_APPROVE',[]);

        $notify[] = ['success','KYC approved successfully'];
        return to_route("admin.$this->guardType.kyc.pending")->withNotify($notify);
    }

    public function kycReject($id)
    {   
        $user = $this->model::findOrFail($id);
        foreach ($user->kyc_data ?? [] as $kycData) {
            if ($kycData->type == 'file') {
                fileManager()->removeFile(getFilePath('verify').'/'.$kycData->value);
            }
        }
        $user->kv = 0;
        $user->kyc_data = null;
        $user->save();

        notify($user,'KYC_REJECT',[]);

        $notify[] = ['success','KYC rejected successfully'];
        return to_route("admin.$this->guardType.kyc.pending")->withNotify($notify);
    }

    public function update(Request $request, $id)
    { 
        $user = $this->model::findOrFail($id);
        $countryData = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryArray   = (array)$countryData;
        $countries      = implode(',', array_keys($countryArray));

        $countryCode    = $request->country;
        $country        = $countryData->$countryCode->country;
        $dialCode       = $countryData->$countryCode->dial_code;

        $request->validate([
            'firstname' => 'required|string|max:40',
            'lastname' => 'required|string|max:40',
            'email' => 'required|email|string|max:40|unique:users,email,' . $user->id,
            'mobile' => 'required|string|max:40|unique:users,mobile,' . $user->id,
            'country' => 'required|in:'.$countries,
        ]);

        $user->mobile = $dialCode.$request->mobile;
        $user->country_code = $countryCode;
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->email = $request->email;

        $user->address = [
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => @$country,
        ];

        $user->ev = $request->ev ? 1 : 0;
        $user->sv = $request->sv ? 1 : 0;
        $user->ts = $request->ts ? 1 : 0;

        if (!$request->kv) {
            $user->kv = 0;
            if ($user->kyc_data) {
                foreach ($user->kyc_data ?? [] as $kycData) {
                    if ($kycData->type == 'file') {
                        fileManager()->removeFile(getFilePath('verify').'/'.$kycData->value);
                    }
                }
            }
            $user->kyc_data = null;
        }else{
            $user->kv = 1;
        }

        $user->save();

        $notify[] = ['success', ucfirst($this->guardType).' details updated successfully'];
        return back()->withNotify($notify);
    }

    public function addSubBalance(Request $request, $id)
    {   
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act' => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        $user = $this->model::findOrFail($id);
        $wallet = Wallet::where('id', $request->wallet_id)->where('user_id', $user->id)->first();
   
        if(!$wallet){
            $notify[]=['error','Sorry wallet not found'];
            return back()->withNotify($notify);
        }

        $amount = $request->amount;
        $trx = getTrx();

        $transaction = new Transaction();

        if ($request->act == 'add') {
            $wallet->balance += $amount;

            $transaction->trx_type = '+';
            $transaction->remark = 'balance_add';

            $notifyTemplate = 'BAL_ADD';

            $notify[] = ['success', $wallet->currency->currency_symbol . $amount . ' added successfully'];

        } else {
            if ($amount > $wallet->balance) {
                $notify[] = ['error', $user->username . ' doesn\'t have sufficient balance.'];
                return back()->withNotify($notify);
            }

            $wallet->balance -= $amount;

            $transaction->trx_type = '-';
            $transaction->remark = 'balance_subtract';

            $notifyTemplate = 'BAL_SUB';
            $notify[] = ['success', $wallet->currency->currency_symbol . $amount . ' subtracted successfully'];
        }

        $wallet->save();

        $transaction->user_id = $user->id;
        $transaction->user_type = $this->type;
        $transaction->wallet_id = $wallet->id;
        $transaction->currency_id = $wallet->currency_id;
        $transaction->before_charge = $amount;
        $transaction->amount = $amount;
        $transaction->post_balance = $wallet->balance;
        $transaction->charge = 0;
        $transaction->trx =  $trx;
        $transaction->details = $request->remark;
        $transaction->save();

        notify($user, $notifyTemplate, [
            'trx' => $trx,
            'amount' => showAmount($amount, $wallet->currency),
            'remark' => $request->remark,
            'post_balance' => showAmount($wallet->balance,$wallet->currency),
            'wallet_currency' => $wallet->currency->currency_code
        ]);

        return back()->withNotify($notify);
    }

    public function login($id){ 
        $auth = strtolower($this->type);
        
        if($auth != 'user'){
            Auth::guard($auth)->loginUsingId($id);
        }

        Auth::loginUsingId($id);
        
        logoutAnother($auth);
        return to_route("$auth.home");
    }

    public function status(Request $request,$id)
    {   
        $user = $this->model::findOrFail($id);
        if ($user->status == 1) {
            $request->validate([
                'reason'=>'required|string|max:255'
            ]);
            $user->status = 0;
            $user->ban_reason = $request->reason;
            $notify[] = ['success', ucfirst($this->guardType).' banned successfully'];
        }else{
            $user->status = 1;
            $user->ban_reason = null;
            $notify[] = ['success', ucfirst($this->guardType).' unbanned successfully'];
        }
        $user->save();
        return back()->withNotify($notify);
    }

    public function showNotificationSingleForm($id)
    {    
        $user = $this->model::findOrFail($id);
        $general = gs();
        if (!$general->en && !$general->sn) {
            $notify[] = ['warning','Notification options are disabled currently'];
            return to_route("admin.$this->guardType.detail",$user->id)->withNotify($notify);
        }
        $pageTitle = 'Send Notification to ' . $user->username;
        return view("admin.$this->guardType.notification_single", compact('pageTitle', 'user'));
    }

    public function sendNotificationSingle(Request $request, $id)
    {   
        $request->validate([
            'message' => 'required|string',
            'subject' => 'required|string',
        ]);

        $user = $this->model::findOrFail($id);
        notify($user,'DEFAULT',[
            'subject'=>$request->subject,
            'message'=>$request->message,
        ]);
        $notify[] = ['success', 'Notification sent successfully'];
        return back()->withNotify($notify);
    }

    public function showNotificationAllForm()
    {   
        $general = gs();
        if (!$general->en && !$general->sn) {
            $notify[] = ['warning','Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }
        $users = $this->model::active()->count();
        $pageTitle = 'Notification to Verified '.ucfirst($this->guardType);
        return view("admin.$this->guardType.notification_all", compact('pageTitle','users'));
    }

    public function sendNotificationAll(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'message' => 'required',
            'subject' => 'required',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()->all()]);
        }

        $user = $this->model::active()->skip($request->skip)->first();

        if (!$user) {
            return response()->json([
                'error'=> ucfirst($this->guardType).' not found',
                'total_sent'=>0,
            ]);
        }

        notify($user,'DEFAULT',[
            'subject'=>$request->subject,
            'message'=>$request->message,
        ]);

        return response()->json([
            'success'=>'message sent',
            'total_sent'=>$request->skip + 1,
        ]);
    }

    public function notificationLog($id){   
        $user = $this->model::findOrFail($id);
        $pageTitle = 'Notifications Sent to '.$user->username;
        $column = strtolower($this->type).'_id';
        $logs = NotificationLog::where($column, $id)->where('user_type', $this->type)->with(strtolower($this->type))->orderBy('id','desc')->paginate(getPaginate());
        $userType = $this->type;
        return view('admin.reports.notification_history', compact('pageTitle','logs','user', 'userType'));
    }

}
