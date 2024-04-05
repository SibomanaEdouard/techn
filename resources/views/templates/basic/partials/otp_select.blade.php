<label class="mb-0">@lang('Select OTP Type')</label>
<select class="select style--two currency" name="otp_type" required>
    <option value="" selected>@lang('Select OTP Type')</option>
    @if($general->en)
        <option value="email">@lang('Email')</option>
    @endif
    @if($general->sn)
        <option value="sms">@lang('SMS')</option>
    @endif
    @if (@userGuard()['user']->ts == 1)
        <option value="2fa">@lang('2FA')</option>
    @endif
</select>
 