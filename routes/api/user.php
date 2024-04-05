<?php

use Illuminate\Support\Facades\Route;

Route::namespace('User\Auth')->group(function(){
    Route::post('login', 'LoginController@login');
    Route::post('register', 'RegisterController@register');

    Route::controller('ForgotPasswordController')->group(function(){
        Route::post('password/email', 'sendResetCodeEmail')->name('password.email');
        Route::post('password/verify-code', 'verifyCode')->name('password.verify.code');
        Route::post('password/reset', 'reset')->name('password.update');
    });
});

Route::middleware(['auth:sanctum', 'ability:user'])->group(function () {
    //authorization
    Route::controller('User\AuthorizationController')->group(function(){
        Route::get('authorization', 'authorization')->name('authorization');
        Route::get('resend-verify/{type}', 'sendVerifyCode')->name('send.verify.code');
        Route::post('verify-email', 'emailVerification')->name('verify.email');
        Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile');
        Route::post('verify-g2fa', 'g2faVerification')->name('go2fa.verify');
    });

    Route::middleware(['check.status'])->group(function () {
        Route::post('user-data-submit', 'User\UserController@userDataSubmit')->name('data.submit');

        Route::middleware('registration.complete')->group(function(){
            Route::get('dashboard', 'User\UserController@dashboard')->name('dashboard');
            Route::post('/get/device/token', 'User\UserController@getDeviceToken')->name('get.device.token');

            Route::controller('OtpController')->group(function () {
                Route::post('otp-verify', 'otpVerify')->name('verify.otp.submit');
                Route::post('otp-resend', 'otpResend')->name('verify.otp.resend');
            });

            Route::get('user-info',function(){
                $notify[] = 'User information';
                return response()->json([
                    'remark'=>'user_info',
                    'status'=>'success',
                    'message'=>['success'=>$notify],
                    'data'=>[
                        'user'=>auth()->user()
                    ]
                ]);
            });
  
            Route::namespace('User')->group(function(){
                Route::controller('UserController')->group(function(){
                    //KYC
                    Route::get('kyc-form','kycForm')->name('kyc.form');
                    Route::post('kyc-submit','kycSubmit')->name('kyc.submit');
     
                    //Report
                    Route::any('deposit/history', 'depositHistory')->name('deposit.history');
                    Route::get('transactions','transactions')->name('transactions');
                    
                    Route::post('/qr-code/scan', 'qrCodeScan')->name('qr.code.scan');
                    Route::get('/qr-code', 'qrCode')->name('qr.code');  
                    Route::post('/qr-code/download', 'qrCodeDownload')->name('qr.code.download');
                    Route::post('/qr-code/remove', 'qrCodeRemove')->name('qr.code.remove');

                    Route::post('/logout-other-devices', 'logoutOtherDevices')->name('logout.other.devices');
                    Route::get('/wallets', 'wallets')->name('wallets');
    
                    Route::post('profile-setting', 'submitProfile');
                    Route::post('change-password', 'submitPassword');
                });
    
                //Money out 
                Route::controller('MoneyOutController')->middleware(['module:money_out', 'kyc'])->group(function(){
                    Route::post('/agent/exist', 'checkUser')->name('agent.check.exist');
                    Route::get('/money-out', 'moneyOut')->name('money.out');
                    Route::post('/money-out', 'moneyOutConfirm');
                    Route::post('/money-out-done/{actionId?}', 'moneyOutDone')->name('money.out.done');
                });
    
                //Make payment
                Route::controller('MakePaymentController')->middleware(['module:make_payment', 'kyc'])->group(function(){
                    Route::post('/merchant/exist', 'checkUser')->name('merchant.check.exist');
                    Route::get('/make-payment', 'paymentFrom')->name('payment');
                    Route::post('/make-payment', 'paymentConfirm');
                    Route::post('/make-payment-done/{actionId?}', 'paymentDone')->name('payment.done');
                });
    
                //Transfer money 
                Route::controller('UserOperationController')->middleware(['module:transfer_money', 'kyc'])->group(function(){
                    Route::get('/transfer/money', 'transfer')->name('transfer');
                    Route::post('/transfer/money', 'transferMoney');
                    Route::post('/transfer/money-done/{actionId?}', 'transferMoneyDone')->name('transfer.done');
                    Route::post('/user/exist', 'checkUser')->name('check.exist');
                });
    
                //Request Money
                Route::controller('UserOperationController')->middleware('module:request_money')->group(function(){
                    Route::get('/requests', 'allRequests')->name('requests');
                    Route::get('/my/requested/history', 'requestedHistory')->name('request.money.history');
                    Route::get('/request/money', 'requestMoney')->name('request.money');
                    Route::post('/request/money', 'confirmRequest')->middleware('kyc');
                    Route::post('/accept/request', 'requestAccept')->name('request.accept');
                    Route::post('/accept/reject', 'requestReject')->name('request.reject');
                    Route::any('/accept/done/{actionId?}', 'requestAcceptDone')->name('request.accept.done');
                });
    
                //Voucher
                Route::controller('VoucherController')->middleware('module:create_voucher')->group(function(){
                    Route::get('/voucher/list', 'userVoucherList')->name('voucher.list');
                    Route::get('/create/voucher', 'userVoucher')->name('voucher.create')->middleware('kyc');
                    Route::post('/create/voucher', 'userVoucherCreate')->middleware('kyc');
                    Route::post('/create/voucher-done/{actionId?}', 'userVoucherCreateDone')->name('voucher.create.done')->middleware('kyc');
                    Route::post('/voucher/redeem', 'userVoucherRedeemConfirm');
                    Route::get('/voucher/redeem/log', 'userVoucherRedeemLog')->name('voucher.redeem.log');
                });
    
                //Exchange money
                Route::controller('MoneyExchangeController')->middleware('module:money_exchange')->prefix('exchange')->name('exchange')->group(function(){
                    Route::get('/money', 'exchangeForm')->name('.money');
                    Route::post('/money', 'exchangeConfirm');
                });
    
                //Invoice
                Route::controller('InvoiceController')->middleware('module:create_invoice')->prefix('invoice')->name('invoice')->group(function(){
                    Route::get('/all', 'invoices')->name('.all');
                    Route::get('/create', 'createInvoice')->name('.create');
                    Route::post('/create', 'createInvoiceConfirm');
                    Route::get('/edit/{invoiceNum}', 'editInvoice')->name('.edit');
                    Route::post('/update/', 'updateInvoice')->name('.update');
                    Route::get('/send-to-mail/{id}', 'sendInvoiceToMail')->name('.send.mail');
                    Route::get('/publish/{id}', 'publishInvoice')->name('.publish');
                    Route::get('/discard/{id}', 'discardInvoice')->name('.discard');
                });

                // Withdraw
                Route::controller('WithdrawController')->prefix('withdraw')->name('withdraw')->group(function(){
                    Route::middleware('kyc')->group(function(){
                        Route::get('/methods', 'withdrawMethods')->name('.methods');
                        Route::get('/add-method', 'addWithdrawMethodPage')->name('.method.add.page');
                        Route::post('/add-method', 'addWithdrawMethod')->name('.method.add');
                        Route::get('/edit-method/{id}', 'editWithdrawMethod')->name('.edit');
                        Route::post('/method/update', 'withdrawMethodUpdate')->name('.update');
                        Route::post('/money', 'withdrawMoney')->name('.money');
                        Route::get('/preview/{trx}', 'withdrawPreview')->name('.preview');
                        Route::post('/money/submit', 'withdrawSubmit')->name('.submit');
                        Route::post('/money/done/{actionId?}', 'withdrawSubmitDone')->name('.submit.done');
                    });
                    Route::get('/history', 'withdrawLog')->name('.history');
                });
            });

            // Payment
            Route::controller('PaymentController')->group(function(){
                Route::get('deposit/methods', 'methods')->name('deposit');
                Route::post('deposit/insert', 'depositInsert')->name('deposit.insert');
                Route::get('deposit/confirm', 'depositConfirm')->name('deposit.confirm');
                Route::get('deposit/manual', 'manualDepositConfirm')->name('deposit.manual.confirm');
                Route::post('deposit/manual', 'manualDepositUpdate')->name('deposit.manual.update');
            });
        });
    });

    Route::get('logout', 'User\Auth\LoginController@logout');
});
