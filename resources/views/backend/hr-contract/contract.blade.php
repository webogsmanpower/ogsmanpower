@extends('backend.layouts.app')
@section('title')
    {{ __('Contract') }}
@endsection
@section('content')
<div class="container">
    <h1>Contract</h1>



    <form action="{{ route('contracts.update', $contract->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- <div class="form-group">
            <label for="content">Contract Content</label>
            <textarea name="content" id="content" rows="10" class="form-control">{{ $contract->content }}</textarea>
        </div> --}}
        <div class="row">
            <div class="form-group col-md-12">
                <x-forms.label name="Contract Content" :required="false" />
                <textarea id="image_ckeditor" rows="10" name="content" placeholder="{{ __('Detils') }}"
                class="form-control">{{ old('bio', $contract->content) }}</textarea>
            </div>

        </div>

        <button type="submit" class="btn btn-primary mt-3">Update Contract</button>
    </form>
</div>
@endsection
