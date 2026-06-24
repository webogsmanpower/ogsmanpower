{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.agent.new-sidebar')

@section('title')
    {{ __('settings') }}
@endsection
@section('main')
    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row">
                {{-- <x-website.candidate.sidebar /> --}}
                <div class="col-lg-9">
                    <div class="dashboard-right">
                        <div class="dashboard-right-header rt-mb-32">
                            <div class="left-text m-0">
                                <h3 class="f-size-18 lh-1 m-0">{{ __('settings') }}</h3>
                            </div>
                            <span class="sidebar-open-nav">
                                <i class="ph-list"></i>
                            </span>
                        </div>
                        <div class="cadidate-dashboard-tabs candidate">
                            <div class="tw-overflow-x-auto">
                                <ul class="nav nav-pills tw-gap-x-8" id="pills-tab" role="tablist">
                                    {{-- Basic Setting  --}}
                                    <li class="nav-item" role="presentation">
                                        <button
                                            class="nav-link {{ !session('type') || session('type') == 'basic' ? 'active' : '' }}"
                                            id="pills-personal-tab" data-bs-toggle="pill" data-bs-target="#pills-personal"
                                            type="button" role="tab" aria-controls="pills-personal"
                                            aria-selected="true">
                                            <x-svg.user-icon />
                                            {{ __('basic') }}
                                        </button>
                                    </li>


                                    <span class="glider"></span>
                                </ul>
                            </div>
                            <div class="tab-content" id="pills-tabContent">
                                {{-- Basic Setting  --}}
                                <div class="tab-pane fade {{ !session('type') || session('type') == 'basic' ? 'show active' : '' }}"
                                    id="pills-personal" role="tabpanel" aria-labelledby="pills-personal-tab">
                                    <form action="{{ route('agent.settingUpdate') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="type" value="basic">
                                        <div class="dashboard-account-setting-item tw-py-0">
                                            <h6> {{ __('basic_information') }}</h6>
                                            <div class="row">
                                                <div class="col-lg-6 mb-3">
                                                    <x-forms.label name="name" required="true"
                                                        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                    <div class="fromGroup">
                                                        <div class="form-control-icon">
                                                            <x-forms.input type="text" name="name"
                                                                value="{{ $user->name }}" placeholder="name"
                                                                id="name" />

                                                            @error('name')
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <x-forms.label name="username" required="true"
                                                        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                    <div class="fromGroup">
                                                        <div class="form-control-icon">
                                                            <x-forms.input type="text" name="username"
                                                                value="{{ $user->username }}" placeholder="username"
                                                                id="username" />

                                                            @error('username')
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <x-forms.label name="email" required="true"
                                                        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                    <div class="fromGroup">
                                                        <div class="form-control-icon">
                                                            <x-forms.input type="email" name="email"
                                                                value="{{ $user->email }}" placeholder="email"
                                                                id="email" />

                                                            @error('name')
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <x-forms.label name="whatsapp" required="true"
                                                        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                    <div class="fromGroup">
                                                        <div class="form-control-icon">
                                                            <x-forms.input type="text" name="whatsapp"
                                                                value="{{ $user->whatsapp }}" placeholder="whatsapp"
                                                                id="whatsapp" />

                                                            @error('name')
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <x-forms.label name="password" required="false"
                                                        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                    <div class="fromGroup">
                                                        <div class="form-control-icon">
                                                            <x-forms.input type="text" name="password"
                                                                 placeholder="password"
                                                                id="password" />


                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <x-forms.label name="confirm_password" required="true"
                                                        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                    <div class="fromGroup">
                                                        <div class="form-control-icon">
                                                            <x-forms.input type="text" name="confirm_password"
                                                                 placeholder="confirm_password"
                                                                id="confirm_password" />

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12 ">
                                                    <label> Image</label><small style="color: red">* (
                                                        {{ __('Ratio') }} 4:3 )</small>
                                                    <div class="custom-file">
                                                        <input type="file" name="image" id="agentImageInput"
                                                            class="custom-file-input"
                                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                        <label class="custom-file-label" for="agentImageInput">Choose
                                                            File</label>
                                                    </div>
                                                    <center class="pt-4">
                                                        <img style="height: 200px; border: 1px solid; border-radius: 10px;"
                                                            id="agentImagePreview"
                                                            src="{{ isset($user) && $user->image ? asset($user->image) : asset('backend/image/default.png') }}"
                                                            alt="agent-image">
                                                    </center>
                                                </div>


                                                {{-- <div class="col-lg-12 mb-3">
                                                    <x-forms.label :required="false" name="about_us"
                                                        class="pointer body-font-4 d-block text-gray-900 rt-mb-8" />
                                                    <textarea class="form-control ckedit  @error('about_us') is-invalid @enderror" name="about_us" id="image_ckeditor">
                                                    {!! $user->company->bio !!}</textarea>
                                                    @error('about_us')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div> --}}
                                                <div class="col-lg-12 mt-4">
                                                    <button type="submit" class="btn btn-primary">
                                                        {{ __('save_changes') }}
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </form>
                                    <div>
                                        {{-- <h6 class="resume">{{ __('your_cv_resume') }}</h6>
                                        @if ($errors->has('resume_name') || $errors->has('resume_file'))
                                            <div class="alert alert-danger" role="alert">
                                                @error('resume_name')
                                                    <span class="d-block"><strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                                @error('resume_file')
                                                    <span class="d-block"><strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        @endif
                                        <div class="resume-lists">
                                            @foreach ($resumes as $resume)
                                                <div class="resume-item">
                                                    <div class="resume-icon">
                                                        <x-svg.file-icon2 />
                                                    </div>
                                                    <div>
                                                        <h4 class="resume-title">{{ $resume->name }}</h4>
                                                        <h6 class="resume-size">{{ $resume->file_size }}</h6>
                                                    </div>
                                                    <div class="dot-icon ms-auto">
                                                        <button type="button" class="btn p-0" id="dropdownMenuButton5"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <x-svg.three-dots />
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end company-dashboard-dropdown"
                                                            aria-labelledby="dropdownMenuButton5">
                                                            <li>
                                                                <form id="cv_show_{{ $resume->id }}"
                                                                    action="{{ route('candidate.cv.show') }}"
                                                                    method="POST">
                                                                    @csrf
                                                                    <input type="hidden" name="cv"
                                                                        value="{{ $resume->id }}" class="d-none">
                                                                    <button type="submit"
                                                                        class="dropdown-item cv-show-submit-btn">
                                                                        <x-svg.eye width="20" height="20" />
                                                                        {{ __('view') }}
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <button
                                                                    onclick="editResume({{ $resume->id }},'{{ $resume->name }}', '{{ $resume->file_size }}')"
                                                                    type="button" class="dropdown-item">
                                                                    <x-svg.pen-edit />
                                                                    {{ __('edit') }}
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <form
                                                                    action="{{ route('candidate.resume.delete', $resume->id) }}"
                                                                    method="POST" id="resumeForm">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="button" onclick="resumeDelete()"
                                                                        class="dropdown-item">
                                                                        <x-svg.trash-icon />
                                                                        {{ __('delete') }}
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            @endforeach

                                            <div class="resume-item add-resume" data-bs-toggle="modal"
                                                data-bs-target="#resumeModal">
                                                <div class="resume-icon">
                                                    <x-svg.plus-icon />
                                                </div>
                                                <div>
                                                    <h4 class="resume-title">{{ __('add_cv_resume') }}</h4>
                                                    <h6 class="resume-size">{{ __('browse_file_here_only') }} - pdf</h6>
                                                </div>
                                            </div>
                                        </div> --}}
                                    </div>

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


@endsection

@section('frontend_links')
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap-datepicker.min.css">
    <!-- >=>Leaflet Map<=< -->
    <x-map.leaflet.map_links />
    <x-map.leaflet.autocomplete_links />
    @include('map::links')
    <style>
        .ck-editor__editable_inline {
            min-height: 300px;
        }

        .w-100-percent {
            width: 100% !important;
        }

        #jobrole #basic-addon1 {
            width: 50px !important;
            margin-left: 28px !important;
        }

        .border-cutom {
            border-radius: 5px 0 0 5px !important;
        }

        .input-group-text-custom {
            max-height: 48px;
            padding: 12px;
            background-color: #e9ecef;
            border-radius: 0 5px 5px 0;
        }

        .has-badge-cutom {
            top: 34% !important;
        }
    </style>


    <style>
        .mymap {
            border-radius: 12px;
            z-index: 999;
        }
    </style>
