@extends($activeTemplate .'layouts.common_auth')

@php
    $content = getContent('agent_login.content',true)->data_values;
@endphp

@section('content')
<section class="verifiction-page account-section">
  <div class="left"> 
      <div class="left-inner w-100">
        <div class="text-center mb-5">
            <a class="site-logo" href="{{route('home')}}"><img src="{{getImage(getFilePath('logoIcon') .'/dark_logo.png')}}" alt="logo"></a>
        </div>
        <div class="d-flex justify-content-center">
            <div class="verification-code-wrapper">
                <div class="verification-area">
                    <h5 class="pb-3 text-center border-bottom">@lang('Verify Mobile Number')</h5>
                    <form action="{{route('agent.verify.mobile')}}" method="POST" class="submit-form">
                        @csrf
                        <p class="verification-text mt-3">
                          @lang('A 6 digit verification code sent to your mobile number') :  +{{ showMobileNumber(agent()->mobile) }}
                        </p>

                        @include($activeTemplate.'partials.verification_code')

                        <div class="mb-3">
                            <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                        </div>

                        <div class="mb-3">
                            <p>
                                @lang('If you don\'t get any code'), <a href="{{route('agent.send.verify.code', 'phone')}}"> @lang('Try again')</a>
                            </p>
                            @if($errors->has('resend'))
                                <small class="text--danger d-block">{{ $errors->first('resend') }}</small>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
      </div>
  </div>
  <div class="right bg_img" style="background-image: url('{{getImage('assets/images/frontend/agent_login/'.@$content->background_image,'1920x1280')}}');">
  </div>
</section>
@endsection
