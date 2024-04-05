@extends($activeTemplate.'layouts.merchant_master')

@section('content')
<div class="row mt-5 justify-content-center">
    <div class="col-xl-10">
        <div class="card box-shadow">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2 align-items-center">
                <h6>@lang('Open New Ticket')</h6>
                <a href="{{ route('ticket') }}" class="btn btn-sm btn--base text-end">
                    <i class="las la-backward"></i>
                    @lang('Support Tickets')
                </a>
            </div> 

            <div class="card-body"> 
                <form action="{{ route('ticket.store') }}" method="post" enctype="multipart/form-data"
                    onsubmit="return submitUserForm();">
                    @csrf
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">@lang('Name')</label>
                            <input type="text" name="name" value="{{ @$user->firstname . ' ' . @$user->lastname }}"
                                class="form--control " placeholder="@lang('Enter your name')" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">@lang('Email address')</label>
                            <input type="email" name="email" value="{{ @$user->email }}" class="form--control"
                                placeholder="@lang('Enter your email')" readonly>
                        </div>

                        <div class="form-group col-md-6">
                            <label>@lang('Subject')</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" class="form--control"
                                placeholder="@lang('Subject')">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="priority">@lang('Priority')</label>
                            <select name="priority" class="form--control">
                                <option value="3">@lang('High')</option>
                                <option value="2">@lang('Medium')</option>
                                <option value="1">@lang('Low')</option>
                            </select>
                        </div>
                        <div class="col-12 form-group">
                            <label for="inputMessage">@lang('Message')</label>
                            <textarea name="message" id="inputMessage" rows="6" class="form--control" placeholder="@lang('Message')">{{ old('message') }}</textarea>
                        </div>
                    </div>

                    <div class="row form-group ">
                        <div class="col-lg-12 file-upload">
                            <label class="form-label">@lang('Attachments')</label> 
                            <small class="text--danger">@lang('Max 5 files can be uploaded'). @lang('Maximum upload size is') {{ ini_get('upload_max_filesize') }}</small>
                            <div class="input-group">
                                <input type="file" name="attachments[]" id="inputAttachments" class="form-control form-control-lg rounded"/>
                                <button type="button" class="input-group-text btn--success addFile rounded ms-2">
                                    <i class="las la-plus"></i>
                                </button>
                            </div>
                            <div id="fileUploadsContainer"></div>
                            <p class="ticket-attachments-message text-muted">
                                @lang('Allowed File Extensions'): .@lang('jpg'), .@lang('jpeg'), .@lang('png'), .@lang('pdf'), .@lang('doc'), .@lang('docx')
                            </p>
                        </div>
                    </div>
                    <div class="row form-group justify-content-center">
                        <div class="col-md-12">
                            <button class="btn btn--base w-100" type="submit" id="recaptcha">@lang('Submit')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <style>
        .input-group-text:focus {
            box-shadow: none !important;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            var fileAdded = 0;
            $('.addFile').on('click', function() {
                if (fileAdded >= 4) {
                    notify('error', 'You\'ve added maximum number of file');
                    return false;
                }
                fileAdded++;
                $("#fileUploadsContainer").append(`
                    <div class="input-group my-3">
                        <input type="file" name="attachments[]" class="form-control form-control-lg rounded" required />
                     <button-lg class="input-group-text btn--danger remove-btn rounded ms-2"><i class="las la-times"></i></button-lg>
                    </div>
                `)
            });
            $(document).on('click', '.remove-btn', function() {
                fileAdded--;
                $(this).closest('.input-group').remove();
            });
        })(jQuery);
    </script>
@endpush