@endsection

@section('frontend_scripts')

    @livewireScripts
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
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
    <script src="{{ asset('backend/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('frontend/assets/js/bootstrap-datepicker.min.js') }}"></script>
    @if (app()->getLocale() == 'ar')
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.ar.min.js
                                                                                                                                    ">
        </script>
    @endif

    <script>
        function previewImage(input, imageElementId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(imageElementId).src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Add event listeners for image preview
        document.getElementById('passportImageInput').addEventListener('change', function() {
            previewImage(this, 'passportImagePreview');
        });

        //init datepicker
        $("#available_id_date").attr("autocomplete", "off");


        $('#available_status').on('change', function() {
            availableStatus(this.value);
        });

        function availableStatus(status) {
            if (status == 'available_in') {
                $('#available_in_status').removeClass('d-none');
            } else {
                $('#available_in_status').addClass('d-none');
            }
        }


        function UploadMode(param) {
            if (param === 'photo') {
                $('#photo-uploadMode').removeClass('d-none');
                $('#photo-oldMode').addClass('d-none');
            } else {
                $('#banner-uploadMode').removeClass('d-none');
                $('#banner-oldMode').addClass('d-none');
            }
        }
        //init datepicker
        $("#date").attr("autocomplete", "off");
        //init datepicker
        $('#date').datepicker({
            format: 'dd-mm-yyyy',
            isRTL: "{{ app()->getLocale() == 'ar' ? true : false }}",
            language: "{{ app()->getLocale() }}",
        });
    </script>
    <script>
        $('#visibility').on('change', function() {
            $(this).submit();
        });
        $('#alert').on('change', function() {
            $(this).submit();
        });

        function AccountDelete() {
            if (confirm("Are you sure ??") == true) {
                $('#AccountDelete').submit();
            } else {
                return false;
            }
        }

        function resumeDelete() {
            if (confirm("Are you sure ?") == true) {
                $('#resumeForm').submit();
            } else {
                return false;
            }
        }

        function editResume(id, name, size) {
            $('#resume_id_input').val(id);
            $('#resume_name_input').val(name);
            $('#resume_file_size').html(size);
            $('#resumeEditModal').modal('show');
        }
        $('.cv-remove-image').on('click', function() {
            $('.resume-file-upload-input').replaceWith($('.resume-file-upload-input').clone());
            $('.resume-file-upload-content').hide();
            $('.cv-image-upload-wrap').show();
            $('.resume-file-upload-input').val('');
        })

        function resumeManageReadURL(input, type) {
            if (type == 'add') {
                var fileName = document.querySelector('#resume_add_input').files[0].name;
                var fileSize = document.querySelector('#resume_add_input').files[0].size / 1024 / 1024;
                var fileType = document.querySelector('#resume_add_input').files[0].type;
            } else {
                var fileName = document.querySelector('#resume_edit_input').files[0].name;
                var fileSize = document.querySelector('#resume_edit_input').files[0].size / 1024 / 1024;
                var fileType = document.querySelector('#resume_edit_input').files[0].type;
            }
            $('.resume_selected_file_name').html(fileName);
            $('.resume_selected_file_size').html(fileSize.toFixed(4));
            $('.resume_selected_file_type').html(fileType);
            if (input.files && input.files[0]) {
                console.log(input.className)
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (input.className === 'profile-file-upload-input') {
                        $('.profile-image-upload-wrap').hide();
                        $('.profile-file-upload-image').attr('src', e.target.result);
                        $('.profile-file-upload-content').show();
                        // $('.image-title').html(input.files[0].name);
                    }
                    if (input.className === 'banner-file-upload-input') {
                        $('.banner-image-upload-wrap').hide();
                        $('.banner-file-upload-image').attr('src', e.target.result);
                        $('.banner-file-upload-content').show();
                        // $('.image-title').html(input.files[0].name);
                    }
                    if (input.className === 'resume-file-upload-input') {
                        $('.cv-image-upload-wrap').hide();
                        $('.resume-file-upload-content.none').show();
                    }
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                $('.profile-remove-image').on('click', function() {
                    // console.log(this.className)
                    $('.profile-file-upload-input').replaceWith($('.profile-file-upload-input').clone());
                    $('.profile-file-upload-content').hide();
                    $('.profile-file-upload-image').attr('src', '');
                    $('.profile-image-upload-wrap').show();
                })
                $('.banner-remove-image').on('click', function() {
                    // console.log(this.className)
                    $('.banner-file-upload-input').replaceWith($('.banner-file-upload-input').clone());
                    $('.banner-file-upload-content').hide();
                    $('.banner-file-upload-image').attr('src', '');
                    $('.banner-image-upload-wrap').show();
                })
            }
        }
        setTimeout(function() {
            {{ session()->forget('type') }}
        }, 10000);
    </script>

    {{-- Leaflet  --}}
    {{-- @include('map::set-edit-leafletmap', ['lat' => $candidate->lat, 'long' => $candidate->long]) --}}


    <!-- ============== google map ========= -->
    <x-website.map.google-map-check />
    <script>

            const image =
                "https://gisgeography.com/wp-content/uploads/2018/01/map-marker-3-116x200.png";
            const beachMarker = new google.maps.Marker({
                draggable: true,
                position: {
                    lat: oldlat,
                    lng: oldlng
                },
                map,
                // icon: image
            });
            google.maps.event.addListener(map, 'click',
                function(event) {
                    $('.loader_position').removeClass('d-none');
                    $('.location_secion').addClass('d-none');

                    pos = event.latLng
                    beachMarker.setPosition(pos);
                    let lat = beachMarker.position.lat();
                    let lng = beachMarker.position.lng();
                    axios.post(
                        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${token}`
                    ).then((data) => {
                        if (data.data.error_message) {
                            toastr.error(data.data.error_message, 'Error!');
                            toastr.error('Your location is not set because of a wrong API key.', 'Error!');
                        }

                        const total = data.data.results.length;
                        let amount = '';
                        if (total > 1) {
                            amount = total - 2;
                        }
                        const result = data.data.results.slice(amount);
                        let country = '';
                        let region = '';
                        for (let index = 0; index < result.length; index++) {
                            const element = result[index];
                            if (element.types[0] == 'country') {
                                country = element.formatted_address;
                            }
                            if (element.types[0] == 'administrative_area_level_1') {
                                const str = element.formatted_address;
                                const first = str.split(',').shift()
                                region = first;
                            }
                        }
                        var form = new FormData();
                        form.append('lat', lat);
                        form.append('lng', lng);
                        form.append('country', country);
                        form.append('region', region);
                        form.append('exact_location', data.data.results[0].formatted_address);

                        setLocationSession(form);

                        $('.location_country').text(country);
                        $('.location_full_address').text(data.data.results[0].formatted_address ||
                            'No address found');
                        $('.loader_position').addClass('d-none');
                        $('.location_secion').removeClass('d-none');
                    })
                });
            google.maps.event.addListener(beachMarker, 'dragend',
                function() {
                    $('.loader_position').removeClass('d-none');
                    $('.location_secion').addClass('d-none');

                    let lat = beachMarker.position.lat();
                    let lng = beachMarker.position.lng();
                    axios.post(
                        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${token}`
                    ).then((data) => {
                        if (data.data.error_message) {
                            toastr.error(data.data.error_message, 'Error!');
                            toastr.error('Your location is not set because of a wrong API key.', 'Error!');
                        }

                        const total = data.data.results.length;
                        let amount = '';
                        if (total > 1) {
                            amount = total - 2;
                        }
                        const result = data.data.results.slice(amount);
                        let country = '';
                        let region = '';
                        for (let index = 0; index < result.length; index++) {
                            const element = result[index];
                            if (element.types[0] == 'country') {
                                country = element.formatted_address;
                            }
                            if (element.types[0] == 'administrative_area_level_1') {
                                const str = element.formatted_address;
                                const first = str.split(' ').shift()
                                region = first;
                            }
                        }
                        var form = new FormData();
                        form.append('lat', lat);
                        form.append('lng', lng);
                        form.append('country', country);
                        form.append('region', region);
                        form.append('exact_location', data.data.results[0].formatted_address);

                        setLocationSession(form);

                        $('.location_country').text(country);
                        $('.location_full_address').text(data.data.results[0].formatted_address ||
                            'No address found');
                        $('.loader_position').addClass('d-none');
                        $('.location_secion').removeClass('d-none');
                    })
                });
            // Search
            var input = document.getElementById('searchInput');
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

            let country_code = '{{ current_country_code() }}';
            if (country_code) {
                var options = {
                    componentRestrictions: {
                        country: country_code
                    }
                };
                var autocomplete = new google.maps.places.Autocomplete(input, options);
            } else {
                var autocomplete = new google.maps.places.Autocomplete(input);
            }

            autocomplete.bindTo('bounds', map);
            var infowindow = new google.maps.InfoWindow();
            var marker = new google.maps.Marker({
                map: map,
                anchorPoint: new google.maps.Point(0, -29)
            });
            autocomplete.addListener('place_changed', function() {
                infowindow.close();
                marker.setVisible(false);
                var place = autocomplete.getPlace();
                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }
            });
        }
        window.initMap = initMap;
    </script>
    <script>
        @php
            $link1 = 'https://maps.googleapis.com/maps/api/js?key=';
            $link2 = $setting->google_map_key;
            $Link3 = '&callback=initMap&libraries=places,geometry';
            $scr = $link1 . $link2 . $Link3;
        @endphp;
    </script>
    <script src="{{ $scr }}" async defer></script>
    <!-- =============== google map ========= -->
    <script type="text/javascript">
        $(document).ready(function() {
            $("[data-toggle=tooltip]").tooltip()
        })
    </script>

    <script>
        $('#pills-setting-tab').on('click', function() {
            setTimeout(() => {
                map.resize();
                leaflet_map.invalidateSize(true);
            }, 200);
        })
    </script>
    <script>
        $(".new-select").select2({ // minimumResultsForSearch: Infinity,
        });
    </script>
    <script type="text/javascript">
        // feature field
        function add_features_field() {
            $("#multiple_feature_part").append(`
        <div class="col-12 custom-select-padding">
            <div class="d-flex tw-items-center">
                <div class="d-flex mborder">
                    <div class="position-relative">
                        <select
                            class="w-100-p border-0 rt-selectactive-2 form-control" name="social_media[]">
                            <option value="" class="d-none" disabled selected>{{ __('select_one') }}</option>
                            <option value="facebook">{{ __('facebook') }}</option>
                            <option value="twitter">{{ __('twitter') }}</option>
                            <option value="instagram">{{ __('instagram') }}</option>
                            <option value="youtube">{{ __('youtube') }}</option>
                            <option value="linkedin">{{ __('linkedin') }}</option>
                            <option value="pinterest">{{ __('pinterest') }}</option>
                            <option value="reddit">{{ __('reddit') }}</option>
                            <option value="github">{{ __('github') }}</option>
                            <option value="other">{{ __('other') }}</option>
                        </select>
                    </div>
                    <div class="w-100">
                        <input class="border-0" type="url" name="url[]" id="" placeholder="{{ __('profile_link_url') }}...">
                    </div>
                </div>
                <div class="tw-ms-2">
                    <button class="tw-w-12 tw-h-12 tw-border-0 tw-rounded tw-bg-[#F1F2F4] tw-inline-flex tw-justify-center tw-items-center" type="button" id="remove_item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="#18191C" stroke-width="1.5" stroke-miterlimit="10"/>
                            <path d="M15 9L9 15" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15 15L9 9" stroke="#18191C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `);
    $(".rt-selectactive-2").select2({ // minimumResultsForSearch: Infinity,
});
        }

        $(document).on("click", "#remove_item", function() {
            $(this).parent().parent().parent('div').remove();
        });
    </script>
@endsection
