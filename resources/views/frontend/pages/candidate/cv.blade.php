{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.candidate.layout.app')

@section('title')
    {{ __('cv') }}
@endsection
@section('main')
    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row">
                {{-- <x-website.candidate.sidebar /> --}}
                <div class="col-lg-9">
                    <div class="dashboard-right">
                        <div class="cadidate-dashboard-tabs candidate ">
                            {{-- CV Setting  --}}
                            <div>
                                <div class="col-lg-6 mb-3">
                                    <form action="{{ route('candidate.viewResume') }}" method="POST"
                                        enctype="multipart/form-data" target="_blank">
                                        @csrf
                                        <div class="mb-3">
                                            <x-forms.label :required="true" name="Bilangual Resume Language"
                                                class="body-font-4 d-block text-gray-900 rt-mb-8" />
                                            <select class="form-control @error('language_code') is-invalid @enderror"
                                                name="language_code">
                                                <option value="" disabled selected>Select one</option>

                                                <option value="en"
                                                    {{ $candidate->language_code == 'en' ? 'selected' : '' }} selected>
                                                    English {{ $subscription->contains('en') ? '(Paid)' : '' }}
                                                </option>

                                                <option value="tr"
                                                    {{ $candidate->language_code == 'tr' ? 'selected' : '' }}>
                                                    Turkish {{ $subscription->contains('tr') ? '(Paid)' : '' }}</option>
                                                <option value="da"
                                                    {{ $candidate->language_code == 'da' ? 'selected' : '' }}>
                                                    German{{ $subscription->contains('da') ? '(Paid)' : '' }}
                                                </option>
                                                <option value="ro"
                                                    {{ $candidate->language_code == 'ro' ? 'selected' : '' }}>
                                                    Romanian{{ $subscription->contains('ro') ? '(Paid)' : '' }}
                                                </option>
                                                <option value="lt"
                                                    {{ $candidate->language_code == 'lt' ? 'selected' : '' }}>
                                                    Lithuanian{{ $subscription->contains('lt') ? '(Paid)' : '' }}
                                                </option>
                                                <option value="pl"
                                                    {{ $candidate->language_code == 'pl' ? 'selected' : '' }}>
                                                    Polish{{ $subscription->contains('pl') ? '(Paid)' : '' }}
                                                </option>
                                                <option value="fr"
                                                    {{ $candidate->language_code == 'fr' ? 'selected' : '' }}>
                                                    France{{ $subscription->contains('fr') ? '(Paid)' : '' }}
                                                </option>
                                                <option value="es"
                                                    {{ $candidate->language_code == 'es' ? 'selected' : '' }}>
                                                    Spanish{{ $subscription->contains('es') ? '(Paid)' : '' }}
                                                </option>
                                                <option value="ar"
                                                    {{ $candidate->language_code == 'ar' ? 'selected' : '' }}>
                                                    Arabic{{ $subscription->contains('ar') ? '(Paid)' : '' }}
                                                </option>


                                            </select>
                                        </div>

                                </div>
                                <div class="cv-container p-4 border rounded bg-light">
                                    <h4 class="mb-4 text-center">Choose Your CV Format</h4>

                                    <div class="form-group mb-3">
                                        <label class="form-check-label fw-bold">Available Formats:</label>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format"
                                                value="general_format" id="general_format"
                                                {{ $candidate->resume_format == 'general_format' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="general_format">
                                                General Format
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format"
                                                value="driver_format" id="driver_format"
                                                {{ $candidate->resume_format == 'driver_format' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="driver_format">
                                                Driver Format
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format"
                                                value="guard_format" id="guard_format"
                                                {{ $candidate->resume_format == 'guard_format' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="guard_format">
                                                Security Guard Format
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format"
                                                value="beautician_format" id="beautician_format"
                                                {{ $candidate->resume_format == 'beautician_format' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="beautician_format">
                                                Beautician Format
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format"
                                                value="web_developer_format" id="web_developer_format"
                                                {{ $candidate->resume_format == 'web_developer_format' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="web_developer_format">
                                                Professionl Format
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format"
                                                value="bike_rider_format" id="bike_rider_format"
                                                {{ $candidate->resume_format == 'bike_rider_format' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="bike_rider_format">
                                                Bike Rider Format
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="format"
                                                value="bilangual_format" id="bilangual_format"
                                                {{ $candidate->resume_format == 'bilangual_format' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="bilangual_format">
                                                Bilangual Format
                                            </label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="action_type" id="action_type1" value="view">

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-block w-100"
                                            onclick="document.getElementById('action_type1').value='view'">View
                                            Resume</button>
                                        <button type="submit" class="btn btn-primary btn-block w-100"
                                            onclick="document.getElementById('action_type1').value='download'">Download
                                            Resume</button>
                                    </div>

                                </div>
                                </form>
                            </div>

                            @php
                                $languages = [
                                    'en' => 'English',
                                    'tr' => 'Turkish',
                                    'da' => 'German',
                                    'ro' => 'Romanian',
                                    'lt' => 'Lithuanian',
                                    'pl' => 'Polish',
                                    'fr' => 'French',
                                    'es' => 'Spanish',
                                    'ar' => 'Arabic',
                                ];
                            @endphp



                            <div class="mt-3">
                                <h2> Payments</h2>
                                <table class="table table-striped table-hover">
                                    <thead>

                                        <tr>

                                            <th scope="col">Language</th>
                                            <th scope="col">Payment Method</th>
                                            <th scope="col">Status</th>
                                        </tr>

                                    </thead>
                                    <tbody>
                                        @foreach ($subscription as $sub)
                                            <tr>
                                                <td>{{ $languages[$sub->language_code] ?? $sub->language_code }}</td>
                                                <td>{{ $sub->payment_method }}</td>
                                                <td>{{ $sub->status }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    {{-- <div class="dashboard-footer text-center body-font-4 text-gray-500">
            <x-website.footer-copyright />
        </div> --}}
    </div>
    </div>
@endsection
