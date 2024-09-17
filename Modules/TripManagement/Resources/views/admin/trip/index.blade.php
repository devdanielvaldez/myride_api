@extends('adminmodule::layouts.master')

@section('title', translate('Trips'))

@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h4 class="text-capitalize mb-4 ">{{ translate('trip_list')}}</h4>

            <div class="row mb-4">
                @include('tripmanagement::admin.trip.partials._trip-inline-menu')
            </div>
            @if($type == 'all')
                <div class="row mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                                <h5 class="text-primary fw-medium text-uppercase mb-3">{{translate('trips_statistics')}}</h5>

                                <div class="dropdown custom-date-dropdown">
                                    <select name="date_range" id="date-range"
                                            class="js-select btn btn-outline-primary form-control">
                                        <option value="{{ALL_TIME}}" class="text-primary" selected>{{translate(ALL_TIME)}}</option>
                                        <option value="{{TODAY}}">{{translate(TODAY)}}</option>
                                        <option value="{{PREVIOUS_DAY}}">{{translate(PREVIOUS_DAY)}}</option>
                                        <option value="{{LAST_7_DAYS}}">{{translate(LAST_7_DAYS)}}</option>
                                        <option value="{{THIS_WEEK}}">{{translate(THIS_WEEK)}}</option>
                                        <option value="{{LAST_WEEK}}">{{translate(LAST_WEEK)}}</option>
                                        <option value="{{THIS_MONTH}}">{{translate(THIS_MONTH)}}</option>
                                        <option value="{{LAST_MONTH}}">{{translate(LAST_MONTH)}}</option>
                                    </select>
                                </div>
                                <div id="data-input" class="d-none">
                                    <input class="btn btn-outline-primary show-calender me-3" id="start_date" type="date">
                                    <input onchange="getDate()" class="btn btn-outline-primary show-calender" id="end_date"
                                           type="date">
                                </div>
                            </div>
                            <div id="trip-stats">
                                @include('tripmanagement::admin.trip.partials._trip-list-stat')
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row g-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                                <h5 class="text-primary fw-medium text-uppercase mb-3">{{translate('all_trips')}}</h5>

                                <div class="d-flex align-items-center gap-2 text-capitalize">
                                    <span class="text-muted">{{translate('total_trips')}} : </span>
                                    <span class="text-primary fs-16 fw-bold" id="">{{$trips->total()}}</span>
                                </div>
                            </div>
                            <div class="table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{url()->current()}}" class="search-form search-form_style-two">
                                    <div class="input-group search-form__input_group">
                                            <span class="search-form__icon">
                                                <i class="bi bi-search"></i>
                                            </span>
                                        <input type="search" name="search" value="{{request()->search}}"
                                               class="theme-input-style search-form__input"
                                               placeholder="{{translate('Search_here_by_Trip_ID')}}">
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{translate('search')}}</button>
                                </form>

                                <div class="d-flex flex-wrap gap-3">
                                    @can('super-admin')
                                        <a href="{{ route('admin.trip.index', ['type' => request('type')]) }}"
                                           class="btn btn-outline-primary px-3" data-bs-toggle="tooltip"
                                           data-bs-title="{{ translate('refresh') }}">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </a>
                                    @endcan
                                    @can('trip_log')
                                        <a href="{{route('admin.trip.log')}}" class="btn btn-outline-primary px-3"
                                           data-bs-toggle="tooltip" data-bs-title="{{ translate('view_Log') }}">
                                            <i class="bi bi-clock-fill"></i>
                                        </a>
                                    @endcan

                                    @can('trip_export')
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-outline-primary"
                                                    data-bs-toggle="dropdown">
                                                <i class="bi bi-download"></i>
                                                {{translate('download')}}
                                                <i class="bi bi-caret-down-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                                <li><a class="dropdown-item"
                                                       href="{{route('admin.trip.export')}}?search={{$search}}&&type={{$type}}&&file=excel">{{ translate('excel') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endcan

                                </div>
                            </div>
                            <div id="trip-list-view">
                                @include('tripmanagement::admin.trip.partials._trip-list')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Main Content -->
@endsection

@push('script')
    <script>
        function loadPartialView(url, divId, data) {
            $.get({
                url: url,
                dataType: 'json',
                data: {data},
                beforeSend: function () {
                    $('#resource-loader').show();
                },
                success: function (response) {
                    $(divId).empty().html(response)
                },
                complete: function () {
                    $('#resource-loader').hide();
                },
                error: function () {
                    $('#resource-loader').hide();
                    toastr.error('{{translate('failed_to_load_data')}}')
                },
            });
        }
    </script>
    <script>
        let data_range = $('#date-range');
        let data_input = $('#data-input');

        data_range.on('change', function () {
            if (data_range.val() === 'custom_date') {
                data_input.css('display', 'flex')
            } else {
                data_input.css('display', 'none')
                loadPartialView('{{url()->full()}}', '#trip-stats', data_range.val())
            }
        });
    </script>

    <script>
        function getDate() {
            let start = $('#start_date').val()
            let end = $('#end_date').val()
            if (!start || !end || start > end) {
                toastr.error('{{translate('please_select_proper_date_range')}}');
                return;
            }
            let data = {start: start, end: end}
            loadPartialView('{{url()->full()}}', '#trip-stats', data)
        }
    </script>

@endpush
