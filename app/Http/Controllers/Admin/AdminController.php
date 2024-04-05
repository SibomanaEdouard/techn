<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Lib\CurlRequest;
use App\Models\AdminNotification;
use App\Models\Agent;
use App\Models\ChargeLog;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Withdrawal;
use App\Rules\FileTypeValidate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller{
 
    public function dashboard(){

        $pageTitle = 'Dashboard';
        $totalCurrency = Currency::count();

        // User Info
        $widget['total_users']             = User::count();
        $widget['verified_users']          = User::active()->count();
        $widget['email_unverified_users']  = User::emailUnverified()->count();
        $widget['mobile_unverified_users'] = User::mobileUnverified()->count();

        $widget['total_agents']             = Agent::count();
        $widget['verified_agents']          = Agent::active()->count();
        $widget['email_unverified_agents']  = Agent::emailUnverified()->count();
        $widget['mobile_unverified_agents'] = Agent::mobileUnverified()->count();

        $widget['total_merchants']             = Merchant::count();
        $widget['verified_merchants']          = Merchant::active()->count();
        $widget['email_unverified_merchants']  = Merchant::emailUnverified()->count();
        $widget['mobile_unverified_merchants'] = Merchant::mobileUnverified()->count();

        // user Browsing, Country, Operating Log
        $userLoginData = UserLogin::where('created_at', '>=', Carbon::now()->subDay(30))->get(['browser', 'os', 'country']);

        $chart['user_browser_counter'] = $userLoginData->groupBy('browser')->map(function ($item, $key) {
            return collect($item)->count();
        });
        $chart['user_os_counter'] = $userLoginData->groupBy('os')->map(function ($item, $key) {
            return collect($item)->count();
        });
        $chart['user_country_counter'] = $userLoginData->groupBy('country')->map(function ($item, $key) {
            return collect($item)->count();
        })->sort()->reverse()->take(5); 

        @$deposit['total_deposit_amount']        = Deposit::where('deposits.status', 1)
            ->leftjoin('currencies', 'currencies.id', '=', 'deposits.currency_id')
            ->selectRaw('SUM(amount * currencies.rate) as amount')
        ->first('amount')->amount ?? 0; 

        $deposit['total_deposit_pending']       = Deposit::pending()->count();
        $deposit['total_deposit_rejected']      = Deposit::rejected()->count();
        
        $deposit['total_deposit_charge']        = Deposit::where('deposits.status', 1)
            ->leftjoin('currencies', 'currencies.id', '=', 'deposits.currency_id')
            ->selectRaw('SUM(charge * currencies.rate) as charge')
        ->first('charge')->charge ?? 0;

        $withdrawals['total_withdraw_amount']   = Withdrawal::where('withdrawals.status', 1)
            ->leftjoin('currencies', 'currencies.id', '=', 'withdrawals.currency_id')
            ->selectRaw('SUM(amount * currencies.rate) as amount')
        ->first('amount')->amount ?? 0;

        $withdrawals['total_withdraw_pending']  = Withdrawal::pending()->count();
        $withdrawals['total_withdraw_rejected'] = Withdrawal::rejected()->count();

        $withdrawals['total_withdraw_charge']   = Withdrawal::where('withdrawals.status', 1)
            ->leftjoin('currencies', 'currencies.id', '=', 'withdrawals.currency_id')
            ->selectRaw('SUM(charge * currencies.rate) as charge')
        ->first('charge')->charge ?? 0;

        $trxReport['date'] = collect([]);
        $plusTrx = Transaction::where('trx_type','+')->where('transactions.created_at', '>=', Carbon::now()->subDays(30))
                    ->leftjoin('currencies', 'currencies.id', '=', 'transactions.currency_id')
                    ->selectRaw("SUM(amount * currencies.rate) as amount, DATE_FORMAT(transactions.created_at,'%Y-%m-%d') as date")
                    ->orderBy('transactions.created_at')
                    ->groupBy('date')
                    ->get();

        $plusTrx->map(function ($trxData) use ($trxReport) {
            $trxReport['date']->push($trxData->date);
        });

        $minusTrx = Transaction::where('trx_type','-')->where('transactions.created_at', '>=', Carbon::now()->subDays(30))
                    ->leftjoin('currencies', 'currencies.id', '=', 'transactions.currency_id')
                    ->selectRaw("SUM(amount * currencies.rate) as amount, DATE_FORMAT(transactions.created_at,'%Y-%m-%d') as date")
                    ->orderBy('transactions.created_at')
                    ->groupBy('date')
                    ->get();

        $minusTrx->map(function ($trxData) use ($trxReport) {
            $trxReport['date']->push($trxData->date);
        });

        $trxReport['date'] = dateSorting($trxReport['date']->unique()->toArray());

        // Monthly Deposit & Withdraw Report Graph
        $report['months'] = collect([]);
        $report['deposit_month_amount'] = collect([]);
        $report['withdraw_month_amount'] = collect([]);

        $depositsMonth = Deposit::where('deposits.created_at', '>=', Carbon::now()->subYear())
            ->where('deposits.status', 1)
            ->leftjoin('currencies', 'currencies.id', '=', 'deposits.currency_id')
            ->selectRaw("SUM( CASE WHEN deposits.status = 1 THEN (amount * currencies.rate) END) as depositAmount")
            ->selectRaw("DATE_FORMAT(deposits.created_at,'%M-%Y') as months")
            ->orderBy('deposits.created_at')
            ->groupBy('months')
        ->get();

        $depositsMonth->map(function ($depositData) use ($report) {
            $report['months']->push($depositData->months);
            $report['deposit_month_amount']->push(getAmount($depositData->depositAmount));
        });

        $withdrawalMonth = Withdrawal::where('withdrawals.created_at', '>=', Carbon::now()->subYear())
            ->where('withdrawals.status', 1)
            ->leftjoin('currencies', 'currencies.id', '=', 'withdrawals.currency_id')
            ->selectRaw("SUM( CASE WHEN withdrawals.status = 1 THEN (amount * currencies.rate) END) as withdrawAmount")
            ->selectRaw("DATE_FORMAT(withdrawals.created_at,'%M-%Y') as months")
            ->orderBy('withdrawals.created_at')
            ->groupBy('months')
        ->get();

        $withdrawalMonth->map(function ($withdrawData) use ($report){
            if (!in_array($withdrawData->months,$report['months']->toArray())) {
                $report['months']->push($withdrawData->months);
            }
            $report['withdraw_month_amount']->push(getAmount($withdrawData->withdrawAmount));
        });

        $months = $report['months'];

        for($i = 0; $i < $months->count(); ++$i) {
            $monthVal      = Carbon::parse($months[$i]);
            if(isset($months[$i+1])){
                $monthValNext = Carbon::parse($months[$i+1]);
                if($monthValNext < $monthVal){
                    $temp = $months[$i];
                    $months[$i]   = Carbon::parse($months[$i+1])->format('F-Y');
                    $months[$i+1] = Carbon::parse($temp)->format('F-Y');
                }else{
                    $months[$i]   = Carbon::parse($months[$i])->format('F-Y');
                }
            }
        } 
  
        //monthly charge and commission graph
        $report['profit_months'] = collect([]);
        $report['charge_month_amount'] = collect([]);
        $report['commission_month_amount'] = collect([]);

        $charges = ChargeLog::where('charge_logs.created_at', '>=', Carbon::now()->subYear())->where('charge_logs.remark',null)
            ->leftjoin('currencies', 'currencies.id', '=', 'charge_logs.currency_id')
            ->selectRaw("SUM(amount * currencies.rate) as chargeAmount")
            ->selectRaw("DATE_FORMAT(charge_logs.created_at,'%M-%Y') as months")
            ->orderBy('charge_logs.created_at')
            ->groupBy('months')
        ->get();

        $charges->map(function ($chargeData) use ($report) {
            $report['profit_months']->push($chargeData->months);
            $report['charge_month_amount']->push(showAmount($chargeData->chargeAmount));
        });

        $commissions = ChargeLog::where('charge_logs.created_at', '>=', Carbon::now()->subYear())->where('charge_logs.remark', 'commission')
            ->leftjoin('currencies', 'currencies.id', '=', 'charge_logs.currency_id')
            ->selectRaw("SUM(amount * currencies.rate) as commissionAmount")
            ->selectRaw("DATE_FORMAT(charge_logs.created_at,'%M-%Y') as months")
            ->orderBy('charge_logs.created_at')
            ->groupBy('months')
        ->get();

        $commissions->map(function ($commissionData) use ($report){
            if (!in_array($commissionData->months,$report['months']->toArray())) {
                $report['profit_months']->push($commissionData->months);
            }
            $report['commission_month_amount']->push(showAmount(abs($commissionData->commissionAmount)));
        });

        //monthly user Registration
        $report['reg_months'] = collect([]);
        $report['user_reg_count'] = collect([]);
        $report['agent_reg_count'] = collect([]);
        $report['merchant_reg_count'] = collect([]);

        $userReg = User::where('created_at', '>=', Carbon::now()->subYear())->where('status',1)
            ->selectRaw("COUNT(id) as userCount")
            ->selectRaw("DATE_FORMAT(created_at,'%M-%Y') as months")
            ->orderBy('created_at')
            ->groupBy('months')->get();

        $userReg->map(function ($userData) use ($report){
            if (!in_array($userData->months,$report['reg_months']->toArray())) {
                $report['reg_months']->push($userData->months);
            }
            $report['user_reg_count']->push($userData->userCount);
        });

        $agentReg = Agent::where('created_at', '>=', Carbon::now()->subYear())->where('status',1)
            ->selectRaw("COUNT(id) as agentCount")
            ->selectRaw("DATE_FORMAT(created_at,'%M-%Y') as months")
            ->orderBy('created_at')
            ->groupBy('months')->get();

        $agentReg->map(function ($agentData) use ($report){
            if (!in_array($agentData->months,$report['reg_months']->toArray())) {
                $report['reg_months']->push($agentData->months);
            }
            $report['agent_reg_count']->push($agentData->agentCount);
        });

        $merchantReg = Merchant::where('created_at', '>=', Carbon::now()->subYear())->where('status',1)
            ->selectRaw("COUNT(id) as merchantCount")
            ->selectRaw("DATE_FORMAT(created_at,'%M-%Y') as months")
            ->orderBy('created_at')
            ->groupBy('months')->get();

        $merchantReg->map(function ($merchantData) use ($report){
            if (!in_array($merchantData->months,$report['reg_months']->toArray())) {
                $report['reg_months']->push($merchantData->months);
            }
            $report['merchant_reg_count']->push($merchantData->merchantCount);
        });

        $general = gs(); 
        $fiatCron = Carbon::parse(@$general->cron_run->fiat_cron)->diffInSeconds() >= 900;
        $cryptoCron = Carbon::parse(@$general->cron_run->crypto_cron)->diffInSeconds() >= 900;

        return view('admin.dashboard', compact('pageTitle', 'widget', 'chart','deposit','withdrawals','report','depositsMonth','withdrawalMonth','months','trxReport','plusTrx','minusTrx', 'fiatCron', 'cryptoCron', 'totalCurrency'));
    }

    public function profile(){
        $pageTitle = 'Profile';
        $admin = auth('admin')->user();
        return view('admin.profile', compact('pageTitle', 'admin'));
    }

    public function profileUpdate(Request $request){
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'image' => ['nullable','image',new FileTypeValidate(['jpg','jpeg','png'])]
        ]);
        $user = auth('admin')->user();

        if ($request->hasFile('image')) {
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('adminProfile'), getFileSize('adminProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        $notify[] = ['success', 'Profile updated successfully'];
        return to_route('admin.profile')->withNotify($notify);
    }

    public function password(){
        $pageTitle = 'Password Setting';
        $admin = auth('admin')->user();
        return view('admin.password', compact('pageTitle', 'admin'));
    }

    public function passwordUpdate(Request $request){
        $this->validate($request, [
            'old_password' => 'required',
            'password' => 'required|min:5|confirmed',
        ]);

        $user = auth('admin')->user();
        if (!Hash::check($request->old_password, $user->password)) {
            $notify[] = ['error', 'Password doesn\'t match!!'];
            return back()->withNotify($notify);
        }
        $user->password = bcrypt($request->password);
        $user->save();
        $notify[] = ['success', 'Password changed successfully.'];
        return to_route('admin.password')->withNotify($notify);
    }

    public function notifications(){
        $notifications = AdminNotification::orderBy('id','desc')->with('user')->paginate(getPaginate());
        $pageTitle = 'Notifications';
        return view('admin.notifications',compact('pageTitle','notifications'));
    }

    public function notificationRead($id){
        $notification = AdminNotification::findOrFail($id);
        $notification->is_read = 1;
        $notification->save();
        $url = $notification->click_url;
        if ($url == '#') {
            $url = url()->previous();
        }
        return redirect($url);
    }

    public function requestReport(){
        $pageTitle = 'Your Listed Report & Request';
        $arr['app_name'] = systemDetails()['name'];
        $arr['app_url'] = env('APP_URL');
        $arr['purchase_code'] = env('PURCHASE_CODE');
        $url = "https://license.viserlab.com/issue/get?".http_build_query($arr);
        $response = CurlRequest::curlContent($url);
        $response = json_decode($response);
        if ($response->status == 'error') {
            return to_route('admin.dashboard')->withErrors($response->message);
        }
        $reports = $response->message[0];
        return view('admin.reports',compact('reports','pageTitle'));
    }

    public function reportSubmit(Request $request){
        $request->validate([
            'type'=>'required|in:bug,feature',
            'message'=>'required',
        ]);
        $url = 'https://license.viserlab.com/issue/add';

        $arr['app_name'] = systemDetails()['name'];
        $arr['app_url'] = env('APP_URL');
        $arr['purchase_code'] = env('PURCHASE_CODE');
        $arr['req_type'] = $request->type;
        $arr['message'] = $request->message;
        $response = CurlRequest::curlPostContent($url,$arr);
        $response = json_decode($response);
        if ($response->status == 'error') {
            return back()->withErrors($response->message);
        }
        $notify[] = ['success',$response->message];
        return back()->withNotify($notify);
    }

    public function readAll(){
        AdminNotification::where('is_read',0)->update([
            'is_read'=> 1
        ]);
        $notify[] = ['success','Notifications read successfully'];
        return back()->withNotify($notify);
    }

    public function downloadAttachment($fileHash){
        $filePath = decrypt($fileHash);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $general = gs();
        $title = slug($general->site_name).'- attachments.'.$extension;
        $mimetype = mime_content_type($filePath);
        header('Content-Disposition: attachment; filename="' . $title);
        header("Content-Type: " . $mimetype);
        return readfile($filePath);
    }

    public function trxDetailGraph(Request $request){
        
        $pageTitle = "Transaction Detail Graph";

        $request->merge([
            'currency_code'=>strtoupper($request->currency_code),
            'user_type'=>strtoupper($request->user_type),
        ]);

        $dateSearch = $request->date;
        $start = null;
        $end = null;

        if ($dateSearch){
            $date = explode('-',$dateSearch);

            $request->merge([
                'start_date'=> trim(@$date[0]),
                'end_date'  => trim(@$date[1])
            ]);
            $request->validate([
                'start_date'    => 'required|date_format:m/d/Y',
                'end_date'      => 'nullable|date_format:m/d/Y'
            ]);

            $start = showDateTime(@$date[0],'Y-m-d');
            $end = showDateTime(@str_replace(' ','',@$date[1]),'Y-m-d');
        } 

        $report['trx_dates'] = collect([]);
        $report['trx_amount'] = collect([]);

        $currencies = Currency::get(['currency_code','id']);

        $transactions = Transaction::where('transactions.created_at', '>=', Carbon::now()->subYear())
            ->where('transactions.trx_type','+')
            ->leftjoin('currencies', 'currencies.id', '=', 'transactions.currency_id')
            ->selectRaw("DATE_FORMAT(transactions.created_at,'%dth-%M') as dates")
            ->when($dateSearch, function($q) use($start,$end){
                return $q->whereBetween('transactions.created_at',[$start,$end]);
            })
            ->searchable(['user_type'])
            ->filter(['currency:currency_code'])
            ->selectRaw("SUM(amount * currencies.rate) as totalAmount")
            ->orderBy('transactions.created_at')
            ->groupBy('dates')
        ->get();

        $transactions->map(function ($trxData) use ($report) {
            $report['trx_dates']->push($trxData->dates);
            $report['trx_amount']->push($trxData->totalAmount);
        });

        return view('admin.transaction_detail_graph',compact('report','pageTitle','currencies','dateSearch'));
    }


}
