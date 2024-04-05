@extends('admin.layouts.app')

@section('panel')
    @if(@json_decode($general->system_info)->version > systemDetails()['version'])
    <div class="row">
        <div class="col-md-12">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">
                    <h3 class="card-title"> @lang('New Version Available') <button class="btn btn--dark float-end">@lang('Version') {{json_decode($general->system_info)->version}}</button> </h3>
                </div>
                <div class="card-body">
                    <h5 class="card-title text-dark">@lang('What is the Update ?')</h5>
                    <p><pre  class="f-size--24">{{json_decode($general->system_info)->details}}</pre></p>
                </div>
            </div>
        </div>
    </div>
    @endif
    @if(@json_decode($general->system_info)->message)
    <div class="row">
        @foreach(json_decode($general->system_info)->message as $msg)
            <div class="col-md-12">
                <div class="alert border border--primary" role="alert">
                    <div class="alert__icon bg--primary"><i class="far fa-bell"></i></div>
                    <p class="alert__message">@php echo $msg; @endphp</p>
                    <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button> 
            </div>
            </div>
        @endforeach
    </div>
    @endif

    <div class="row gy-4">
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.users.all')}}"
                icon="las la-users f-size--56"
                title="Total Users"
                value="{{$widget['total_users']}}"
                bg="primary"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.users.active')}}"
                icon="las la-user-check f-size--56"
                title="Active Users"
                value="{{$widget['verified_users']}}"
                bg="success"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.users.email.unverified')}}"
                icon="lar la-envelope f-size--56"
                title="Email Unverified Users"
                value="{{$widget['email_unverified_users']}}"
                bg="danger"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.users.mobile.unverified')}}"
                icon="las la-comment-slash f-size--56"
                title="Mobile Unverified Users"
                value="{{$widget['mobile_unverified_users']}}"
                bg="red"
            />
        </div><!-- dashboard-w1 end --> 

        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.agents.all')}}"
                icon="las la-user-secret f-size--56"
                title="Total Agents"
                value="{{$widget['total_agents']}}"
                bg="primary"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.agents.active')}}"
                icon="las la-user-check f-size--56"
                title="Active Agents"
                value="{{$widget['verified_agents']}}"
                bg="success"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.agents.email.unverified')}}"
                icon="lar la-envelope f-size--56"
                title="Email Unverified Agents"
                value="{{$widget['email_unverified_agents']}}"
                bg="danger"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.agents.mobile.unverified')}}"
                icon="las la-comment-slash f-size--56"
                title="Mobile Unverified Agents"
                value="{{$widget['mobile_unverified_agents']}}"
                bg="red"
            />
        </div><!-- dashboard-w1 end --> 

        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.merchants.all')}}"
                icon="las la-user-tie f-size--56"
                title="Total Merchants"
                value="{{$widget['total_merchants']}}"
                bg="primary"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.merchants.active')}}"
                icon="las la-user-check f-size--56"
                title="Active Merchants"
                value="{{$widget['verified_merchants']}}"
                bg="success"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.merchants.email.unverified')}}"
                icon="lar la-envelope f-size--56"
                title="Email Unverified Merchants"
                value="{{$widget['email_unverified_merchants']}}"
                bg="danger"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                link="{{route('admin.merchants.mobile.unverified')}}"
                icon="las la-comment-slash f-size--56"
                title="Mobile Unverified Merchants"
                value="{{$widget['mobile_unverified_merchants']}}"
                bg="red"
            />
        </div><!-- dashboard-w1 end --> 
    </div><!-- row end-->

     
    <div class="row gy-4 mt-2">
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.deposit.list')}}"
                icon="fas fa-hand-holding-usd"
                icon_style="false"
                title="Total Deposited"
                value="{{ $general->cur_sym }}{{showAmount($deposit['total_deposit_amount'])}}"
                color="success"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.deposit.pending')}}"
                icon="fas fa-spinner"
                icon_style="false"
                title="Pending Deposits"
                value="{{$deposit['total_deposit_pending']}}"
                color="warning"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.deposit.rejected')}}"
                icon="fas fa-ban"
                icon_style="false"
                title="Rejected Deposits"
                value="{{$deposit['total_deposit_rejected']}}"
                color="warning"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.deposit.list')}}"
                icon="fas fa-percentage"
                icon_style="false"
                title="Deposited Charge"
                value="{{ $general->cur_sym }}{{showAmount($deposit['total_deposit_charge'])}}"
                color="primary"
            />
        </div><!-- dashboard-w1 end -->
    </div><!-- row end-->

    <div class="row gy-4 mt-2">
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.withdraw.log')}}"
                icon="lar la-credit-card"
                title="Total Withdrawan"
                value="{{ $general->cur_sym }}{{showAmount($withdrawals['total_withdraw_amount'])}}"
                color="success"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.withdraw.pending')}}"
                icon="las la-sync"
                title="Pending Withdrawals"
                value="{{$withdrawals['total_withdraw_pending']}}"
                color="warning"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.withdraw.rejected')}}"
                icon="las la-times-circle"
                title="Rejected Withdrawals"
                value="{{$withdrawals['total_withdraw_rejected']}}"
                color="danger"
            />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="2"
                link="{{route('admin.withdraw.log')}}"
                icon="las la-percent"
                title="Withdrawal Charge"
                value="{{ $general->cur_sym }}{{showAmount($withdrawals['total_withdraw_charge'])}}"
                color="primary"
            />
        </div>
    </div><!-- row end-->

    <div class="row gy-4 mt-2">
        <div class="col-xxl-4 col-sm-6">
            <x-widget
                style="3"
                link="{{ route('admin.currency.all') }}"
                icon="las la-coins"
                title="Total Currency"
                value="{{ @$totalCurrency }}"
                color="white"
            />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-4 col-sm-6">
            <x-widget
                style="3"
                link="{{ route('admin.setting.index') }}"
                icon="las la-wallet"
                title="Default Currency"
                value="{{__($general->cur_text)}}"
                color="white"
                bg="1"
            />
        </div><!-- dashboard-w1 end -->

        @php 
            $profit = totalProfit();
        @endphp

        <div class="col-xxl-4 col-sm-6">
            <x-widget
                style="3"
                link="{{ route('admin.profit.all') }}"
                icon="{{ $profit >= 0 ? 'las la-hand-holding-usd' : 'las la-exclamation' }}"
                title="{{ $profit >= 0 ? 'Total Profit' : 'Total Loss' }}"
                value="{{showAmount($profit, checkNegative:true)}}"
                color="white"
                bg="{{ $profit >= 0 ? '19' : 'warning' }}"
            />
        </div><!-- dashboard-w1 end -->
    </div><!-- row end-->

    <div class="row mb-none-30 mt-30">
        <div class="col-xl-12 mb-30">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h5 class="card-title">@lang('Transactions Report') (@lang('Last 30 Days'))</h5>
                    <a href="{{route('admin.trx.detail.graph')}}">@lang('More details')<i class="las la-arrow-right"></i></a>
                </div>
                <div id="apex-line"></div>
              </div>
            </div>
        </div>
        <div class="col-xl-4 mb-30">
            <div class="card h-100">
              <div class="card-body">
                <h5 class="card-title">@lang('Monthly Deposit & Withdraw Report') (@lang('Last 12 Month'))</h5>
                <div id="apex-bar-chart"> </div>
              </div>
            </div>
        </div>
        <div class="col-xl-4 mb-30">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Monthly Charge and Commission History')</h5>
                    <div id="profit-line"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-30">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Monthly Users Registration History')</h5>
                    <div id="reg-line"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-none-30 mt-5">
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card overflow-hidden h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By Browser') (@lang('Last 30 days'))</h5>
                    <canvas id="userBrowserChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By OS') (@lang('Last 30 days'))</h5>
                    <canvas id="userOsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By Country') (@lang('Last 30 days'))</h5>
                    <canvas id="userCountryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    @include('admin.partials.cron_modal')

@endsection

@push('breadcrumb-plugins')
    <button class="btn btn-outline--primary" data-bs-toggle="modal" data-bs-target="#cronModal">
        <i class="las la-server"></i>@lang('Cron Setup')
    </button>
@endpush

@push('script')
<script>
    $(document).ready(function(){        
        "use strict";
        if( @json(@$fiatCron) || @json(@$cryptoCron)){
            $("#cronModal").modal('show');
        }
    });
</script>
@endpush

@include('admin.partials.dashboard_chart')

