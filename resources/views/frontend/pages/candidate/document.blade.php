{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.candidate.layout.app')

@section('title')
    {{ __('profile') }}
@endsection
@section('main')

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-lg-9">
                    <div class="dashboard-right">
                        <div class="cadidate-dashboard-tabs candidate ">
                            <div >
                                <div>
                                    <div class="tw-flex rt-mb-32 lg:tw-mt-0 tw-items-center tw-justify-between">
                                        <h3 class="f-size-18 tw-flex-shrink-0 lh-1 m-0">{{ __('Documents') }}</h3>
                                    </div>
                                    <form id="attachmentForm"
                                        action="{{ route('candidate.settingUpdate') }}" method="POST"
                                        enctype="multipart/form-data">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="type" value="documents">

                                        <div class="row">

                                            <!-- Passport Image Section -->
                                            <div class="form-group">
                                                <label>Passport Image</label><small style="color: red">* (
                                                    {{ __('Ratio') }} 4:3 )</small>
                                                <div class="custom-file">
                                                    <input type="file" name="passport_image" id="passportImageInput"
                                                        class="custom-file-input"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label" for="passportImageInput">Choose
                                                        File</label>
                                                </div>
                                                <center class="pt-4">
                                                    <img style="height: 200px; border: 1px solid; border-radius: 10px;"
                                                        id="passportImagePreview"
                                                        src="{{ isset($attachments) && $attachments->passport_image ? asset('storage/candidates/' . $attachments->passport_image) : asset('images/candidates/img1.jpg') }}"
                                                        alt="passport-image">
                                                </center>
                                            </div>

                                            <!-- CNIC FRONt -->
                                            <div class="form-group">
                                                <label>CNIC Front</label><small style="color: red">* (
                                                    {{ __('Ratio') }} 4:3 )</small>
                                                <div class="custom-file">
                                                    <input type="file" name="cnic_front" id="cnicFrontImageInput"
                                                        class="custom-file-input"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label" for="cnicFrontImageInput">Choose
                                                        File</label>
                                                </div>
                                                <center class="pt-4">
                                                    <img style="height: 200px; border: 1px solid; border-radius: 10px;"
                                                        id="cnicFrontPreview"
                                                        src="{{ isset($attachments) && $attachments->cnic_front ? asset('storage/candidates/' . $attachments->cnic_front) : asset('images/candidates/img1.jpg') }}"
                                                        alt="cnic-front">
                                                </center>
                                            </div>
                                            <!-- CNIC Back -->

                                            <div class="form-group">
                                                <label>CNIC Back</label><small style="color: red">* (
                                                    {{ __('Ratio') }} 4:3 )</small>
                                                <div class="custom-file">
                                                    <input type="file" name="cnic_back" id="cnicBackImageInput"
                                                        class="custom-file-input"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label" for="cnicBackImageInput">Choose
                                                        File</label>
                                                </div>
                                                <center class="pt-4">
                                                    <img style="height: 200px; border: 1px solid; border-radius: 10px;"
                                                        id="cnicBackPreview"
                                                        src="{{ isset($attachments) && $attachments->cnic_back ? asset('storage/candidates/' . $attachments->cnic_back) : asset('images/candidates/img1.jpg') }}"
                                                        alt="cnic-back">
                                                </center>
                                            </div>
                                            {{-- Police Character Certificate --}}
                                            <div class="form-group">
                                                <label>Police Character Certificate</label><small style="color: red">* (
                                                    {{ __('Ratio') }} 4:3 )</small>
                                                <div class="custom-file">
                                                    <input type="file" name="police_character_certificate" id="pPCImageInput"
                                                        class="custom-file-input"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label" for="pPCImageInput">Choose
                                                        File</label>
                                                </div>
                                                <center class="pt-4">
                                                    <img style="height: 200px; border: 1px solid; border-radius: 10px;"
                                                        id="pPCPreview"
                                                        src="{{ isset($attachments) && $attachments->police_character_certificate ? asset('storage/candidates/' . $attachments->police_character_certificate) : asset('images/candidates/img1.jpg') }}"
                                                        alt="Police-character-certificate-image">
                                                </center>
                                            </div>
                                            {{-- Medical --}}
                                            <div class="form-group">
                                                <label>Medical</label><small style="color: red">* (
                                                    {{ __('Ratio') }} 4:3 )</small>
                                                <div class="custom-file">
                                                    <input type="file" name="medical" id="medicalImageInput"
                                                        class="custom-file-input"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label" for="medicalImageInput">Choose
                                                        File</label>
                                                </div>
                                                <center class="pt-4">
                                                    <img style="height: 200px; border: 1px solid; border-radius: 10px;"
                                                        id="medicalPreview"
                                                        src="{{ isset($attachments) && $attachments->medical ? asset('storage/candidates/' . $attachments->medical) : asset('images/candidates/img1.jpg') }}"
                                                        alt="medical-image">
                                                </center>
                                            </div>
                                            {{-- NAVTEC Report --}}
                                            <div class="form-group">
                                                <label>NAVTEC Report</label><small style="color: red">* (
                                                    {{ __('Ratio') }} 4:3 )</small>
                                                <div class="custom-file">
                                                    <input type="file" name="navtec_report" id="navtecImageInput"
                                                        class="custom-file-input"
                                                        accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    <label class="custom-file-label" for="navtecImageInput">Choose
                                                        File</label>
                                                </div>
                                                <center class="pt-4">
                                                    <img style="height: 200px; border: 1px solid; border-radius: 10px;"
                                                        id="navtecPreview"
                                                        src="{{ isset($attachments) && $attachments->navtec_report ? asset('storage/candidates/' . $attachments->navtec_report) : asset('images/candidates/img1.jpg') }}"
                                                        alt="navtec-report">
                                                </center>
                                            </div>
                                            @if (isset($attachments))
                                                <div class="col-lg-12 mt-4">
                                                    <button type="submit" class="btn btn-primary">
                                                        {{ __('Update Documents') }}
                                                    </button>
                                                </div>
                                            @else
                                                <div class="col-lg-12 mt-4">
                                                    <button type="submit" class="btn btn-primary">
                                                        {{ __('Upload Documents') }}
                                                    </button>
                                                </div>
                                            @endif


                                        </div>
                                    </form>
                                </div>



                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
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

        .mymap {
            border-radius: 12px;
            z-index: 999;
        }

        @media (max-width: 768px) {
            .btn {
                line-height: 18px !important;
                padding: 10px 10px 10px !important;
                border-radius: 4px !important;
                font-size: 12px !important;

            }
        }

        .slider {
            width: 100%;
            margin: 20px 0;
        }

        .salary-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
    </style>
@endsection

@section('frontend_scripts')
    @livewireScripts



    <script>
        // Function to display image preview for a given input and image element
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

        document.getElementById('cnicFrontImageInput').addEventListener('change', function() {
            previewImage(this, 'cnicFrontPreview');
        });
        document.getElementById('cnicBackImageInput').addEventListener('change', function() {
            previewImage(this, 'cnicBackPreview');
        });

        document.getElementById('pPCImageInput').addEventListener('change', function() {
            previewImage(this, 'pPCPreview');
        }); document.getElementById('medicalImageInput').addEventListener('change', function() {
            previewImage(this, 'medicalPreview');
        }); document.getElementById('navtecImageInput').addEventListener('change', function() {
            previewImage(this, 'navtecPreview');
        });
    </script>





    <!-- =============== google map ========= -->

@endsection
