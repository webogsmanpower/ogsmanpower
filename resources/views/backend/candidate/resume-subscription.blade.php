@extends('backend.layouts.app')
@section('title')
    {{ __('create_candidate') }}
@endsection
@section('content')
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
    <h2>Payments</h2>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th scope="col">Language</th>
                <th scope="col">Payment Method</th>
                <th scope="col">Status</th>
                <th scope="col">Action</th> <!-- Added Action column -->
            </tr>
        </thead>
        <tbody>
            @foreach ($subscription as $sub)
            <tr>
                <td>{{ $languages[$sub->language_code] ?? $sub->language_code }}</td>
                <td>{{ $sub->payment_method }}</td>
                <td>{{ $sub->status }}</td>
                <td>
                    <!-- Approve Button -->
                    <form action="{{ route('approve.resume.subscription', $sub->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                    </form>

                    <!-- Delete Button -->
                    <form action="{{ route('delete.resume.subscription', $sub->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure you want to delete this subscription?');">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
