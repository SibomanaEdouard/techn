@extends($activeTemplate . 'layouts.' . strtolower(userGuard()['type']) . '_master')
@php
    $class = '';
    if (userGuard()['type'] == 'AGENT' || userGuard()['type'] == 'MERCHANT') {
        $class = 'row justify-content-center mt-5';
    }
@endphp
@section('content')
    <div class="{{ $class }}">
        <div class="col-xl-10">
            <div class="card style--two">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                    <div class="bank-icon  me-2">
                        <i class="las la-university"></i>
                    </div>
                    <h4 class="fw-normal">{{ __($pageTitle) }}</h4>
                </div>
                <div class="card-body p-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <form action="{{ route(strtolower(userGuard()['type']) . '.deposit.manual.update') }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <p class="text-center mt-2">@lang('You have requested') <b
                                                class="text-success">{{ showAmount($data['amount']) }}
                                                {{ __($general->cur_text) }}</b> , @lang('Please pay')
                                            <b class="text-success">{{ showAmount($data['final_amo']) . ' ' . $data['method_currency'] }}
                                            </b> @lang('for successful payment')
                                        </p>
                                        <h4 class="text-center mb-4">@lang('Please follow the instruction below')</h4>

                                        <p class="my-4 text-center">@php echo  $data->gateway->description @endphp</p>

                                    </div>

                                    <x-viser-form identifier="id" identifierValue="{{ $gateway->form_id }}" />

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn--base w-100">@lang('Pay Now')</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
