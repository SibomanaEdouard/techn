@extends($activeTemplate . 'layouts.frontend')

@php
    $content = getContent('login.content', true)->data_values;
    $policies = getContent('policy_pages.element', false, null, true);
@endphp

@section('content')
    <section class="pt-100 pb-100 d-flex flex-wrap align-items-center justify-content-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="account-wrapper">
                        <div 
                            class="left bg_img"
                            style="background-image: url('{{ getImage('assets/images/frontend/login/' . @$content->background_image, '768x1200') }}');"
                        >
                        </div>
                        <div class="right">
                            <div class="inner">
                                <div class="text-center">
                                    <h2 class="title">{{ __($pageTitle) }}</h2>
                                    <p class="font-size--14px mt-1 fw-bold">@lang('Start your journey with') {{ __($general->site_name) }}.</p>
                                </div>
                                <form action="{{ route('user.register') }}" method="POST" class="verify-gcaptcha account-form mt-5">
                                    @csrf
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="user-account-check text-center">
                                                <input 
                                                    class="form-check-input exclude" 
                                                    type="radio" value="personal"
                                                    name="accountRadioCheck" 
                                                    id="personalAccount" 
                                                    checked
                                                >
                                                <label class="form-check-label" for="personalAccount">
                                                    <i class="las la-user"></i>
                                                    <span>@lang('Personal Account')</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="user-account-check text-center">
                                                <input 
                                                    class="form-check-input exclude" 
                                                    type="radio" 
                                                    value="company"
                                                    name="accountRadioCheck" 
                                                    id="companyAccount"
                                                >
                                                <label class="form-check-label" for="companyAccount">
                                                    <i class="las la-briefcase"></i>
                                                    <span>@lang('Company Account')</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group company-name d-none">
                                        <label for="company-name">@lang('Legal Name of Company')</label>
                                        <input 
                                            id="company-name" 
                                            type="text" 
                                            class="form--control" 
                                            name="company_name"
                                            placeholder="@lang('Legal name of company')" 
                                            value="{{ old('company_name') }}" 
                                            disabled
                                        >
                                    </div>
                                    @if(session()->has('reference'))
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="referenceBy" class="form-label">@lang('Reference by')</label>
                                                <input 
                                                    type="text" 
                                                    name="referBy" 
                                                    id="referenceBy" 
                                                    class="form-control form--control" 
                                                    value="{{ session()->get('reference') }}" 
                                                    placeholder="@lang('Reference')"
                                                    readonly
                                                >
                                            </div>
                                        </div>
                                    @endif
                                    <div class="form-group">
                                        <label for="username">@lang('Username')</label>
                                        <input 
                                            id="username" 
                                            type="text" 
                                            class="form--control checkUser"
                                            placeholder="@lang('Username')" 
                                            name="username" 
                                            value="{{ old('username') }}"
                                            required
                                        > 
                                        <small class="text-danger usernameExist"></small>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">@lang('E-Mail Address')</label>
                                        <input 
                                            id="email" 
                                            type="email" 
                                            class="form--control checkUser"
                                            placeholder="@lang('Email address')" 
                                            name="email" 
                                            value="{{ old('email') }}"
                                            required
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label for="country">@lang('Country')</label>
                                        <select name="country" id="country" class="form--control">
                                            @foreach ($countries as $key => $country)
                                                <option 
                                                    data-mobile_code="{{ $country->dial_code }}"
                                                    value="{{ $country->country }}" 
                                                    data-code="{{ $key }}"
                                                >
                                                    {{ __($country->country) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="mobile">@lang('Mobile')</label>
                                        <div class="input-group">
                                            <span class="input-group-text mobile-code"></span>
                                            <input type="hidden" name="mobile_code">
                                            <input type="hidden" name="country_code">
                                            <input 
                                                type="number" 
                                                name="mobile" 
                                                id="mobile" 
                                                value="{{ old('mobile') }}"
                                                class="form--control checkUser" 
                                                placeholder="@lang('Your phone number')"
                                            >
                                        </div>
                                        <small class="text-danger mobileExist"></small>
                                    </div>
                                    <div class="form-group">
                                        <label for="password">@lang('Password')</label>
                                        <div class="form-group">
                                            <input 
                                                id="password" 
                                                type="password"
                                                class="form--control"
                                                name="password" 
                                                placeholder="@lang('Enter password')"
                                                required
                                            >
                                            @if ($general->secure_password)
                                                <div class="input-popup">
                                                    <p class="error lower">@lang('1 small letter minimum')</p>
                                                    <p class="error capital">@lang('1 capital letter minimum')</p>
                                                    <p class="error number">@lang('1 number minimum')</p>
                                                    <p class="error special">@lang('1 special character minimum')</p>
                                                    <p class="error minimum">@lang('6 character password')</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="password-confirm">@lang('Confirm Password')</label>
                                        <input 
                                            id="password-confirm" 
                                            type="password" 
                                            class="form--control"
                                            placeholder="@lang('Confirm password')" 
                                            name="password_confirmation" 
                                            required
                                            autocomplete="new-password"
                                        >
                                    </div>

                                    <x-captcha />

                                    @if ($general->agree)
                                        <div class="form-group">
                                            <div class="form-check d-flex align-items-center">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    name="agree"
                                                    id="termsAndConditions"
                                                    required
                                                    @checked(old('agree'))
                                                >
                                                <label class="form-check-label mb-0 ms-2" for="termsAndConditions">
                                                    @lang('I agree with')
                                                    @foreach ($policies as $policy)
                                                        <a href="{{ route('policy.pages', [slug($policy->data_values->title), $policy->id]) }}" target="_blank">
                                                            {{ __($policy->data_values->title) }}
                                                        </a>@if(!$loop->last), @endif
                                                    @endforeach
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="form-group">
                                        <button type="submit" class="btn btn--base w-100">@lang('Register')</button>
                                    </div>
                                </form>
                                <p class="font-size--14px text-center">@lang('Have an account?') 
                                    <a href="{{ route('user.login') }}">@lang('Login Here').</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="modal fade" id="existModalCenter" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <i class="las la-exclamation-circle text-secondary display-2 mb-15"></i>
                    <h6 class="text-center">@lang('You already have an account. Please login')</h6>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn--dark btn-sm" data-bs-dismiss="modal">@lang('Close')</button>
                    <a href="{{ route('user.login') }}" class="btn btn--base btn-sm">@lang('Login')</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@if($general->secure_password)
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@push('style')
<style>
    .country-code .input-group-text{
        background: #fff !important;
    }
    .country-code select{
        border: none;
    }
    .country-code select:focus{
        border: none;
        outline: none;
    }
</style>
@endpush

@push('script')
    <script>
      "use strict";
        (function ($) {

            $('#companyAccount').on('click',function () { 
                $('.company-name').removeClass('d-none')
                $('.company-name').find('input[name=company_name]').removeAttr('disabled').attr('required','required')
                $('.firstname').text('@lang('Representative First Name')')
                $('.lastname').text('@lang('Representative Last Name')')
            });

            $('#personalAccount').on('click',function () { 
                $('.company-name').addClass('d-none')
                $('.company-name').find('input[name=company_name]').attr('disabled',true)
                $('.firstname').text('@lang('First Name')')
                $('.lastname').text('@lang('Last Name')')
            });

            @if($mobileCode)
                $(`option[data-code={{ $mobileCode }}]`).attr('selected','');
            @endif

            $('select[name=country]').change(function(){
                $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                $('.mobile-code').text('+'+$('select[name=country] :selected').data('mobile_code'));
            });

            $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
            $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
            $('.mobile-code').text('+'+$('select[name=country] :selected').data('mobile_code'));
            
            $('.checkUser').on('focusout',function(e){
                var url = '{{ route('user.checkUser') }}';
                var value = $(this).val();
                var token = '{{ csrf_token() }}';
                if ($(this).attr('name') == 'mobile') {
                    var mobile = `${$('.mobile-code').text().substr(1)}${value}`;
                    var data = {mobile:mobile,_token:token}
                }
                if ($(this).attr('name') == 'email') {
                    var data = {email:value,_token:token}
                }
                if ($(this).attr('name') == 'username') {
                    var data = {username:value,_token:token}
                }
                $.post(url,data,function(response) { 
                  if (response.data != false && response.type == 'email') {
                    $('#existModalCenter').modal('show');
                  }else if(response.data != false){
                    $(`.${response.type}Exist`).text(`${response.type} already exist`);
                  }else{
                    $(`.${response.type}Exist`).text('');
                  }
                });
            });

            var old = @json(session()->getOldInput()); 
            if(old.length != 0){
                $("input[name=accountRadioCheck][value=" + old.accountRadioCheck + "]").attr('checked', 'checked');
                $('input[name=username]').val(old.username);
            }

        })(jQuery);
    </script>
@endpush
