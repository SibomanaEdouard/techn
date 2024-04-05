<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function pending(){
        $pageTitle = 'Pending Withdrawals';
        $withdrawals = $this->withdrawalData('pending');
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }

    public function approved(){
        $pageTitle = 'Approved Withdrawals';
        $withdrawals = $this->withdrawalData('approved');
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }

    public function rejected(){
        $pageTitle = 'Rejected Withdrawals';
        $withdrawals = $this->withdrawalData('rejected');
        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals'));
    }

    public function log($userType = null){
        $pageTitle = 'Withdrawals Log';
        $withdrawalData = $this->withdrawalData(scope:null, summery:true);
        $withdrawals = $withdrawalData['data'];
        $summery = $withdrawalData['summery'];
        $successful = $summery['successful'];
        $pending = $summery['pending'];
        $rejected = $summery['rejected'];

        return view('admin.withdraw.withdrawals', compact('pageTitle', 'withdrawals','successful','pending','rejected'));
    }

    protected function typeFormat(){
        
        $userType = @request()->user_type;
        $userType = strtolower(@$userType);

        $array = [
            'user'=> ['type'=>'USER', 'with'=>['user']],
            'agent'=> ['type'=>'AGENT', 'with'=>['agent']],
            'merchant'=> ['type'=>'MERCHANT', 'with'=>['merchant']],
            'all'=> ['type'=>'*', 'with'=>['user', 'agent', 'merchant']]
        ];

        return @$array[$userType] ?? @$array['all'];
    }

    protected function withdrawalData($scope = null, $summery = false){

        $response = $this->typeFormat(); 
        $with = array_merge($response['with'], ['method', 'curr']);
   
        if ($scope) {
            $withdrawals = Withdrawal::$scope()->with($with);
        }else{
            $withdrawals = Withdrawal::where('withdrawals.status','!=',0)->with($with);
        }
       
        if($response['type'] != '*'){
            $withdrawals = $withdrawals->where('user_type', $response['type']);
        }

        $request = request();
        $request->merge(['user_type'=> strtoupper($request->user_type)]);
        
        if($request->search){
            $withdrawals = $withdrawals->where(function($q)  use ($request, $response) {
                foreach($response['with'] as $relation){ 
                    $q->orWhereHas($relation, function ($query) use ($request, $relation) {
                        $query->where('username', 'like',"%$request->search%")->where('user_type', strtoupper($relation));
                    });
                }
            })->orWhere('trx','LIKE',"%$request->search%");
        }
       
        $withdrawals = $withdrawals->filter(['user_type', 'curr:currency_id'])->dateFilter(table:'withdrawals');

        //via method
        if ($request->method) {
            $withdrawals = $withdrawals->where('method_id',$request->method);
        }
        if (!$summery) {
            return $withdrawals->orderBy('id','desc')->paginate(getPaginate());
        }else{

            $successful = clone $withdrawals;
            $pending = clone $withdrawals;
            $rejected = clone $withdrawals;

            $successfulSummery = $successful->where('withdrawals.status',1)
                ->leftjoin('currencies', 'currencies.id', '=', 'withdrawals.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
            ->first('amount')->amount ?? 0;
       
            $pendingSummery = $pending->where('withdrawals.status',2)
                ->leftjoin('currencies', 'currencies.id', '=', 'withdrawals.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
            ->first('amount')->amount ?? 0;
            
            $rejectedSummery = $rejected->where('withdrawals.status',3)
                ->leftjoin('currencies', 'currencies.id', '=', 'withdrawals.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
            ->first('amount')->amount ?? 0;


            return [
                'data'=> $withdrawals->orderBy('id','desc')->paginate(getPaginate()),
                'summery'=>[
                    'successful'=>$successfulSummery,
                    'pending'=>$pendingSummery,
                    'rejected'=>$rejectedSummery,
                ]
            ];
        }
    }

    public function details($id){   
        $general = gs();
        $withdrawal = Withdrawal::where('id',$id)->where('status', '!=', 0)->with(['user','method'])->firstOrFail();
        $pageTitle = @$withdrawal->getUser->username.' Withdraw Requested ' . showAmount($withdrawal->amount) . ' '.$general->cur_text;
        $details = $withdrawal->withdraw_information ? json_encode($withdrawal->withdraw_information) : null;

        return view('admin.withdraw.detail', compact('pageTitle', 'withdrawal','details'));
    }

    public function approve(Request $request){   
        $request->validate(['id' => 'required|integer']);
        $withdraw = Withdrawal::where('id',$request->id)->where('status',2)->with('user')->firstOrFail();
        $withdraw->status = 1;
        $withdraw->admin_feedback = $request->details;
        $withdraw->save();

        notify($withdraw->getUser, 'WITHDRAW_APPROVE', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => showAmount($withdraw->final_amount),
            'amount' => showAmount($withdraw->amount),
            'charge' => showAmount($withdraw->charge),
            'rate' => showAmount($withdraw->rate),
            'trx' => $withdraw->trx,
            'admin_details' => $request->details
        ]);

        $notify[] = ['success', 'Withdrawal approved successfully'];
        return to_route('admin.withdraw.pending')->withNotify($notify);
    }

    public function reject(Request $request){  
        $general = gs();
        $request->validate(['id' => 'required|integer']);
        $withdraw = Withdrawal::where('id',$request->id)->where('status',2)->with('user')->firstOrFail();

        $withdraw->status = 3;
        $withdraw->admin_feedback = $request->details;
        $withdraw->save();

        $wallet = Wallet::find($withdraw->wallet_id);
        $wallet->balance += $withdraw->amount;
        $wallet->save();

        $user = $withdraw->getUser;

        $transaction = new Transaction();
        $transaction->user_id = $withdraw->user_id;
        $transaction->user_type = $withdraw->user_type;
        $transaction->currency_id = $withdraw->currency_id;
        $transaction->before_charge = $withdraw->amount;
        $transaction->amount = $withdraw->amount;
        $transaction->post_balance = $wallet->balance;
        $transaction->charge = 0;
        $transaction->charge_type = '+';
        $transaction->trx_type = '+';
        $transaction->remark = 'withdraw_reject';
        $transaction->details = showAmount($withdraw->amount) . ' ' . $general->cur_text . ' Refunded from withdrawal rejection';
        $transaction->trx = $withdraw->trx;
        $transaction->save();

        notify($user, 'WITHDRAW_REJECT', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => showAmount($withdraw->final_amount),
            'amount' => showAmount($withdraw->amount),
            'charge' => showAmount($withdraw->charge),
            'rate' => showAmount($withdraw->rate),
            'trx' => $withdraw->trx,
            'post_balance' => showAmount($user->balance),
            'admin_details' => $request->details
        ]);

        $notify[] = ['success', 'Withdrawal rejected successfully'];
        return to_route('admin.withdraw.pending')->withNotify($notify);
    }

}
