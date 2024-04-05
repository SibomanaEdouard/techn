@extends($activeTemplate.'layouts.user_master')

@section('content')
<div class="custom--card"> 
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-10">
                <h6>@lang($pageTitle)</h6>
            </div>
            <div class="col-2 text-end">
                <button class="trans-serach-open-btn"><i class="las la-search"></i></button>
            </div>
        </div>
        <div class="table-responsive--md">
            <form class="transaction-top-form mb-4" action="" method="GET">
                <div class="custom-select-search-box">
                    <input type="text" name="search" class="form--control" value="{{ request()->search }}" placeholder="@lang('Search by transactions')">
                    <button type="submit" class="search-box-btn">
                        <i class="las la-search"></i>
                    </button>
                </div>
            </form>
            <table class="table custom--table">
                <thead> 
                    <tr>
                        <th>@lang('Gateway | Trx')</th>
                        <th>@lang('Initiated')</th>
                        <th>@lang('Amount')</th>
                        <th>@lang('Status')</th>
                        <th>@lang('Details')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $deposit)
                        <tr>
                            <td>
                                <span class="fw-bold"> <span class="text-primary">{{ __($deposit->gateway?->name) }}</span> </span>
                                <br>
                                <small> {{ $deposit->trx }} </small>
                           </td>
                           <td>
                               {{ showDateTime($deposit->created_at) }}<br>{{ diffForHumans($deposit->created_at) }}
                           </td>
                           <td>
                            {{$deposit->currency->currency_symbol}}{{ showAmount($deposit->amount ) }} + <span class="text-danger" title="@lang('charge')">{{ showAmount($deposit->charge)}} </span>
                               <br>
                               <strong title="@lang('Amount with charge')">
                               {{ showAmount($deposit->amount+$deposit->charge) }} {{$deposit->currency->currency_code}}
                               </strong>
                           </td>
                           <td>
                               @php echo $deposit->statusBadge @endphp
                           </td>
                            @php
                                $details = ($deposit->detail != null) ? json_encode($deposit->detail) : null;
                            @endphp
                            <td>
                                <button class="btn btn--dark btn-sm @if($deposit->method_code >= 1000) detailBtn @else disabled @endif"
                                    @if($deposit->method_code >= 1000)
                                        data-info="{{ $details }}"
                                    @endif
                                    @if ($deposit->status == 3)
                                    data-admin_feedback="{{ $deposit->admin_feedback }}"
                                    @endif
                                    >
                                    <i class="fa fa-desktop"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="text-center">
                            <td colspan="100%" class="not-found">{{ __($emptyMessage) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deposits->hasPages())
            <div class="card-footer bg-transparent pt-4 pb-2">
                {{ paginatelinks($deposits) }}
            </div>
        @endif 
    </div>
</div><!-- custom--card end -->

<div id="detailModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Details')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group list-group-flush userData mb-2">
                </ul>
                <div class="feedback"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--dark btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
    <script>
        (function ($) {
            "use strict";
            $('.detailBtn').on('click', function () {
                var modal = $('#detailModal');
         
                var userData = $(this).data('info');
                var html = '';
                
                if(userData){
                    userData.forEach(element => {
                        if(element.type != 'file'){
                            html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${element.name}</span>
                                <span">${element.value}</span>
                            </li>`;
                        }
                    });
                }

                if(!html){
                    html += `
                        <span class="text-center">
                            <span>{{ __($emptyMessage) }}</span>
                        </span>`;
                }

                modal.find('.userData').html(html);
            
                if($(this).data('admin_feedback') != undefined){
                    var adminFeedback = `
                        <div class="ms-3">
                            <strong>@lang('Admin Feedback')</strong>
                            <p>${$(this).data('admin_feedback')}</p>
                        </div>
                    `;
                }else{
                    var adminFeedback = '';
                }

                modal.find('.feedback').html(adminFeedback);


                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
