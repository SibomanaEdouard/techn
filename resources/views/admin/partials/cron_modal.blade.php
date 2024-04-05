<div id="cronModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">@lang('Please Set Cron Job Now')</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row g-3">  
                    <div class="co-md-12">
                        <div class="form-group">
                            <div class="justify-content-between d-flex flex-wrap">
                                <label class="fw-bold">@lang('Cron Command of Fiat Rate')</label>
                                <small class="fst-italic">
                                    @lang('Last Cron Run'): <strong>{{ @$general->cron_run->fiat_cron ? diffForHumans(@$general->cron_run->fiat_cron) : 'N/A' }}</strong>
                                </small>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-lg" id="fiatCron" value="curl -s {{ route('cron.fiat.rate') }}" readonly>
                                <button type="button" class="input-group-text copytext btn--primary copyCronPath border--primary" data-id="fiatCron"> @lang('Copy')</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="justify-content-between d-flex flex-wrap">
                                <label class="fw-bold">@lang('Cron Command of Crypto Rate')</label>
                                <small class="fst-italic">
                                    @lang('Last Cron Run'): <strong>{{ @$general->cron_run->crypto_cron ? diffForHumans(@$general->cron_run->crypto_cron) : 'N/A' }}</strong>
                                </small>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-lg" id="cryptoCron" value="curl -s {{ route('cron.crypto.rate') }}" readonly>
                                <button type="button" class="input-group-text copytext btn--primary copyCronPath border--primary" data-id="cryptoCron"> @lang('Copy')</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-start">
                <p class="fst-italic">
                    @lang('Once per 5-15 minutes is ideal while once every minute is the best option')
                    <u><a href="{{ route('cron.all') }}" type="button" class="text--warning underline">@lang('Run manually')</a></u>
                </p>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    (function($){
        "use strict";

        $(document).on('click', '.copyCronPath', function(){
            var copyText = document.getElementById($(this).data('id'));

            copyText.select();
            copyText.setSelectionRange(0, 99999);
            
            document.execCommand('copy');
            notify('success', 'Copied: '+copyText.value);
        });
        
    })(jQuery)
</script>
@endpush