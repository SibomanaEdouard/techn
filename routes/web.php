<?php

use Illuminate\Support\Facades\Route;

Route::get('/clear', function(){
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});

Route::controller('CronController')->prefix('cron')->name('cron.')->group(function () {
    Route::get('fiat-rate', 'fiatRate')->name('fiat.rate');
    Route::get('crypto-rate', 'cryptoRate')->name('crypto.rate');
    Route::get('all', 'all')->name('all');
}); 

// User Support Ticket
Route::controller('TicketController')->prefix('ticket')->group(function () {
    Route::get('/', 'supportTicket')->name('ticket');
    Route::get('/new', 'openSupportTicket')->name('ticket.open');
    Route::post('/create', 'storeSupportTicket')->name('ticket.store');
    Route::get('/view/{ticket}', 'viewTicket')->name('ticket.view');
    Route::post('/reply/{ticket}', 'replyTicket')->name('ticket.reply');
    Route::post('/close/{ticket}', 'closeTicket')->name('ticket.close');
    Route::get('/download/{ticket}', 'ticketDownload')->name('ticket.download'); 
});

Route::controller('OtpController')->group(function () {
    Route::get('otp-verification', 'otpVerification')->name('verify.otp');
    Route::get('otp-resend', 'otpResend')->name('verify.otp.resend');
    Route::post('otp-verify', 'otpVerify')->name('verify.otp.submit');
});

Route::get('app/deposit/confirm/{hash}', 'Gateway\PaymentController@appDepositConfirm')->name('deposit.app.confirm');

Route::controller('SiteController')->group(function () {

    Route::post('/push/device/token', 'pushDeviceToken')->name('push.device.token');
    Route::get('/session/status', 'sessionStatus')->name('session.status');
    Route::get('/login', 'login')->name('login'); 

    Route::get('/invoice/payment/{invoiceNum}', 'invoicePayment')->name('invoice.payment');
    Route::get('/api/documentation', 'apiDocumentation')->name('api.documentation');

    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');

    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');

    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');
    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');

    Route::get('/announces', 'blogs')->name('blogs');
    Route::get('/blog/{slug}/{id}', 'blogDetails')->name('blog.details');

    Route::post('subscribe', 'subscribe')->name('subscribe');
    Route::get('policy/{slug}/{id}', 'policyPages')->name('policy.pages');
    Route::get('placeholder-image/{size}', 'placeholderImage')->name('placeholder.image');

    Route::get('qr/scan/{uniqueCode}','SiteController@qrScan')->name('qr.scan');
    Route::get('/{slug}', 'pages')->name('pages');

    Route::get('/', 'index')->name('home');
});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ****************************** PAYMENT GATEWAY API ******************************
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//Live payment
Route::controller('GetPaymentController')->group(function () {
    Route::match(['get','post'],'/payment/initiate', 'initiatePayment')->name('initiate.payment');
    Route::get('initiate/payment/checkout', 'initiatePaymentAuthView')->name('initiate.payment.auth.view');
    Route::post('initiate/payment/check-mail', 'checkEmail')->name('payment.check.email');
    Route::get('verify/payment', 'verifyPayment')->name('payment.verify');
    Route::post('confirm/payment', 'verifyPaymentConfirm')->name('confirm.payment');
    Route::get('resend/verify/code', 'sendVerifyCode')->name('resend.code');
    Route::get('cancel/payment', 'cancelPayment')->name('cancel.payment');
});

//Test payment
Route::controller('TestPaymentController')->prefix('sandbox')->name('test.')->group(function () {
    Route::match(['get','post'],'/payment/initiate', 'initiatePayment')->name('initiate.payment');
    Route::get('initiate/payment/checkout', 'initiatePaymentAuthView')->name('initiate.payment.auth.view');
    Route::post('initiate/payment/check-mail', 'checkEmail')->name('payment.check.email');
    Route::get('verify/payment', 'verifyPayment')->name('payment.verify');
    Route::post('confirm/payment', 'verifyPaymentConfirm')->name('confirm.payment');
    Route::get('cancel/payment', 'cancelPayment')->name('cancel.payment');
});
