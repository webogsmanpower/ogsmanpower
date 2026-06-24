@extends('backend.layouts.app')
@section('title')
    {{ __('settings') }}
@endsection
@section('breadcrumbs')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">{{ __('profile') }}</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('home') }}</a></li>
                <li class="breadcrumb-item active">{{ __('settings') }}</li>
            </ol>
        </div>
    </div>
@endsection
{{-- @section('content')
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="tab-pane active" id="gen_settings">
                        <div class="text-center mb-4">
                            <img class="profile-user-img img-circle border-secondary m-auto p-3"
                                src="{{ $user->image_url }}" alt="{{ __('profile_picture') }}" id="admin_image">

                        </div>
                        <form class="form-horizontal" action="{{ route('profile.update') }}" method="POST"
                            enctype="multipart/form-data" autocomplete="off">
                            @method('PUT')
                            @csrf
                            <div class="form-group row">
                                <x-forms.label name="name" for="name" class="col-sm-3" />
                                <div class="col-sm-9">
                                    <input required name="name" value="{{ $user->name }}" type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        placeholder="{{ __('name') }}">
                                    @error('name')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <x-forms.label name="email" for="email" class="col-sm-3" />
                                <div class="col-sm-9">
                                    <input required name="email" value="{{ $user->email }}" type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="{{ __('email') }}">
                                    @error('email')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <x-forms.label name="whatsapp" for="whatsapp" class="col-sm-3" />
                                <div class="col-sm-9">
                                    <input required name="whatsapp" value="{{ $user->whatsapp }}" type="whatsapp"
                                        class="form-control @error('whatsapp') is-invalid @enderror"
                                        placeholder="{{ __('whatsapp') }}">
                                    @error('whatsapp')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <x-forms.label name="Industry Type" for="industry_type" class="col-sm-3" />
                                <select name="industry_type" class="form-control {{ error('industry_type') }}"
                                    id="organization_type_id">
                                    <option value="" class="d-none">
                                        {{ __('select_one') }}
                                    </option>
                                    @foreach ($industry_types as $type)
                                        <option {{ $type->name == old('industry_type') ? 'selected' : '' }}
                                            value="{{ $type->name }}">
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="section pt-3" id="location">
                                <div class="card mb-0">

                                    @if (config('templatecookie.map_show'))
                                        <div class="card-header">
                                            <div class="card-title">
                                                {{ __('location') }}
                                                <span class="text-red font-weight-bold">*</span>
                                                <small class="h6">
                                                    ({{ __('click_to_add_a_pointer') }})
                                                </small>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <x-website.map.map-warning />
                                            @php
                                                $map = $setting->default_map;
                                            @endphp
                                            <div id="google-map-div" class="{{ $map == 'google-map' ? '' : 'd-none' }}">
                                                <input id="searchInput" class="mapClass" type="text"
                                                    placeholder="Enter a location">
                                                <div class="map mymap" id="google-map"></div>
                                            </div>
                                            <div class="{{ $map == 'leaflet' ? '' : 'd-none' }}">
                                                <input type="text" autocomplete="off" id="leaflet_search"
                                                    placeholder="{{ __('enter_city_name') }}" class="form-control" /> <br>
                                                <div id="leaflet-map"></div>
                                            </div>
                                            @error('location')
                                                <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        @php
                                            $location = session()->get('location');
                                        @endphp
                                        <div class="card-footer location_footer d-none">
                                            <span>
                                                <img src="{{ asset('frontend/assets/images/loader.gif') }}" alt="loader"
                                                    width="50px" height="50px" class="loader_position d-none">
                                            </span>
                                            <div class="location_secion">
                                                {{ __('country') }}: <span
                                                    class="location_country">{{ $location && array_key_exists('country', $location) ? $location['country'] : '-' }}</span>
                                                <br>
                                                {{ __('full_address') }}: <span
                                                    class="location_full_address">{{ $location && array_key_exists('exact_location', $location) ? $location['exact_location'] : '-' }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="card-header border-0">
                                            {{ __('location') }}
                                            <span class="text-red font-weight-bold">*</span>
                                        </div>
                                        <div class="card-body pt-0 row">
                                            <div class="col-12">
                                                @livewire('country-state-city')
                                                @error('location')
                                                    <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                                @enderror

                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group row">
                                <x-forms.label name="image" for="change_image" class="col-sm-3" />
                                <div class="col-sm-9">
                                    <div class="custom-file">
                                        <input name="image"
                                            onchange="document.getElementById('admin_image').src = window.URL.createObjectURL(this.files[0])"
                                            type="file" class="custom-file-input"
                                            accept="image/jpg, image/jpeg, image/png">
                                        <label class="custom-file-label">{{ __('choose_file') }}</label>
                                    </div>

                                    @error('image')
                                        <span class="invalid-feedback d-block"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <x-forms.label name="password" for="change-password-visibility" class="col-sm-3"
                                    :required="false" />
                                <div class="col-sm-9 mt-2">
                                    <input type="hidden" value="0" name="isPasswordChange">
                                    <div class="icheck-success d-inline">
                                        <input value="1" name="isPasswordChange" type="checkbox"
                                            {{ old('isPasswordChange') ? 'checked' : '' }}
                                            id="change-password-visibility">
                                        <label for="change-password-visibility">
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div id="password_visibility" class="{{ old('isPasswordChange') ? 'd-block' : 'd-none' }}">
                                <div class="form-group row">
                                    <x-forms.label name="current_password" for="change-password-visibility"
                                        class="col-sm-3" />
                                    <div class="col-sm-9">
                                        <input name="current_password" type="password"
                                            value="{{ old('current_password') }}"
                                            class="form-control @error('current_password') is-invalid @enderror"
                                            placeholder="{{ __('current_password') }}">
                                        @error('current_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <x-forms.label name="new_password" class="col-sm-3" />
                                    <div class="col-sm-9">
                                        <input name="password" type="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="{{ __('new_password') }}">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <x-forms.label name="confirm_password" class="col-sm-3" />
                                    <div class="col-sm-9">
                                        <input name="password_confirmation" type="password"
                                            class="form-control @error('password_confirmation') is-invalid @enderror"
                                            placeholder="{{ __('confirm_password') }}">
                                        @error('password_confirmation')
                                            <div class="invalid-feedback"> {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="offset-sm-3 col-sm-9">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-sync"></i>
                                        {{ __('update') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection --}}


@section('content')
    @if ($errors->has('verification'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('verification') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (!auth()->user()->hasRole('superadmin'))
        <div class="container-fluid">
            <form class="form-horizontal" action="{{ route('profile.update') }}" method="POST"
                enctype="multipart/form-data" autocomplete="off">
                @method('PUT')
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                {{ __('account_details') }}
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <x-forms.label name="employer_name" :required="true" />
                                    <x-forms.input type="text" name="name" data-show-errors="true" placeholder="name"
                                        value="{{ old('name', $user->name) }}" />
                                </div>
                                <div class="row">
                                    <div class="form-group col-sm-6">
                                        <x-forms.label name="username" :required="false" />
                                        <x-forms.input type="text" name="username" placeholder="username"
                                            value="{{ old('username', $user->username) }}" />
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <x-forms.label name="email" />
                                        <x-forms.input type="email" name="email" placeholder="email"
                                            value="{{ old('email', $user->email) }}" />
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="form-group datepicker col-md-6">
                                        <x-forms.label name="website" :required="false" />
                                        <x-forms.input type="text" name="website" placeholder="website"
                                            value="{{ old('website', $user->website) }}" />
                                        <x-forms.error name="establishment_date" />
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <x-forms.label name="industry_type" />
                                        <select name="industry_type_id"
                                            class="form-control select2bs4 {{ error('industry_type_id') }}"
                                            id="organization_type_id">
                                            <option value="" class="d-none">
                                                {{ __('select_one') }}
                                            </option>
                                            @foreach ($industry_types as $type)
                                                <option
                                                    {{ $type->id == old('industry_type_id', $user->industry_type_id) ? 'selected' : '' }}
                                                    value="{{ $type->id }}">
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-forms.error name="industry_type_id" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-sm-6">
                                        <x-forms.label name="whatsapp" />
                                        <x-forms.input type="whatsapp" name="whatsapp" placeholder="whatsapp"
                                            value="{{ old('whatsapp', $user->whatsapp) }}" />
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <x-forms.label name="change_password" />
                                        <x-forms.input type="password" name="password" placeholder="password" />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <x-forms.label name="bio" :required="false" />
                                        <textarea id="image_ckeditor" rows="8" name="bio" placeholder="{{ __('bio') }}" class="form-control">{{ old('bio', $user->bio) }}</textarea>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                {{ __('Profile Image') }}
                            </div>
                            <div class="card-body">
                                <div class="row justify-content-center">
                                    <div class="form-group col-xl-6 text-center">
                                        <x-forms.label name="Profile" :required="false" />
                                        <div class="profile-image-wrapper">
                                            <input name="image" type="file" class="dropify profile-image-input"
                                                data-show-errors="true" data-default-file="{{ $user->image_url }}"
                                                data-height="150">
                                        </div>
                                        {{-- @dd($user->image_url) --}}
                                        <p
                                            class="tw-text-gray-500 tw-text-xs tw-text-center mt-2 recommended-img-note mb-0">
                                            Recommended Image Size: 150x150
                                        </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-xl-6">
                                        <x-forms.label name="ID Card" :required="false" />
                                        <input name="id_card_image" type="file" data-show-errors="true"
                                            data-width="50%" class="dropify"
                                            data-default-file="{{ asset($user->id_card_image) }}">
                                        <p class="tw-text-gray-500 tw-text-xs tw-text-left mt-2 recommended-img-note mb-0">
                                            Recommended Image Size: 68x68</p>
                                    </div>

                                    <div class="form-group col-xl-6">
                                        <x-forms.label name="Passport" :required="false" />
                                        <input name="passport_image" type="file" data-show-errors="true"
                                            data-width="50%" class="dropify"
                                            data-default-file="{{ asset($user->passport_image) }}">
                                        <p class="tw-text-gray-500 tw-text-xs tw-text-left mt-2 recommended-img-note mb-0">
                                            Recommended Image Size: 68x68</p>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="form-group col-xl-6">
                                        <x-forms.label name="Company Certificate" :required="false" />
                                        <input name="comapny_certificate_image" type="file" data-show-errors="true"
                                            data-width="50%" class="dropify"
                                            data-default-file="{{ asset($user->comapny_certificate_image) }}">
                                        <p class="tw-text-gray-500 tw-text-xs tw-text-left mt-2 recommended-img-note mb-0">
                                            Recommended Image Size: 68x68</p>
                                    </div>

                                    <div class="form-group col-xl-6">
                                        <x-forms.label name="Lisence" :required="false" />
                                        <input name="lisence_image" type="file" data-show-errors="true"
                                            data-width="50%" class="dropify"
                                            data-default-file="{{ asset($user->lisence_image) }}">
                                        <p class="tw-text-gray-500 tw-text-xs tw-text-left mt-2 recommended-img-note mb-0">
                                            Recommended Image Size: 68x68</p>
                                    </div>

                                </div>
                            </div>
                        </div>
                        {{-- <div class="card">
                        <div class="card-header">
                            {{ __('social_details') }}
                        </div>
                        <div class="card-body">
                            <div id="multiple_feature_part">
                                <div class="row justify-content-center">
                                    <div class="form-group col-md-4">
                                        <select
                                            class="form-control select2bs4 @error('social_media') border-danger @enderror"
                                            name="social_media[]">
                                            <option value="" class="d-none" disabled>{{ __('select_one') }}
                                            </option>
                                            <option {{ old('social_media') == 'facebook' ? 'selected' : '' }}
                                                value="facebook">{{ __('facebook') }}</option>
                                            <option {{ old('social_media') == 'twitter' ? 'selected' : '' }}
                                                value="twitter">{{ __('twitter') }}</option>
                                            <option {{ old('social_media') == 'instagram' ? 'selected' : '' }}
                                                value="instagram">{{ __('instagram') }}
                                            </option>
                                            <option {{ old('social_media') == 'youtube' ? 'selected' : '' }}
                                                value="youtube">{{ __('youtube') }}</option>
                                            <option {{ old('social_media') == 'linkedin' ? 'selected' : '' }}
                                                value="linkedin">{{ __('linkedin') }}</option>
                                            <option {{ old('social_media') == 'pinterest' ? 'selected' : '' }}
                                                value="pinterest">{{ __('pinterest') }}
                                            </option>
                                            <option {{ old('social_media') == 'reddit' ? 'selected' : '' }}
                                                value="reddit">{{ __('reddit') }}</option>
                                            <option {{ old('social_media') == 'github' ? 'selected' : '' }}
                                                value="github">{{ __('github') }}</option>
                                            <option {{ old('social_media') == 'other' ? 'selected' : '' }} value="other">
                                                {{ __('other') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <input type="url" name="url[]" class="form-control">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <a role="button" onclick="add_features_field()"
                                            class="btn bg-primary text-light"><i class="fas fa-plus"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> --}}
                        <div class="card">
                            @if (config('templatecookie.map_show'))
                                <div class="card-header">
                                    {{ __('location') }}
                                    <span class="text-red font-weight-bold">*</span>
                                    <small class="h6">
                                        ({{ __('click_to_add_a_pointer') }})
                                    </small>
                                </div>
                                <div class="card-body">
                                    <x-website.map.map-warning />

                                    @php
                                        $map = $setting->default_map;
                                    @endphp
                                    <div id="google-map-div" class="{{ $map == 'google-map' ? '' : 'd-none' }}">
                                        <input id="searchInput" class="mapClass" type="text"
                                            placeholder="Enter a location">
                                        <div class="map mymap" id="google-map"></div>
                                    </div>
                                    <div class="{{ $map == 'leaflet' ? '' : 'd-none' }}">
                                        <input type="text" autocomplete="off" id="leaflet_search"
                                            placeholder="{{ __('enter_city_name') }}" class="form-control" /> <br>
                                        <div id="leaflet-map"></div>
                                    </div>
                                    @error('location')
                                        <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                    @enderror

                                </div>
                                @php
                                    $location = session()->get('location');

                                @endphp
                                <div class="card-footer location_footer d-none">
                                    <span>
                                        <img src="{{ asset('frontend/assets/images/loader.gif') }}" alt="loading"
                                            width="50px" height="50px" class="loader_position d-none">
                                    </span>
                                    <div class="location_secion">
                                        {{ __('country') }}: <span
                                            class="location_country">{{ $location && array_key_exists('country', $location) ? $location['country'] : '-' }}</span>
                                        <br>
                                        {{ __('full_address') }}: <span
                                            class="location_full_address">{{ $location && array_key_exists('exact_location', $location) ? $location['exact_location'] : '-' }}</span>
                                    </div>
                                </div>
                            @else
                                @php
                                    session([
                                        'selectedCountryId' => null,
                                        'selectedStateId' => null,
                                        'selectedCityId' => null,
                                    ]);
                                    session([
                                        'selectedCountryId' => $user->country,
                                        'selectedStateId' => $user->region,
                                        'selectedCityId' => $user->district,
                                    ]);
                                @endphp
                                <div class="card-header border-0">
                                    {{ __('location') }}
                                </div>
                                <div class="card-body pt-0 row">
                                    <div class="col-12">
                                        @livewire('country-state-city')
                                        @error('location')
                                            <span class="ml-3 text-md text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
                <div class="d-flex justify-content-center align-items-center">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-block bg-success">
                            <i class="fas fa-plus mr-1"></i>
                            {{ __('save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="tab-pane active" id="gen_settings">
                            <div class="text-center mb-4">
                                <img class="profile-user-img img-circle border-secondary m-auto p-3"
                                    src="{{ $user->image_url }}" alt="{{ __('profile_picture') }}" id="admin_image">

                            </div>
                            <form class="form-horizontal" action="{{ route('profile.update') }}" method="POST"
                                enctype="multipart/form-data" autocomplete="off">
                                @method('PUT')
                                @csrf
                                <div class="form-group row">
                                    <x-forms.label name="name" for="name" class="col-sm-3" />
                                    <div class="col-sm-9">
                                        <input required name="name" value="{{ $user->name }}" type="text"
                                            class="form-control @error('name') is-invalid @enderror"
                                            placeholder="{{ __('name') }}">
                                        @error('name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <x-forms.label name="email" for="email" class="col-sm-3" />
                                    <div class="col-sm-9">
                                        <input required name="email" value="{{ $user->email }}" type="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            placeholder="{{ __('email') }}">
                                        @error('email')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <x-forms.label name="whatsapp" for="whatsapp" class="col-sm-3" />
                                    <div class="col-sm-9">
                                        <input required name="whatsapp" value="{{ $user->whatsapp }}" type="whatsapp"
                                            class="form-control @error('whatsapp') is-invalid @enderror"
                                            placeholder="{{ __('whatsapp') }}">
                                        @error('whatsapp')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <x-forms.label name="image" for="change_image" class="col-sm-3" />
                                    <div class="col-sm-9">
                                        <div class="custom-file">
                                            <input name="image"
                                                onchange="document.getElementById('admin_image').src = window.URL.createObjectURL(this.files[0])"
                                                type="file" class="custom-file-input"
                                                accept="image/jpg, image/jpeg, image/png">
                                            <label class="custom-file-label">{{ __('choose_file') }}</label>
                                        </div>

                                        @error('image')
                                            <span class="invalid-feedback d-block"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <x-forms.label name="password" for="change-password-visibility" class="col-sm-3"
                                        :required="false" />
                                    <div class="col-sm-9 mt-2">
                                        <input type="hidden" value="0" name="isPasswordChange">
                                        <div class="icheck-success d-inline">
                                            <input value="1" name="isPasswordChange" type="checkbox"
                                                {{ old('isPasswordChange') ? 'checked' : '' }}
                                                id="change-password-visibility">
                                            <label for="change-password-visibility">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div id="password_visibility"
                                    class="{{ old('isPasswordChange') ? 'd-block' : 'd-none' }}">
                                    <div class="form-group row">
                                        <x-forms.label name="current_password" for="change-password-visibility"
                                            class="col-sm-3" />
                                        <div class="col-sm-9">
                                            <input name="current_password" type="password"
                                                value="{{ old('current_password') }}"
                                                class="form-control @error('current_password') is-invalid @enderror"
                                                placeholder="{{ __('current_password') }}">
                                            @error('current_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <x-forms.label name="new_password" class="col-sm-3" />
                                        <div class="col-sm-9">
                                            <input name="password" type="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                placeholder="{{ __('new_password') }}">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <x-forms.label name="confirm_password" class="col-sm-3" />
                                        <div class="col-sm-9">
                                            <input name="password_confirmation" type="password"
                                                class="form-control @error('password_confirmation') is-invalid @enderror"
                                                placeholder="{{ __('confirm_password') }}">
                                            @error('password_confirmation')
                                                <div class="invalid-feedback"> {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-3 col-sm-9">
                                        <button type="submit" class="btn btn-success"><i class="fas fa-sync"></i>
                                            {{ __('update') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('style')
    <link rel="stylesheet" href="{{ asset('backend') }}/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="{{ asset('backend') }}/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    <style>
        .ck-editor__editable_inline {
            min-height: 400px;
        }

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

        /* Ensure the wrapper for the image is styled correctly */
        .profile-image-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            /* Make the container circular */
            overflow: hidden;
            /* Crop any overflowed content */
            margin: 0 auto;
            /* Center the container */
            border: 2px solid #ddd;
            /* Add a subtle border */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Optional shadow */
        }

        /* Style the input itself */
        .profile-image-input {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            /* Ensure circular input */
            object-fit: cover;
            /* Scale the image to fill the circle */
        }

        /* Optional: Add hover effect for the wrapper */
        .profile-image-wrapper:hover {
            border-color: #007bff;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
    <style>
        .profile-user-img {
            height: 150px !important;
            width: 150px !important;
            object-fit: cover !important;
        }
    </style>
    <!-- >=>Leaflet Map<=< -->
    <x-map.leaflet.map_links />
    <x-map.leaflet.autocomplete_links />

    @include('map::links')
@endsection

@section('script')
    @livewireScripts
    <script>
        function closeAlert() {
            document.getElementById('error-alert').style.display = 'none';
        }
    </script>

    <script>
        $(document).ready(function() {
            $('.select21').select2();
        });
        window.addEventListener('render-select2', event => {
            console.log('fired');
            $('.select21').select2();
        })
    </script>
    @stack('js')
    @if (app()->getLocale() == 'ar')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js
                                                                                                                    "></script>
    @endif
    <script>
        //init datepicker
        $(document).ready(function() {
            $('#establishment_date').datepicker({
                format: 'dd-mm-yyyy',
                isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
                language: "{{ app()->getLocale() }}",
            });
        });

        $(document).on("click", "#remove_item", function() {
            $(this).parent().parent('div').remove();
        });

        function add_features_field() {
            $("#multiple_feature_part").append(`
            <div class="row justify-content-center">
                <div class="form-group col-md-4">
                    <select class="form-control select2bs4 @error('social_media') border-danger @enderror"
                        name="social_media[]">
                        <option value="" class="d-none" disabled>{{ __('select_one') }}
                        </option>
                        <option {{ old('social_media') == 'facebook' ? 'selected' : '' }}
                            value="facebook">{{ __('facebook') }}</option>
                        <option {{ old('social_media') == 'twitter' ? 'selected' : '' }}
                            value="twitter">{{ __('twitter') }}</option>
                        <option {{ old('social_media') == 'instagram' ? 'selected' : '' }}
                            value="instagram">{{ __('instagram') }}
                        </option>
                        <option {{ old('social_media') == 'youtube' ? 'selected' : '' }}
                            value="youtube">{{ __('youtube') }}</option>
                        <option {{ old('social_media') == 'linkedin' ? 'selected' : '' }}
                            value="linkedin">{{ __('linkedin') }}</option>
                        <option {{ old('social_media') == 'pinterest' ? 'selected' : '' }}
                            value="pinterest">{{ __('pinterest') }}
                        </option>
                        <option {{ old('social_media') == 'reddit' ? 'selected' : '' }}
                            value="reddit">{{ __('reddit') }}</option>
                        <option {{ old('social_media') == 'github' ? 'selected' : '' }}
                            value="github">{{ __('github') }}</option>
                        <option {{ old('social_media') == 'other' ? 'selected' : '' }} value="other">
                            {{ __('other') }}</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <input type="url" name="url[]" class="form-control">
                </div>
                <div class="form-group col-md-2">
                    <a role="button" id="remove_item"
                        class="btn bg-danger text-light"><i class="fas fa-times"></i></a>
                </div>
            </div>
            `);
        }
    </script>
    <script>
        $('#change-password-visibility').on('change', function() {
            var value = $(this).prop('checked') == true ? 1 : 0;

            if (value == 1) {
                $('#password_visibility').addClass('d-block')
                $('#password_visibility').removeClass('d-none')
            } else {
                $('#password_visibility').addClass('d-none')
                $('#password_visibility').removeClass('d-block')
            }
        })
    </script>

    {{-- Leaflet  --}}
    @include('map::set-leafletmap')
    @include('map::set-googlemap')
@endsection
