<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\Form;
use App\Models\Wallet;
use App\Lib\FormProcessor;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use App\Http\Controllers\Api\Common;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\DeviceToken;
use App\Models\GeneralSetting;
use App\Models\QRcode;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class AgentController extends Controller
{

    use Common;

    public function dashboard()
    {      
        $user = auth()->user('agent');

        $totalAddMoney = Deposit::where('user_type', 'AGENT')
        ->where('user_id',$user->id)->where('deposits.status',1)
        ->leftJoin('currencies','currencies.id','=','deposits.currency_id')
        ->selectRaw('SUM(amount * currencies.rate) as finalAmount')
        ->first();

        $totalWithdraw = Withdrawal::where('user_type', 'AGENT')
        ->where('user_id',$user->id)
        ->where('withdrawals.status',1)
        ->leftJoin('currencies','currencies.id','=','withdrawals.currency_id')
        ->selectRaw('SUM(amount * currencies.rate) as finalAmount')->first();

        $wallets = Wallet::hasCurrency()->where('user_id', $user->id)->where('user_type', 'AGENT')
            ->select(DB::raw('*'))
            ->addSelect(DB::raw('
                (select count(*) 
                from transactions
                where wallet_id = wallets.id) 
                as transactions
        '))->orderBy('transactions', 'desc')->take(3)->get();

        $latestTrx = Transaction::where('user_id', $user->id)->where('user_type', 'AGENT')
            ->with('currency', 'receiverUser', 'receiverAgent', 'receiverMerchant')->orderBy('id', 'desc')->take(10)
        ->get();

        $date = Carbon::today()->subDays(7);
        $moneyIn = Transaction::where('user_id', $user->id)->where('user_type', 'AGENT')->where('trx_type', '+')->whereDate('created_at', '>=', $date)
        ->with('currency')->get(['amount', 'currency_id']);

        $moneyOut = Transaction::where('user_id', $user->id)->where('user_type', 'AGENT')->where('trx_type', '-')->whereDate('created_at', '>=', $date)
        ->with('currency')->get(['amount', 'currency_id']);
        
        $totalMoneyIn = 0;
        $totalMoneyOut = 0;

        $in = [];
        foreach ($moneyIn as $inTrx) {
            $in[] = $inTrx->amount * $inTrx->currency->rate;
        }
        $totalMoneyIn = array_sum($in);

        $out = [];
        foreach ($moneyOut as $outTrx) {
            $out[] = $outTrx->amount * $outTrx->currency->rate;
        }

        $totalMoneyOut = array_sum($out);
        $totalMoneyInOut = ['totalMoneyIn' => $totalMoneyIn, 'totalMoneyOut' => $totalMoneyOut];

        $general = GeneralSetting::first();
        $notify[] = 'Dashboard';

        return response()->json([
            'remark'=>'dashboard',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'agent'=>$user, 
                'wallets'=>$wallets,
                'latest_trx'=>$latestTrx,
                'last_7_day_money_in_out'=>$totalMoneyInOut,
                'total_add_money'=>showAmount($totalAddMoney->finalAmount ?? 0, $general->currency).' '.$general->cur_text,
                'total_withdraw'=>showAmount($totalWithdraw->finalAmount ?? 0, $general->currency).' '.$general->cur_text,
            ]
        ]);
    }

    public function userDataSubmit(Request $request)
    {
        $user = auth()->user('agent');
        if ($user->profile_complete == 1) {
            $notify[] = 'You\'ve already completed your profile';
            return response()->json([
                'remark'=>'already_completed',
                'status'=>'error',
                'message'=>['error'=>$notify],
            ]);
        }
        $validator = Validator::make($request->all(), [
            'firstname'=>'required',
            'lastname'=>'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }


        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->address = [
            'country'=>@$user->address->country,
            'address'=>$request->address,
            'state'=>$request->state,
            'zip'=>$request->zip,
            'city'=>$request->city,
        ];
        $user->profile_complete = 1;
        $user->save();

        $notify[] = 'Profile completed successfully';
        return response()->json([
            'remark'=>'profile_completed',
            'status'=>'success',
            'message'=>['success'=>$notify],
        ]);
    }

    public function kycForm()
    {   
        if (auth()->user('agent')->kv == 2) {
            $notify[] = 'Your KYC is under review';
            return response()->json([
                'remark'=>'under_review',
                'status'=>'error',
                'message'=>['error'=>$notify],
            ]);
        }
        if (auth()->user('agent')->kv == 1) {
            $notify[] = 'You are already KYC verified';
            return response()->json([
                'remark'=>'already_verified',
                'status'=>'error',
                'message'=>['error'=>$notify],
            ]);
        }   
        $form = Form::where('act','agent_kyc')->first();
        $notify[] = 'KYC field is below';
        return response()->json([
            'remark'=>'kyc_form',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'form'=>$form->form_data
            ]
        ]);
    }

    public function kycSubmit(Request $request)
    {   
        $form = Form::where('act','agent_kyc')->first();
        $formData = $form->form_data;
        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);

        $validator = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }

        $userData = $formProcessor->processFormData($request, $formData);
        $user = auth()->user('agent');
        $user->kyc_data = $userData;
        $user->kv = 2;
        $user->save();

        $notify[] = 'KYC data submitted successfully';
        return response()->json([
            'remark'=>'kyc_submitted',
            'status'=>'success',
            'message'=>['success'=>$notify],
        ]);

    }

    public function depositHistory()
    {     
        $deposits = Deposit::where('user_id', auth()->user('agent')->id)->where('user_type', 'AGENT')
        ->searchable(['trx'])->with('gateway')->orderBy('id','desc')->with('currency')
        ->apiQuery();

        $notify[] = 'Deposit data';
        return response()->json([
            'remark'=>'deposits',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'deposits'=>$deposits
            ]
        ]);
    }
 
    public function transactions(Request $request)
    {   
        $transactions = $this->trxLog($request); 
   
        $operations = ['add_money', 'money_in', 'money_out', 'withdraw_money', 'add_balance', 'sub_balance'];
        $times = ['7days', '15days', '1month', '1year'];

        $allCurrency = auth()->user('agent')->wallets; 
        $currencies = [];

        foreach($allCurrency as $currency){
            $currencies[] = $currency->currency_code;
        }

        $notify[] = 'Transactions data';
        return response()->json([
            'remark'=>'transactions',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'operations'=>$operations,
                'times'=>$times,
                'currencies'=>$currencies,
                'transactions'=>$transactions,
            ]
        ]);
    }

    public function submitProfile(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'firstname'=>'required',
            'lastname'=>'required',
            'image' => ['image', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
        ]);
     
        if ($validator->fails()) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }
 
        $user = auth()->user('agent');

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->address = [
            'country'=>@$user->address->country,
            'address'=>$request->address,
            'state'=>$request->state,
            'zip'=>$request->zip,
            'city'=>$request->city,
        ];
      
        if($request->hasFile('image')){ 
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('agentProfile'), getFileSize('agentProfile'), $old);
            } catch (\Exception $exp) {
                return response()->json([
                    'remark'=>'validation_error',
                    'status'=>'error',
                    'message'=>['error'=>['Couldn\'t upload your image']],
                ]);
            }
        }

        $user->save();

        $notify[] = 'Profile updated successfully';
        return response()->json([
            'remark'=>'profile_updated',
            'status'=>'success',
            'message'=>['success'=>$notify],
        ]);
    }

    public function submitPassword(Request $request)
    {   
        $passwordValidation = Password::min(6);
        $general = gs();
        if ($general->secure_password) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => ['required','confirmed',$passwordValidation]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }

        $user = auth()->user('agent');
        if (Hash::check($request->current_password, $user->password)) {
            $password = Hash::make($request->password);
            $user->password = $password;
            $user->save();
            $notify[] = 'Password changed successfully';
            return response()->json([
                'remark'=>'password_changed',
                'status'=>'success',
                'message'=>['success'=>$notify],
            ]);
        } else {
            $notify[] = 'The password doesn\'t match!';
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$notify],
            ]);
        }
    }

    public function qrCode(){  
        $notify[] = 'QR Code';
        $user = auth()->user('agent');
        $qrCode = $user->qrCode;

        if (!$qrCode) {
            $qrCode = new QRcode();
            $qrCode->user_id = $user->id;
            $qrCode->user_type = 'AGENT';
            $qrCode->unique_code = keyGenerator(15);
            $qrCode->save();
        }
        $uniqueCode = $qrCode->unique_code;
        $qrCode = cryptoQR($uniqueCode);
        return response()->json([
            'remark'=>'qr_code',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'qr_code'=>$qrCode,
            ]
        ]);
    }

    public function qrCodeDownload(){

        $user = auth()->user('agent'); 
        $qrCode = $user->qrCode()->first();
        $general = gs();

        $file = cryptoQR($qrCode->unique_code);
        $filename = 'agent_qr_code_'.$user->id . '.jpg';
        $template = Image::make('assets/images/qr_code_template/' . $general->qr_code_template);
        $qrCode = Image::make($file)->opacity(100)->fit(2000, 2000);
        $template->insert($qrCode, 'center');
        $template->encode('jpg');

        $template->save(getFilePath('temporary').'/'.$filename);

        return response()->json([
            'remark'=>'download_qr_code',
            'status'=>'success',
            'data'=>[
                'download_link'=>asset(getFilePath('temporary').'/'.$filename),
                'download_file_name'=>$filename,
            ]
        ]);
      
    }

    public function qrCodeRemove(Request $request){
        $validator = Validator::make($request->all(), [
            'file_name'=>'required'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }
   
        $file = getFilePath('temporary').'/'.$request->file_name;

        if (file_exists($file)) {
            unlink($file);

            return response()->json([
                'remark'=>'qr_code_remove',
                'status'=>'success', 
                'message'=>['success'=>'QR code removed successfully'],
            ]);
        }

        return response()->json([
            'remark'=>'qr_code_remove',
            'status'=>'success', 
            'message'=>['success'=>'Already removed'],
        ]);
    }

    public function wallets(){ 
        $notify[] = "All Wallets";
        $wallets = Wallet::hasCurrency()->where('user_id', auth()->user('agent')->id)->where('user_type', 'AGENT')
            ->with('currency')->orderBy('balance', 'DESC')
        ->get();

        return response()->json([
            'remark'=>'all_wallets',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'wallets'=>$wallets,
            ]
        ]);
    }

    public function commissionLog(){
        $notify[] = "Commission Logs";
        $logs = Transaction::where('user_type', 'AGENT')->where('user_id', auth()->user('agent')->id)->where('remark', 'commission')->with('currency')->apiQuery();

        return response()->json([
            'remark'=>'commission_log',
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'logs'=>$logs,
            ]
        ]);
    }

    public function getDeviceToken(Request $request){

        $validator = Validator::make($request->all(), [
            'token'=> 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }

        $deviceToken = DeviceToken::where('token', $request->token)->where('user_type', 'AGENT')->first();

        if($deviceToken){
            $notify[] = 'Already exists';
            return response()->json([
                'remark'=>'get_device_token',
                'status'=>'success',
                'message'=>['success'=>$notify],
            ]);
        }

        $deviceToken = new DeviceToken();
        $deviceToken->user_id = auth()->user('agent')->id;
        $deviceToken->user_type = 'AGENT';
        $deviceToken->token = $request->token;
        $deviceToken->is_app = 1;
        $deviceToken->save();

        $notify[] = 'Token save successfully';
        return response()->json([
            'remark'=>'get_device_token',
            'status'=>'success',
            'message'=>['success'=>$notify],
        ]);
    }
}

