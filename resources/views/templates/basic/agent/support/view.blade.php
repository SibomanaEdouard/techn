@extends($activeTemplate.'layouts.'.$layout)

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card box-shadow">
                    <div class="card-header card-header-bg d-flex flex-wrap justify-content-between align-items-center">
                        <div class="button-title-badge d-flex flex-wrap align-items-center gap-2"> 
                            <div class="button-badge d-flex flex-wrap align-items-center justify-content-between"> 
                                @php echo $myTicket->statusBadge; @endphp
                                <div class="d-block d-sm-none">
                                    @if ($myTicket->status != 3 && $myTicket->agent)
                                    <button class="btn btn-danger close-button btn-sm confirmationBtn" 
                                        type="button"
                                        data-question="@lang('Are you sure to close this ticket?')" 
                                        data-action="{{ route('ticket.close', $myTicket->id) }}">
                                        <i class="las la-times"></i>
                                    </button>
                                @endif
                                </div>
                            </div>
                            <h5 class="card-title mt-0 mb-0">
                                [@lang('Ticket')#{{ $myTicket->ticket }}] {{ $myTicket->subject }}
                            </h5> 
                        </div>   
                        <div class="d-sm-block d-none"> 
                            @if ($myTicket->status != 3 && $myTicket->agent)
                                <button class="btn btn-danger close-button btn-sm confirmationBtn" 
                                    type="button"
                                    data-question="@lang('Are you sure to close this ticket?')" 
                                    data-action="{{ route('ticket.close', $myTicket->id) }}">
                                    <i class="las la-times"></i>
                                </button>
                            @endif
                        </div>                     
                    </div>
                    <div class="card-body">
                        @if ($myTicket->status != 4)
                            <form method="post" action="{{ route('ticket.reply', $myTicket->id) }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="replayTicket" value="1">
                                <div class="row justify-content-between">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <textarea name="message" class="form-control form-control-lg" id="inputMessage" placeholder="@lang('Your Reply')"
                                                rows="4" cols="10"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row justify-content-between">
                                    <div class="col-md-9">
                                        <div class="row justify-content-between">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label">@lang('Attachments')</label> 
                                                    <small class="text-danger">
                                                        @lang('Max 5 files can be uploaded'). @lang('Maximum upload size is') {{ ini_get('upload_max_filesize') }}
                                                    </small>
                                                    <div class="input-group">
                                                        <input type="file" name="attachments[]" id="inputAttachments" class="form-control form-control-lg rounded"/>
                                                        <button type="button" class="input-group-text btn--success addFile rounded ms-2">
                                                            <i class="las la-plus"></i>
                                                        </button>
                                                    </div>
                                                    <div id="fileUploadsContainer"></div>
                                                    <p class="my-2 ticket-attachments-message text-muted">
                                                        @lang('Allowed File Extensions'): .@lang('jpg'), .@lang('jpeg'), .@lang('png'), .@lang('pdf'), .@lang('doc'), .@lang('docx')
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mt-lg-0 mt-md-4">
                                        <label for="inputAttachments" class="d-lg-block d-sm-none d-md-block d-none">&nbsp;</label>
                                        <button type="submit" class="btn btn--base custom-success w-100">@lang('Reply')</button>
                                    </div>
                                </div>
                            </form>
                        @endif
                        <div class="row">
                            <div class="col-md-12 mt-4 mt-lg-0 mt-md-0">
                                <div class="card">
                                    <div class="card-body">
                                        @foreach ($messages as $message)
                                            @if ($message->admin_id == 0)
                                                <div class="row border border-primary border-radius-3 my-3 py-3 mx-2">
                                                    <div class="col-md-3 border-right text-right">
                                                        <h5 class="my-3">{{ $message->ticket->name }}</h5>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <p class="text-muted font-weight-bold my-3">
                                                            @lang('Posted on')
                                                            {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                                        <p>{{ $message->message }}</p>
                                                        @if ($message->attachments()->count() > 0)
                                                            <div class="mt-2">
                                                                @foreach ($message->attachments as $k => $image)
                                                                    <a href="{{ route('ticket.download', encrypt($image->id)) }}"
                                                                        class="mr-3"
                                                                    >
                                                                        <i class="fa fa-file"></i>
                                                                        @lang('Attachment') {{ ++$k }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <div class="row border border-warning border-radius-3 my-3 py-3 mx-2"
                                                    style="background-color: #ffd96729">
                                                    <div class="col-md-3 border-right text-right">
                                                        <h5 class="my-3">{{ $message->admin->name }}</h5>
                                                        <p class="lead text-muted">@lang('Staff')</p>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <p class="text-muted font-weight-bold my-3">
                                                            @lang('Posted on')
                                                            {{ $message->created_at->format('l, dS F Y @ H:i') }}</p>
                                                        <p>{{ $message->message }}</p>
                                                        @if ($message->attachments()->count() > 0)
                                                            <div class="mt-2">
                                                                @foreach ($message->attachments as $k => $image)
                                                                    <a href="{{ route('ticket.download', encrypt($image->id)) }}"
                                                                        class="mr-3"><i class="fa fa-file"></i>
                                                                        @lang('Attachment') {{ ++$k }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-user-confirmation-modal></x-user-confirmation-modal>
@endsection

@push('style')
    <style>
        .input-group-text:focus {
            box-shadow: none !important;
        }
        @media (max-width: 575px) {
            .button-badge {
                   width: 100%;  
            }
        }
        @media (max-width: 575px) {
            .button-title-badge {
                   width: 100%;  
            }
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
                        <button class="input-group-text btn--danger remove-btn rounded ms-2"><i class="las la-times"></i></button>
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
