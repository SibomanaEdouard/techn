<?php

namespace App\Http\Controllers\Admin;

use App\Models\Deposit;
use App\Models\Gateway;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function pending()
    {
        $pageTitle = 'Pending Deposits';
        $deposits = $this->depositData('pending');
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function approved()
    {
        $pageTitle = 'Approved Deposits';
        $deposits = $this->depositData('approved');
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function successful()
    {
        $pageTitle = 'Successful Deposits';
        $deposits = $this->depositData('successful');
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function rejected()
    {
        $pageTitle = 'Rejected Deposits';
        $deposits = $this->depositData('rejected');
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function initiated()
    {
        $pageTitle = 'Initiated Deposits';
        $deposits = $this->depositData('initiated');
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function deposit($userType = null)
    {
        $pageTitle = 'Deposit History';
        $depositData = $this->depositData(scope: null, summery: true);
        $deposits = $depositData['data'];
        $summery = $depositData['summery'];
        $successful = $summery['successful'];
        $pending = $summery['pending'];
        $rejected = $summery['rejected'];
        $initiated = $summery['initiated'];

        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'successful', 'pending', 'rejected', 'initiated'));
    }

    protected function typeFormat()
    {

        $userType = @request()->user_type;
        $userType = strtolower(@$userType);

        $array = [
            'user' => ['type' => 'USER', 'with' => ['user']],
            'agent' => ['type' => 'AGENT', 'with' => ['agent']],
            'all' => ['type' => '*', 'with' => ['user', 'agent']]
        ];

        return @$array[$userType] ?? @$array['all'];
    }

    protected function depositData($scope = null, $summery = false, $userType = null)
    {

        $response = $this->typeFormat();
        $with = array_merge($response['with'], ['gateway']);

        if ($scope) {
            $deposits = Deposit::$scope()->with($with);
        } else {
            $deposits = Deposit::with($with);
        }

        if ($response['type'] != '*') {
            $deposits = $deposits->where('user_type', $response['type']);
        }

        $request = request();
        $request->merge(['user_type' => strtoupper($request->user_type)]);

        if ($request->search) {
            $deposits = $deposits->where(function ($q)  use ($request, $response) {
                foreach ($response['with'] as $relation) {
                    $q->orWhereHas($relation, function ($query) use ($request, $relation) {
                        $query->where('username', 'like', "%$request->search%")->where('user_type', strtoupper($relation));
                    });
                }
            })->orWhere('trx', 'LIKE', "%$request->search%");
        }

        //date search
        $deposits = $deposits->filter(['user_type', 'currency:currency_id'])->dateFilter(table: 'deposits');

        //vai method
        if ($request->method) {
            $method = Gateway::where('alias', $request->method)->firstOrFail();
            $deposits = $deposits->where('method_code', $method->code);
        }

        if (!$summery) {
            return $deposits->orderBy('id', 'desc')->paginate(getPaginate());
        } else {
            $successful = clone $deposits;
            $pending = clone $deposits;
            $rejected = clone $deposits;
            $initiated = clone $deposits;

            $successfulSummery = $successful->where('deposits.status', 1)
                ->leftjoin('currencies', 'currencies.id', '=', 'deposits.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
                ->first('amount')->amount ?? 0;

            $pendingSummery = $pending->where('deposits.status', 2)
                ->leftjoin('currencies', 'currencies.id', '=', 'deposits.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
                ->first('amount')->amount ?? 0;

            $rejectedSummery = $rejected->where('deposits.status', 3)
                ->leftjoin('currencies', 'currencies.id', '=', 'deposits.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
                ->first('amount')->amount ?? 0;

            $initiatedSummery = $initiated->where('deposits.status', 0)
                ->leftjoin('currencies', 'currencies.id', '=', 'deposits.currency_id')
                ->selectRaw("SUM(amount * currencies.rate) as amount")
                ->first('amount')->amount ?? 0;

            return [
                'data' => $deposits->orderBy('id', 'desc')->paginate(getPaginate()),
                'summery' => [
                    'successful' => $successfulSummery,
                    'pending' => $pendingSummery,
                    'rejected' => $rejectedSummery,
                    'initiated' => $initiatedSummery,
                ]
            ];
        }
    }

    public function details($id)
    {
        $general = gs();
        $deposit = Deposit::where('id', $id)->with(['user', 'agent', 'gateway'])->firstOrFail();
        $pageTitle = @$deposit->getUser->username . ' requested ' . showAmount($deposit->amount) . ' ' . $general->cur_text;
        $details = ($deposit->detail != null) ? json_encode($deposit->detail) : null;
        return view('admin.deposit.detail', compact('pageTitle', 'deposit', 'details'));
    }

    public function approve($id)
    {
        $deposit = Deposit::where('id', $id)->where('status', 2)->firstOrFail();

        PaymentController::userDataUpdate($deposit, true);

        $notify[] = ['success', 'Deposit request approved successfully'];
        return to_route('admin.deposit.pending')->withNotify($notify);
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'message' => 'required|string|max:255'
        ]);

        $deposit = Deposit::where('id', $request->id)->where('status', 2)->firstOrFail();

        $deposit->admin_feedback = $request->message;
        $deposit->status = 3;
        $deposit->save();

        if ($deposit->user_type == 'USER') {
            $user = User::find($deposit->user_id);
        } elseif ($deposit->user_type == 'AGENT') {
            $user = Agent::find($deposit->user_id);
        }

        notify($user, 'DEPOSIT_REJECT', [
            'method_name' => $deposit->gatewayCurrency()->name,
            'method_currency' => $deposit->method_currency,
            'method_amount' => showAmount($deposit->final_amo, getCurrency($deposit->method_currency)),
            'amount' => showAmount($deposit->amount, $deposit->currency),
            'charge' => showAmount($deposit->charge, $deposit->currency),
            'rate' => showAmount($deposit->rate, $deposit->currency),
            'trx' => $deposit->trx,
            'rejection_message' => $request->message,
            'currency' => $deposit->currency->currency_code,
        ]);

        $notify[] = ['success', 'Deposit request rejected successfully'];
        return  to_route('admin.deposit.pending')->withNotify($notify);
    }
}
