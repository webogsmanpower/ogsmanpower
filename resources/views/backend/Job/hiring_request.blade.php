@extends('backend.layouts.app')
@section('title')
    {{ __('applied_jobs') }}
@endsection
@section('content')
    @php
        $userr = auth()->user();
    @endphp
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap justify-content-between">
                        <h3 class="card-title line-height-36">{{ __('Hiring Requests') }}</h3>
                        <div class="d-flex flex-column flex-md-row">
                        </div>
                    </div>
                </div>

                <div class="card-body table-responsive p-0 m-0">
                    @include('backend.layouts.partials.message')
                    <div class="row">

                        <div class="col-sm-12">
                            <table class="ll-table table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th width="10%">{{ __('candidate') }}</th>
                                        <th width="10%">{{ __('company') }}</th>
                                        <th width="10%">{{ __('message') }}</th>
                                        <th width="10%">{{ __('date') }}</th>
                                        @if (userCan('job.update') || userCan('job.delete'))
                                            <th width="10%">{{ __('action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($hireRequests->count() > 0)
                                        @foreach ($hireRequests as $index => $request)
                                            <tr>
                                                <td tabindex="0">
                                                    <a href="{{ route('candidate.show', $request->candidate->id) }}"
                                                        class="company">
                                                        @if ($request->candidate->user->name)
                                                            <img src="{{ asset($request->candidate->photo) }}"
                                                                alt="image">
                                                        @else
                                                            <x-svg.briefcase-logo />
                                                        @endif
                                                        <div>
                                                            <p>
                                                                <span>{{ $request->candidate && $request->candidate->user ? $request->candidate->user->name : ' ' }}</span>
                                                            </p>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td tabindex="0">
                                                    <a href="{{ route('company.show', $request->employer->id) }}"
                                                        class="employer">
                                                        @if ($request->employer)
                                                            <img src="{{ asset($request->employer->logo_url) }}"
                                                                alt="image">
                                                        @else
                                                            <x-svg.briefcase-logo />
                                                        @endif
                                                        <div>
                                                            <p>
                                                                <span>{{ $request->employer && $request->employer->user ? $request->employer->user->name : $request->company_name }}</span>
                                                            </p>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td tabindex="0">

                                                    <p>{{ $request->message }}</p>

                                                </td>
                                                <td tabindex="0">

                                                    {{ $request->created_at->format('d M Y, H:i') }}

                                                </td>
                                                <td>
                                                    <form action="{{ route('send.hire.mail', $request->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn ll-btn ll-border-none" data-toggle="tooltip" data-placement="top" title="{{ __('mail') }}">
                                                            {{ __('Send Mail') }}
                                                            <x-svg.table-btn-arrow />
                                                        </button>
                                                    </form>
                                                </td>

                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6" class="text-center">{{ __('no_data_found') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


@endsection

@section('script')
    <script>
        $(document).ready(function() {
            validate();
            $('#title').keyup(validate);
        });

        function validate() {
            if ($('#title')?.val()?.length > 0) {
                $('#crossB').removeClass('d-none');
            } else {
                $('#crossB').addClass('d-none');
            }
        }

        function RemoveFilter(id) {
            $('#' + id).val('');
            $('#formSubmit').submit();
        }
    </script>
@endsection

@section('style')
    <style>
        .select2-results__option[aria-selected=true] {
            display: none;
        }

        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            color: #fff;
            border: 1px solid #fff;
            background: #007bff;
            border-radius: 30px;
        }

        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
        }

        /* Style  radio button */
        .expired_radio::after {
            content: "";
            display: inline-block;
            border-radius: 50%;
            margin-right: 8px;
            background-color: red;
        }
    </style>
@endsection
