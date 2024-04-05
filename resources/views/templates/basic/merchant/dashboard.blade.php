@extends($activeTemplate.'layouts.merchant_master')

@section('content')

@if($merchant->kv == 0 || $merchant->kv == 2)
    <div class="d-user-notification d-flex flex-wrap align-items-center mt-4 {{ @$kyc['bgColor'] }}">
        <div class="icon  {{ @$kyc['iconBg'] }}">
            <i class="las la-folder"></i>
        </div>
        <div class="content">
        <p class="text-white fw--bold">@lang(@$kyc['msg'])</p>
        </div>
        <div class="right text-sm-end text-center">
            <a href="{{ @$kyc['route'] }}" class="btn btn-sm btn--base {{ @$kyc['btnBg'] }} ">@lang(@$kyc['btnTxt'])</a>
        </div>
    </div>
@endif 

<div class="push_notice mt-4"></div>

<div class="d-flex justify-content-between mt-4">
    <h6 class="mb-3">@lang('Wallets')</h6>
    <a href="{{route('merchant.wallets')}}" class="font-size--14px text--base">@lang('More Wallets') <i class="las la-long-arrow-alt-right"></i></a>
</div>
<div class="row mb-5 gy-4">
    @foreach ($wallets as $wallet)
        <div class="col-lg-4 col-md-6">
            <div class="d-widget curve--shape">
                <div class="d-widget__content">
                    <i class="las la-wallet"></i>
                    <h2 class="d-widget__amount fw-normal">
                        {{$wallet->currency->currency_symbol}}{{showAmount($wallet->balance,$wallet->currency)}} 
                        {{$wallet->currency->currency_code}}
                    </h2>
                </div>
            </div><!-- d-widget end -->
        </div>
    @endforeach
</div><!-- row end -->


