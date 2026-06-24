@extends('backend.layouts.app')
@section('title')
    {{ __('industry_types_list') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-warning">
                    This list will be displayed on the company settings page as well as the profile setup page. The company
                    can select which industry his company was in from a list.
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-wrap justify-content-between">
                            <h3 class="card-title line-height-36">{{ __('industry_types_list') }}
                                ({{ $industrytypes ? count($industrytypes) : '0' }})</h3>
                            <button data-toggle="modal" data-target="#bulk_import_modal" class="btn bg-info"><i
                                    class="fas fa-plus mr-1"></i>
                                {{ __('bulk_import') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>{{ __('name') }}</th>
                                    <th>{{ __('Jobs By Industry') }}</th>
                                    <th>{{ __('Candidates By Country') }}</th>


                                    @if (userCan('industry_types.update') || userCan('industry_types.delete'))
                                        <th width="10%">{{ __('action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($industrytypes as $key => $industrytype)
                                    <tr>
                                        <td>
                                            <h5>{{ $industrytype->name }}</h5>
                                            <div>
                                                @foreach ($industrytype->translations as $translation)
                                                    @if (app()->getLocale() == $translation->locale)
                                                    @else
                                                        <span
                                                            class="d-block"><b>{{ getLanguageByCodeInLookUp($translation->locale, $app_language) }}</b>:
                                                            {{ $translation->name }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="update-visibility1"
                                                data-id="{{ $industrytype->id }}" data-column="candidates_by_industry"
                                                {{ $industrytype->industryTranslation->candidates_by_industry == 1 ? 'checked' : '' }}>
                                        </td>

                                        <td class="text-center">
                                            <input type="checkbox" class="update-visibility1"
                                                data-id="{{ $industrytype->id }}" data-column="jobs_by_industry"
                                                {{ $industrytype->industryTranslation->jobs_by_industry == 1 ? 'checked' : '' }}>
                                        </td>

                                        <td>
                                            @if (userCan('industry_types.update'))
                                                <a href="{{ route('industryType.edit', $industrytype->id) }}"
                                                    class="btn bg-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            @if (userCan('industry_types.delete'))
                                                <form action="{{ route('industryType.destroy', $industrytype->id) }}"
                                                    method="POST" class="d-inline">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button
                                                        onclick="return confirm('{{ __('are_you_sure_you_want_to_delete_this_item') }}');"
                                                        class="btn bg-danger"><i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            {{ __('no_data_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                @if (!empty($industryType) && userCan('industry_types.update'))
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title line-height-36">{{ __('edit') }} {{ __('industry_type') }}</h3>
                            <a href="{{ route('industryType.index') }}"
                                class="btn bg-primary float-right d-flex align-items-center justify-content-center"><i
                                    class="fas fa-plus mr-1"></i>{{ __('create') }}
                            </a>
                        </div>
                        {{-- @dd($industryType->name); --}}
                        <div class="card-body">
                            <form class="form-horizontal" action="{{ route('industryType.update', $industryType->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                @foreach ($app_language as $key => $language)
                                    @php
                                        $label = __('name') . ' ' . getLanguageByCode($language->code);
                                        $name = "name_{$language->code}";
                                        $code = $industryType->translations[$key]['locale'] ?? '';
                                        $data = $industryType->translations->where('locale', $language->code)->first();
                                        $value = $data ? $data->name : '';
                                    @endphp
                                    <div class="form-group">
                                        <x-forms.label :name="$label" for="name" :required="true" />
                                        <input id="name" type="text" name="{{ $name }}" placeholder="{{ __('name') }}" value="{{ $value }}"
                                            class="form-control @if ($errors->has($name)) is-invalid @endif">
                                        @if ($errors->has($name))
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $errors->first($name) }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                @endforeach

                                <div class="form-group">
                                    <label class="col-form-label">{{ __('image') }}
                                        <small class="text-danger">*</small>
                                    </label>
                                    <div class="">
                                        <input name="image" type="file" data-show-errors="true" data-width="100%" data-default-file="{{ asset('storage/' . $industryType->industryTranslation->image) }}"
                                            class="form-control dropify form-control-file @error('image') is-invalid @enderror border-0">
                                        @error('image')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-plus mr-1"></i>
                                        {{ __('save') }}
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                @endif
                @if (empty($industryType) && userCan('industry_types.create'))
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title line-height-36">{{ __('create') }} {{ __('industry_type') }}</h3>
                        </div>
                        <div class="card-body">
                            @if (userCan('industry_types.create'))
                            <form class="form-horizontal" action="{{ route('industryType.store') }}" method="POST" enctype="multipart/form-data">

                                    @csrf
                                    @foreach ($app_language as $key => $language)
                                        @php
                                            $label = __('name') . ' ' . getLanguageByCode($language->code);
                                            $name = "name_{$language->code}";
                                        @endphp
                                        <div class="form-group">
                                            <x-forms.label :name="$label" for="name" :required="true" />
                                            <input id="name" type="text" name="{{ $name }}"
                                                placeholder="{{ __('name') }}" value="{{ old('name') }}"
                                                class="form-control @if ($errors->has($name)) is-invalid @endif">
                                            @if ($errors->has($name))
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $errors->first($name) }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach


                                    <div class="form-group">
                                        <label class="col-form-label">{{ __('image') }}
                                            <small class="text-danger">*</small>
                                        </label>
                                        <div class="">
                                            <input name="image" type="file" data-show-errors="true" data-width="100%" data-default-file=""
                                                class="form-control dropify form-control-file @error('image') is-invalid @enderror border-0">
                                            @error('image')
                                                <span class="invalid-feedback d-block" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-plus mr-1"></i>
                                            {{ __('save') }}
                                        </button>
                                    </div>
                                </form>
                            @else
                                <p>{{ __('dont_have_permission') }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="modal fade" id="bulk_import_modal" tabindex="-1" role="dialog"
            aria-labelledby="bulk_import_modalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header ">
                        <h5 class="modal-title" id="exampleModalLongTitle">{{ __('bulk_import') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('admin.industry.type.bulk.import') }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-warning" role="alert">
                                Before importing, please download the example file and match the fields structure. If any
                                field data is missing, the system will generate it
                            </div>
                            <div class="form-group">
                                <label for="experience">{{ __('example_file') }}</label> <br>
                                <a href="/backend/dummy/industry_example.xlsx" target="_blank"
                                    class="btn btn-primary btn-block">
                                    <i class="fas fa-download"></i>
                                    {{ __('download') }} {{ __('example_file') }}
                                </a>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label for="experience">{{ __('choose_file') }}</label> <br>
                                <input type="file" class="form-control dropify" name="import_file"
                                    data-allowed-file-extensions='["csv", "xlsx","xls"]' accept=".csv,.xlsx,.xls"
                                    data-max-file-size="3M">
                                @error('import_file')
                                    <span class="invalid-feedback d-block" role="alert">{{ __($message) }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ __('close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('name')

    @endpush
    {{-- <script>
        $(document).on('change', '.update-visibility1', function() {
            console.log("aaaaaaa");
            var $checkbox = $(this); // Get the current checkbox
            var industryId = $checkbox.data('id'); // Get the ID from data-id attribute
            var column = $checkbox.data('column'); // Get the column name from data-column attribute
            var value = $checkbox.prop('checked') ? 1 : 0; // Determine if checkbox is checked (1) or unchecked (0)

            // Debugging logs
            console.log('Checkbox Element:', $checkbox);
            console.log('Industry ID:', industryId);
            console.log('Column:', column);
            console.log('Value:', value);

            // Validation: Ensure data-id and data-column are present
            if (!industryId || !column) {
                console.error('Missing data-id or data-column attributes in the checkbox.');
                alert('Invalid checkbox configuration.');
                return;
            }

            // Add a loading indicator next to the checkbox
            var loader = $('<span class="loader">Updating...</span>');
            $checkbox.closest('td').append(loader);

            // Ensure we only send one request per checkbox at a time
            if (!$checkbox.hasClass('updating')) {
                $checkbox.addClass('updating'); // Mark as updating

                $.ajax({
                    url: '{{ route('industry.toggleVisibility') }}', // Laravel route
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}', // CSRF token for security
                        id: industryId, // Industry ID
                        column: column, // Column name
                        value: value // New value (1 or 0)
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            console.log('Update successful:', response);
                        } else {
                            console.error('Unexpected response:', response);
                            alert('Failed to update visibility.');
                        }
                        $checkbox.removeClass('updating'); // Remove updating class
                        loader.remove(); // Remove loader
                    },
                    error: function(xhr) {
                        console.error('AJAX Error:', xhr.responseJSON || xhr);
                        alert('Error occurred while updating visibility.');
                        $checkbox.removeClass('updating');
                        loader.remove(); // Remove loader
                    }
                });
            }
        });
    </script> --}}

    <script src="{{ asset('backend') }}/plugins/dropify/js/dropify.min.js"></script>
    <script>
        $('.dropify').dropify();

        $('#target').iconpicker({
            align: 'left', // Only in div tag
            arrowClass: 'btn-danger',
            arrowPrevIconClass: 'fas fa-angle-left',
            arrowNextIconClass: 'fas fa-angle-right',
            cols: 16,
            footer: true,
            header: true,
            icon: '',
            iconset: 'flagicon',
            labelHeader: '{0} of {1} pages',
            labelFooter: '{0} - {1} of {2} icons',
            placement: 'bottom', // Only in button tag
            rows: 6,
            search: true,
            searchText: 'Search',
            selectedClass: 'btn-success',
            unselectedClass: ''
        });
        $('#target').on('change', function(e) {
            $('#icon').val(e.icon)
        });
        // dropify
        var drEvent = $('.dropify').dropify();
        drEvent.on('dropify.error.fileSize', function(event, element) {
            alert('Filesize error message!');
        });
        drEvent.on('dropify.error.imageFormat', function(event, element) {
            alert('Image format error message!');
        });
        // $('.search-control').val('');
    </script>
@endsection

@section('script')
<script>
    $(document).on('change', '.update-visibility1', function() {
        console.log("aaaaaaa");
        var $checkbox = $(this); // Get the current checkbox
        var industryId = $checkbox.data('id'); // Get the ID from data-id attribute
        var column = $checkbox.data('column'); // Get the column name from data-column attribute
        var value = $checkbox.prop('checked') ? 1 : 0; // Determine if checkbox is checked (1) or unchecked (0)

        // Debugging logs
        console.log('Checkbox Element:', $checkbox);
        console.log('Industry ID:', industryId);
        console.log('Column:', column);
        console.log('Value:', value);

        // Validation: Ensure data-id and data-column are present
        if (!industryId || !column) {
            console.error('Missing data-id or data-column attributes in the checkbox.');
            alert('Invalid checkbox configuration.');
            return;
        }

        // Add a loading indicator next to the checkbox
        var loader = $('<span class="loader">Updating...</span>');
        $checkbox.closest('td').append(loader);

        // Ensure we only send one request per checkbox at a time
        if (!$checkbox.hasClass('updating')) {
            $checkbox.addClass('updating'); // Mark as updating

            $.ajax({
                url: '{{ route('industry.toggleVisibility') }}', // Laravel route
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // CSRF token for security
                    id: industryId, // Industry ID
                    column: column, // Column name
                    value: value // New value (1 or 0)
                },
                success: function(response) {
                    if (response.status === 'success') {
                        console.log('Update successful:', response);
                    } else {
                        console.error('Unexpected response:', response);
                        alert('Failed to update visibility.');
                    }
                    $checkbox.removeClass('updating'); // Remove updating class
                    loader.remove(); // Remove loader
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr.responseJSON || xhr);
                    alert('Error occurred while updating visibility.');
                    $checkbox.removeClass('updating');
                    loader.remove(); // Remove loader
                }
            });
        }
    });
</script>
@endsection
