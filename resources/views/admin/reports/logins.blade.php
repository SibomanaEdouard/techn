@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>
                                <th>@lang('User')</th>
                                <th>@lang('User Type')</th>
                                <th>@lang('Login at')</th>
                                <th>@lang('IP')</th>
                                <th>@lang('Location')</th>
                                <th>@lang('Browser | OS')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($loginLogs as $log)
                                <tr>
                                    <td>
                                        <span class="fw-bold">
                                            {{ @$log->showUser->fullname }}
                                        </span>
                                        <br>
                                        <span class="small">
                                            @php echo $log->goToUserProfile; @endphp
                                        </span>
                                    </td>
                                    <td>
                                        @php echo $log->userType; @endphp
                                    </td>
                                    <td>
                                        {{showDateTime($log->created_at) }} <br> {{diffForHumans($log->created_at) }}
                                    </td>
                                    <td>
                                        <span class="fw-bold">
                                        <a href="{{route('admin.report.login.ipHistory',[$log->user_ip])}}">{{ $log->user_ip }}</a>
                                        </span>
                                    </td>
                                    <td>{{ __($log->city) }} <br> {{ __($log->country) }}</td>
                                    <td>
                                        {{ __($log->browser) }} <br> {{ __($log->os) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                </tr>
                            @endforelse

                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($loginLogs->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($loginLogs) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    @if(request()->routeIs('admin.report.login.history')) 
        <div class="profit-breadcrumb">
            <form action="" class="row g-2 form">
                <div class="col-lg-4 col-md-4 col-12">
                    <x-select-user-type strUpper="{{ false }}" title="User Type" />
                </div>
                <div class="col-lg-8 col-md-8">
                    <x-search-key-field placeholder="Enter Username" />
                </div> 
            </form>
        </div>
    @endif
@endpush

@if(request()->routeIs('admin.report.login.ipHistory'))
    @push('breadcrumb-plugins')
        <a href="https://www.ip2location.com/{{ $ip }}" target="_blank" class="btn btn--primary">@lang('Lookup IP') {{ $ip }}</a>
    @endpush
@endif


@push('script')
  <script>
    (function($){
        "use strict";
        $('select[name=user_type]').on('change', function(){
            $('.form').submit();
        });
    })(jQuery)
  </script>
@endpush
 