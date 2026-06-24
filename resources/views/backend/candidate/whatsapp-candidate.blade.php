@extends('backend.layouts.app')

@section('title')
    {{ __('Candidate List - Send WhatsApp Messages') }}
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container ">
        <div class="card shadow-sm">
            <div class="card-header bg-white text-dark">
                <h5 class="mb-0">{{ __(' WhatsApp Messages') }}</h5>
            </div>
            <div class="card-body">
                <form id="sendMessagesForm" class="needs-validation" novalidate>
                    <div class="form-group mb-3">
                        <label for="filter" class="form-label">{{ __('Filter By') }}</label>
                        <select id="filter" name="filter" class="form-select" onchange="toggleFilters()">
                            <option value="all">{{ __('All Candidates') }}</option>
                            <option value="title">{{ __('By Title') }}</option>
                        </select>
                    </div>

                    <div id="allFilter" class="filter-group mb-3">
                        <label for="candidate_ids" class="form-label">{{ __('Select Candidates') }}</label>
                        <select name="candidate_ids[]" id="candidate_ids" class="form-select" multiple>
                            @foreach ($candidates as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="titleFilter" class="filter-group mb-3" style="display: none;">
                        <label for="title" class="form-label">{{ __('Select Title') }}</label>
                        <select name="title" id="title" class="form-select">
                            @foreach ($titles as $title)
                                <option value="{{ $title->title }}">{{ $title->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="message" class="form-label">{{ __('Message') }}</label>
                        <textarea name="message" id="message" class="form-control" rows="4"
                            placeholder="{{ __('Enter your message') }}" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> {{ __('Send Message') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <script>
        function toggleFilters() {
            const filter = document.getElementById('filter').value;
            document.getElementById('allFilter').style.display = filter === 'all' ? 'block' : 'none';
            document.getElementById('titleFilter').style.display = filter === 'title' ? 'block' : 'none';
        }

        document.getElementById('sendMessagesForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            try {
                const response = await fetch('send-messages', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const result = await response.json();
                if (response.ok) {
                    alert(result.status);
                    e.target.reset();
                    toggleFilters();
                } else {
                    alert(result.message || 'Something went wrong!');
                }
            } catch (error) {
                alert('Error sending messages. Please try again later.');
            }
        });
    </script>
@endsection