<div class="row mb-3 gy-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-body">
              <div class="d">
                  <h5 class="card-title">@lang('Monthly  Transaction Report')</h5>
              </div>
              <div id="apex-line"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="row align-items-center mb-3 ">
            <div class="col-6">
                <h6 class="fw-normal">@lang('Insights')</h6>
            </div>
            <div class="col-6 text-end">
                <div class="dropdown custom--dropdown has--arrow">
                    <button class="text-btn dropdown-toggle font-size--14px text--base" type="button" id="latestAcvitiesButton" data-bs-toggle="dropdown" aria-expanded="false">
                        @lang('Select')
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="latestAcvitiesButton">
                        <li><a class="dropdown-item money" data-day="7" href="javascript:void(0)">@lang('Last 7 days')</a></li>
                        <li><a class="dropdown-item money" data-day="15" href="javascript:void(0)">@lang('Last 15 days')</a></li>
                        <li><a class="dropdown-item money" data-day="31" href="javascript:void(0)">@lang('Last month')</a></li>
                        <li><a class="dropdown-item money" data-day="365" href="javascript:void(0)">@lang('Last year')</a></li>
                    </ul>
                </div>
            </div>
        </div><!-- row end -->
        <div class="row mb-4">
            <div class="col-sm-6">
                <div class="custom--card mb-4">
                    <div class="card-body"> 
                        <h6 class="mb-4 font-size--16px">@lang('Total Money Received') <small class="text--muted last-time">( @lang('last 7 days') )</small></h6> 
                        <h3 class="title fw-normal money-in">
                            {{$general->cur_sym}}{{showAmount($totalMoneyInOut['totalMoneyIn'],$general->currency)}} {{$general->cur_text}}
                        </h3>
                        <a href="{{ route('merchant.transactions', ['type'=>'plus_trx']) }}" class="text--link text-muted font-size--14px">
                            @lang('Total received')
                        </a>
                        <div class="d-flex flex-wrap align-items-center justify-content-between mt-4">
                            <a href="{{ route('merchant.transactions', ['type'=>'plus_trx']) }}" class="font-size--14px fw-bold">@lang('View Transactions')</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 mt-sm-0 mt-3">
                <div class="custom--card">
                    <div class="card-body">
                        <h6 class="mb-4 font-size--16px">@lang('Total Money out') <small class="text--muted last-time">( @lang('last 7 days') )</small> </h6>
                        <h3 class="title fw-normal money-out">
                            {{$general->cur_sym}}{{showAmount($totalMoneyInOut['totalMoneyOut'],$general->currency)}} {{$general->cur_text}}
                        </h3> 
                        <a href="{{ route('merchant.transactions', ['type'=>'minus_trx']) }}" class="text--link text-muted font-size--14px">
                            @lang('Total spent')
                        </a>
                        <div class="d-flex flex-wrap align-items-center justify-content-between mt-4">
                            <a href="{{ route('merchant.transactions', ['type'=>'minus_trx']) }}" class="font-size--14px fw-bold">@lang('View Transactions')</a>
                        </div>
                    </div>
                </div>
            </div>
            <p class="my-2">@lang('Withdraw')</p>
            <div class="col-md-12">
                <div class="custom--card">
                    <div class="card-body">
                        <h6 class="mb-4 font-size--16px">@lang('Total Withdraw') </h6>
                        <h3 class="title fw-normal">{{$general->cur_sym}}{{showAmount($totalWithdraw,$general->currency)}} {{$general->cur_text}}<sup>*</sup></h3>
                        <div class="d-flex flex-wrap align-items-center justify-content-between mt-4">
                            <a href="{{route('merchant.withdraw.history')}}" class="font-size--14px fw-bold">@lang('View history')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="custom--card">
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-8">
                <h6>@lang('Latest Transactions')</h6>
            </div>
        </div>
        <div class="accordion table--acordion" id="transactionAccordion">
            @forelse ($histories as $history)
                <div class="accordion-item transaction-item {{$history->trx_type == '-' ? 'sent-item':'rcv-item'}}">
                    <h2 class="accordion-header" id="h-{{$loop->iteration}}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-{{$loop->iteration}}" aria-expanded="false" aria-controls="c-1">
                        <div class="col-lg-3 col-sm-4 col-6 order-1 icon-wrapper">
                            <div class="left">
                                <div class="icon">
                                    <i class="las la-long-arrow-alt-right"></i>
                                </div>
                                <div class="content">
                                    <h6 class="trans-title">{{__(ucwords(str_replace('_',' ',$history->remark)))}}</h6>
                                    <span class="text-muted font-size--14px mt-2">{{showDateTime($history->created_at,'M d Y @g:i:a')}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-5 col-12 order-sm-2 order-3 content-wrapper mt-sm-0 mt-3">
                            <p class="text-muted font-size--14px"><b>{{__($history->details)}} {{$history->receiver ? @$history->receiver->username : ''  }}</b></p>
                        </div>
                        <div class="col-lg-3 col-sm-3 col-6 order-sm-3 order-2 text-end amount-wrapper">
                            <p><b>{{showAmount($history->amount,$history->currency)}} {{$history->currency->currency_code}}</b></p>
                        </div>
                    </button>
                    </h2>
                    <div id="c-{{$loop->iteration}}" class="accordion-collapse collapse" aria-labelledby="h-1" data-bs-parent="#transactionAccordion">
                        <div class="accordion-body">
                            <ul class="caption-list">
                                <li>
                                    <span class="caption">@lang('Transaction ID')</span>
                                    <span class="value">{{$history->trx}}</span>
                                </li>
                                <li>
                                    <span class="caption">@lang('Wallet')</span>
                                    <span class="value">{{$history->currency->currency_code}}</span>
                                </li>
                            @if($history->charge > 0)
                                <li>
                                    <span class="caption">@lang('Before Charge')</span>
                                    <span class="value">{{showAmount($history->before_charge,$history->currency)}} {{$history->currency->currency_code}}</span>
                                </li>
                            
                                <li>
                                    <span class="caption">@lang('Charge')</span>
                                    <span class="value">{{ $history->charge_type }}{{showAmount($history->charge,$history->currency)}} {{$history->currency->currency_code}}</span>
                                </li>
                            @endif
                                <li>
                                    <span class="caption">@lang('Transacted Amount')</span>
                                    <span class="value">{{showAmount($history->amount,$history->currency)}} {{$history->currency->currency_code}}</span>
                                </li>
                                <li>
                                    <span class="caption">@lang('Remaining Balance')</span>
                                    <span class="value">{{showAmount($history->post_balance,$history->currency)}} {{$history->currency->currency_code}}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div><!-- transaction-item end -->
                @empty
                <div class="accordion-body text-center">
                   <h4 class="text--muted">@lang('No transaction found')</h4>
                </div>
            @endforelse
        </div>
    </div>
</div><!-- custom--card end -->
<div class="modal fade" id="reasonModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header"><h6>@lang('Reasons')</h6></div>
            <div class="modal-body text-center my-4">
                <p class="reason"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--dark btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
            </div>
        </div>
    </div>
</div>
@endsection


@push('script')
<script src="{{asset('assets/global/js/apexcharts.min.js')}}"></script>
    <script>
        'use strict';

        (function ($) {
            $('.money').on('click', function () {
                var url = '{{ route('merchant.check.insight') }}';
                var day = $(this).data('day');
                var text = $(this).text();
                var data = {
                    day:day
                }
                $.get(url,data,function(response) {
                    if(response.error){
                        notify('error',response.error)
                        return false;
                    }
                    var moneyIn = response.totalMoneyIn;
                    var moneyOut = response.totalMoneyOut;
                    var curSym = '{{$general->cur_sym}}';
                    var curTxt = '{{$general->cur_text}}';

                    $('.money-in').text(curSym+moneyIn.toFixed(2)+' '+curTxt);
                    $('.money-out').text(curSym+moneyOut.toFixed(2)+' '+curTxt);
                    $('.last-time').text('( '+text.toLowerCase()+' )');
                    $('#latestAcvitiesButton').text(text);               
                });
            });

            $('.reason').on('click', function () {
                $('#reasonModal').find('.reason').text($(this).data('reasons'))
                $('#reasonModal').modal('show')
            });  
        })(jQuery);
        
        var options = {
            chart: {
                height: 376,
                type: "area",
                toolbar: {
                show: false
                },
                dropShadow: {
                enabled: true,
                enabledSeries: [0],
                top: -2,
                left: 0,
                blur: 10,
                opacity: 0.08
                },
                animations: {
                enabled: true,
                easing: 'linear',
                dynamicAnimation: {
                    speed: 1000
                }
                },
            },
            dataLabels: {
                enabled: false
            },
            colors: ["#2E93fA"],
            series: [
                {
                name: "Charges",
                data: @json( $report['trx_amount'])
                }
            ],

            fill: {
                type: "gradient",
                gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.9,
                stops: [0, 90, 100]
                }
            },
            tooltip: {
                        y: {
                            formatter: function (val) {
                                return "{{__($general->cur_sym)}}"+ val +" "
                            }
                        }
                    },
            xaxis: {
                categories: @json( $report['trx_dates'])
            },
            grid: {
                padding: {
                left: 5,
                right: 5
                },
                xaxis: {
                
                lines: {
                    show: true
                }
                },   
                yaxis: {
                lines: {
                    show: true
                }
                }, 
            },
        };

        var chart = new ApexCharts(document.querySelector("#apex-line"), options);
        chart.render()
    </script>
@endpush
