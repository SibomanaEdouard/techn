<?php

namespace App\Http\Controllers\Api\User;

use App\Models\Form;
use App\Models\Wallet;
use App\Lib\FormProcessor;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use App\Http\Controllers\Api\Common;
use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\QRcode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    use Common;

    public function dashboard()
    {      
        $user = auth()->user();

        $wallets = $user->topTransactedWallets()->with('currency');

        $totalBalance[] = 0;
        foreach ($wallets->get() as $wallet) {
            $totalBalance[] = $wallet->balance * $wallet->currency->rate;
        }

        $wallets = $wallets->take(3)->get();

        $latestTrx = Transaction::where('user_id', $user->id)->where('user_type', 'USER')
            ->with('currency', 'receiverUser', 'receiverAgent', 'receiverMerchant')->orderBy('id', 'desc')->take(10)
        ->get();

        $totalMoneyInOut = $user->moneyInOut();
        $general = GeneralSetting::first();

        $notify[] = 'Dashboard';
        return response()->json([
            'remark'=>'dashboard', 
            'status'=>'success',
            'message'=>['success'=>$notify],
            'data'=>[
                'user'=>$user, 
                'wallets'=>$wallets,
                'latest_trx'=>$latestTrx,
                'last_7_day_money_in_out'=>$totalMoneyInOut,
                'total_site_balance'=>$general->cur_sym.showAmount(array_sum(@$totalBalance), $general->currency)
            ]
        ]);
    }
        
    public function userDataSubmit(Request $request)
    {
        $user = auth()->user();
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
        if (auth()->user()->kv == 2) {
            $notify[] = 'Your KYC is under review';
            return response()->json([
                'remark'=>'under_review',
                'status'=>'error',
                'message'=>['error'=>$notify],
            ]);
        }
        if (auth()->user()->kv == 1) {
            $notify[] = 'You are already KYC verified';
            return response()->json([
                'remark'=>'already_verified',
                'status'=>'error',
                'message'=>['error'=>$notify],
            ]);
        }   
        $form = Form::where('act','user_kyc')->first();
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
        $form = Form::where('act','user_kyc')->first();
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
        $user = auth()->user();
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
        $deposits = auth()->user()->deposits()->searchable(['trx'])->with(['gateway'])->orderBy('id','desc')->with('currency')->apiQuery();

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

        $operations = ['add_money', 'money_out', 'transfer_money', 'request_money', 'make_payment', 'exchange_money', 'create_voucher', 'withdraw', 'balance_add', 'balance_subtract'];

        $times = ['7days', '15days', '1month', '1year'];

        $allCurrency = auth()->user()->wallets;
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
 
        $user = auth()->user();

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
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
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

        $user = auth()->user();
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
        $user = auth()->user();
        $qrCode = $user->createQrCode();
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

        $user = auth()->user();
        $qrCode = $user->qrCode()->first();
        $general = gs();

        $file = cryptoQR($qrCode->unique_code);
        $filename = 'user_qr_code_'.$user->id . '.jpg';
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

    public function qrCodeScan(Request $request){

        $validator = Validator::make($request->all(), [
            'code'=>'required'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }

        $data = QrCode::where('unique_code', $request->code)->first();
        if(!$data){
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>['QR code doesn\'t match']],
            ]);
        }
       
        if($data->user_type == 'USER' && $data->user_id == auth()->user('user')->id){
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>["Can't transact because it's your QR code."]],
            ]);
        }

        return response()->json([
            'remark'=>'qr_code_scan',
            'status'=>'success',
            'message'=>['success'=>['QR code scan']],
            'data'=>[
                'user_type'=>$data->user_type,
                'user_data'=>$data->getUser,
            ]
        ]);

    }

    public function logoutOtherDevices(Request $request){
     
        $validator = Validator::make($request->all(), [
            'password'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>$validator->errors()->all()],
            ]);
        }

        $password = $request->password;

        if (!Hash::check($password, auth()->user()->password)) {
            return response()->json([
                'remark'=>'validation_error',
                'status'=>'error',
                'message'=>['error'=>['The password doesn\'t match!']],
            ]);
        }

        Auth::guard('web')->setUser(auth()->user())->logoutOtherDevices($password);
      
        return response()->json([
            'remark'=>'logout_other_devices',
            'status'=>'success',
            'message'=>['success'=>['Logout from other devices']],
        ]);
    }

    public function wallets(){ 
        $notify[] = "All Wallets";
        $wallets = Wallet::hasCurrency()->where('user_id', auth()->user()->id)->where('user_type', 'USER')
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

        $deviceToken = DeviceToken::where('token', $request->token)->where('user_type', 'USER')->first();

        if($deviceToken){
            $notify[] = 'Already exists';
            return response()->json([
                'remark'=>'get_device_token',
                'status'=>'success',
                'message'=>['success'=>$notify],
            ]);
        }

        $deviceToken = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->user_type = 'USER';
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
