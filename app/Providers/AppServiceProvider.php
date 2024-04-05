<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\Agent;
use App\Models\Deposit;
use App\Models\Frontend;
use App\Models\Language;
use App\Models\Merchant;
use App\Models\ModuleSetting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(){

        $general = gs();
        $activeTemplate = activeTemplate();
        $viewShare['general'] = $general;
        $viewShare['activeTemplate'] = $activeTemplate;
        $viewShare['activeTemplateTrue'] = activeTemplate(true);
        $viewShare['language'] = Language::all();
        $viewShare['emptyMessage'] = 'Data not found';
        $viewShare['module'] = ModuleSetting::get();

        view()->share($viewShare);

        view()->composer('admin.partials.sidenav', function ($view) {
            $view->with([
                'bannedUsersCount'           => User::banned()->count(),
                'emailUnverifiedUsersCount' => User::emailUnverified()->count(),
                'mobileUnverifiedUsersCount'   => User::mobileUnverified()->count(),
                'kycUnverifiedUsersCount'   => User::kycUnverified()->count(),
                'kycPendingUsersCount'   => User::kycPending()->count(), 

                'bannedAgentsCount'           => Agent::banned()->count(),
                'emailUnverifiedAgentsCount' => Agent::emailUnverified()->count(),
                'mobileUnverifiedAgentsCount'   => Agent::mobileUnverified()->count(),
                'kycUnverifiedAgentsCount'   => Agent::kycUnverified()->count(), 
                'kycPendingAgentsCount'   => Agent::kycPending()->count(),

                'bannedMerchantsCount'           => Merchant::banned()->count(),
                'emailUnverifiedMerchantsCount' => Merchant::emailUnverified()->count(),
                'mobileUnverifiedMerchantsCount'   => Merchant::mobileUnverified()->count(),
                'kycUnverifiedMerchantsCount'   => Merchant::kycUnverified()->count(), 
                'kycPendingMerchantsCount'   => Merchant::kycPending()->count(),

                'pendingTicketCount'         => SupportTicket::whereIN('status', [0,2])->count(),
                'pendingDepositsCount'    => Deposit::pending()->count(),
                'pendingWithdrawCount'    => Withdrawal::pending()->count(),
            ]);
        });

        view()->composer('admin.partials.topnav', function ($view) {
            $view->with([
                'adminNotifications'=>AdminNotification::where('is_read',0)->with('user', 'agent', 'merchant')->orderBy('id','desc')->take(10)->get(),
                'adminNotificationCount'=>AdminNotification::where('is_read',0)->count(),
            ]);
        });

        view()->composer('partials.seo', function ($view) {
            $seo = Frontend::where('data_keys', 'seo.data')->first();
            $view->with([
                'seo' => $seo ? $seo->data_values : $seo,
            ]);
        });
 
        if ($general->force_ssl) { 
            \URL::forceScheme('https');
        }


        Paginator::useBootstrapFour();
    }
}
