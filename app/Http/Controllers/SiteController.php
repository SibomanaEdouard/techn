<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Agent;
use App\Models\Currency;
use App\Models\DeviceToken;
use App\Models\Frontend;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\Merchant;
use App\Models\Page;
use App\Models\Plugin;
use App\Models\QRcode;
use App\Models\Subscriber;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Validator;

class SiteController extends Controller{

    public function index()
    {
        $reference = @$_GET['reference'];

        if ($reference) {
            session()->put('reference', $reference);
        }

        $pageTitle = 'Home';
        $sections = Page::where('tempname', $this->activeTemplate)->where('slug', '/')->first();
        return view($this->activeTemplate . 'home', compact('pageTitle', 'sections'));
    }

    public function pages($slug)
    {
        $page = Page::where('tempname', $this->activeTemplate)->where('slug', $slug)->firstOrFail();
        $pageTitle = $page->name;
        $sections = $page->secs;
        return view($this->activeTemplate . 'pages', compact('pageTitle', 'sections'));
    }


    public function contact()
    {
        $pageTitle = "Contact Us";
        $sections = Page::where('tempname', $this->activeTemplate)->where('slug', 'contact')->first();
        return view($this->activeTemplate . 'contact', compact('pageTitle', 'sections'));
    }


    public function contactSubmit(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        $request->session()->regenerateToken();
        $random = getNumber();
        $user = userGuard();

        $ticket = new SupportTicket();
        $ticket->user_id = $user['user'] ? $user['user']->id : 0;
        $ticket->user_type = $user['user'] ? strtoupper($user['type']) : null;
        $ticket->name = $request->name;
        $ticket->email = $request->email;
        $ticket->priority = 2;

        $ticket->ticket = $random;
        $ticket->subject = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = 0;
        $ticket->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user['user'] ? $user['user']->id : 0;
        $adminNotification->title = 'A new support ticket has opened ';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message = $request->message;
        $message->save();

        $notify[] = ['success', 'Ticket created successfully!'];

        return to_route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function policyPages($slug, $id)
    {
        $policy = Frontend::where('id', $id)->where('data_keys', 'policy_pages.element')->firstOrFail();
        $pageTitle = $policy->data_values->title;
        return view($this->activeTemplate . 'policy', compact('policy', 'pageTitle'));
    }

    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        if (!$language) $lang = 'en';
        session()->put('lang', $lang);
        return back();
    }

    public function blogDetails($slug, $id)
    {
        $blog = Frontend::where('id', $id)->where('data_keys', 'blog.element')->firstOrFail();
        $recentBlogs = Frontend::where('id', '!=', $id)->where('data_keys', 'blog.element')->latest()->take(15)->get();
        $pageTitle = 'Blog Details';
        return view($this->activeTemplate . 'blog_details', compact('blog', 'pageTitle', 'recentBlogs'));
    }


    public function cookieAccept()
    {
        $general = gs();
        Cookie::queue('gdpr_cookie', $general->site_name, 43200);
    }

    public function cookiePolicy()
    {
        $pageTitle = 'Cookie Policy';
        $cookie = Frontend::where('data_keys', 'cookie.data')->first();
        return view($this->activeTemplate . 'cookie', compact('pageTitle', 'cookie'));
    }

    public function placeholderImage($size = null)
    {
        $imgWidth = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text = $imgWidth . '×' . $imgHeight;
        $fontFile = realpath('assets/font/RobotoMono-Regular.ttf');
        $fontSize = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 175, 175, 175);
        imagefill($image, 0, 0, $bgFill);
        $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance()
    {
        $pageTitle = 'Maintenance Mode';
        $general = gs();
        if ($general->maintenance_mode == 0) {
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->first();
        return view($this->activeTemplate . 'maintenance', compact('pageTitle', 'maintenance'));
    }

    public function blogs()
    {
        $pageTitle = 'Announces';
        $blogs = Frontend::where('data_keys', 'blog.element')->latest()->paginate(getPaginate());
        $sections = Page::where('tempname', $this->activeTemplate)->where('slug', 'blogs')->first();
        return view($this->activeTemplate . 'blogs', compact('blogs', 'pageTitle', 'sections'));
    }

    public function apiDocumentation()
    {
        $pageTitle = 'Developer - Api Documentation';
        $allCurrency = Currency::enable()->get();
        $plugins = Plugin::where('status',1)->get();
        return view($this->activeTemplate . 'api_documentation', compact('pageTitle', 'allCurrency','plugins'));
    }

    public function qrScan($uniqueCode){

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT, GET, POST");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        $qrCode = QRcode::where('unique_code', $uniqueCode)->first();

        if (!$qrCode) {
            return response()->json(['error' => 'Not found']);
        }

        if ($qrCode->user_type == 'USER') {
            $user = User::find($qrCode->user_id);
        }
        elseif($qrCode->user_type == 'AGENT') {
            $user = Agent::find($qrCode->user_id);
        }
        else{
            $user = Merchant::find($qrCode->user_id);
        }

        if (!$user) {
            return response()->json(['error' => 'Not found']);
        }

        return $user->username;
    }

    public function invoicePayment($invoiceNum)
    {
        try {
            $invNum = decrypt($invoiceNum);
        } catch (\Throwable $th) {
            $notify[] = ['error', 'Invalid invoice number'];
            return back()->withNotify($notify);
        }

        $pageTitle = "Invoice-#$invNum";
        $invoice = Invoice::where('invoice_num', $invNum)->first();

        if (!$invoice) {
            $notify[] = ['error', 'Invoice not found'];
            return to_route('home')->withNotify($notify);
        }

        if ($invoice->status == 0) {
            $notify[] = ['error', 'Sorry! Invoice not published'];
            return to_route('home')->withNotify($notify);
        }
        return view($this->activeTemplate . 'invoice', compact('invoice', 'invoiceNum', 'pageTitle'));
    }

    public function pushDeviceToken(Request $request){

        $validator = Validator::make($request->all(), [
            'token'=> 'required'
        ]);

        if($validator->fails()){
            return ['success'=>false, 'errors'=>$validator->errors()->all()];
        }

        $getGuard = @userGuard();
        $user = @$getGuard['user'];
        $userType = @$getGuard['type'];

        $deviceToken = DeviceToken::where('token', $request->token)->where('user_type', $userType)->first();

        if($deviceToken){
            return ['success'=>true, 'message'=>'Already exists'];
        }

        $deviceToken = new DeviceToken();
        $deviceToken->user_id = $user->id;
        $deviceToken->user_type = $userType;
        $deviceToken->token = $request->token;
        $deviceToken->is_app = 0;
        $deviceToken->save();

        return ['success'=>true, 'message'=>'Token save successfully'];
    }

    public function sessionStatus(Request $request){

        if($request->reload){
            return ['status'=>true];
        }

        $user = @userGuard()['type'];

        if($user == $request->userType){
            return ['status'=>200];
        }

        return ['status'=>404];
    }

    public function login(){
        return back();
    }

    public function subscribe(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:250|unique:subscribers,email'
        ]);

        if(!$validator->passes()) {
            return response()->json(['error'=>$validator->errors()->all()]);
        }

        $newSubscriber = new Subscriber();
        $newSubscriber->email = $request->email;
        $newSubscriber->save();

        return response()->json(['success'=>true, 'message'=>'Thank you, We\'ll notice you our latest news']);
    }

}
