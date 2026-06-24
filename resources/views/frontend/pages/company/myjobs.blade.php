@extends('components.website.company.layout.app')

@section('title', __('my_jobs'))

@section('main')
    <style>
        /* ===== Common Styles ===== */
        .job-listing-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            background: #ffffff;
            transition: box-shadow 0.3s ease;
        }
        .job-listing-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .job-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #111827;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .job-title:hover {
            color: #1d4ed8;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }

        /* ===== Actions ===== */
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 12px;
            color: #374151;
            text-decoration: none;
            margin-right: 12px;
            transition: transform 0.2s ease, color 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.1);
            color: #1d4ed8;
        }
        .action-btn i {
            font-size: 18px;
            margin-bottom: 4px;
        }

        /* ===== Dropdown Menu ===== */
        .dropdown-menu {
            min-width: 160px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            padding: 0.5rem 0;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 1rem;
            color: #374151;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .dropdown-item:hover {
            background-color: #f0f4f8;
            color: #1d4ed8;
        }

        /* ===== Table ===== */
        .job-table th, .job-table td {
            padding: 12px 8px;
            text-align: left;
        }
        .job-table th { font-weight: 600; color: #111827; }

        /* ===== Responsive ===== */
        .desktop-view { display: none; }
        .mobile-view { display: block; }

        @media(min-width: 768px) {
            .desktop-view { display: block; }
            .mobile-view { display: none; }
        }
    </style>

    {{-- Desktop Table View --}}
    <div class="desktop-view container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>{{ __('my_jobs') }} <span class="text-gray-400">({{ $myJobs->total() }})</span></h3>
            <form id="status-filter" action="{{ route('company.myjob') }}" method="GET" class="d-flex gap-2">
                <select name="status" class="form-select">
                    <option value="">{{ __('all') }}</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('active') }}</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('pending') }}</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>{{ __('expired') }}</option>
                </select>
                <select name="apply_on" class="form-select">
                    <option value="">{{ __('all') }}</option>
                    <option value="app" {{ request('apply_on') == 'app' ? 'selected' : '' }}>{{ __('app') }}</option>
                    <option value="email" {{ request('apply_on') == 'email' ? 'selected' : '' }}>{{ __('email') }}</option>
                    <option value="custom_url" {{ request('apply_on') == 'custom_url' ? 'selected' : '' }}>{{ __('custom_url') }}</option>
                </select>
            </form>
        </div>

        <table class="table job-table table-hover">
            <thead>
                <tr>
                    <th>{{ __('job') }}</th>
                    <th>{{ __('status') }}</th>
                    <th>{{ __('applications') }}</th>
                    <th>{{ __('action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($myJobs as $job)
                    <tr>
                        <td>
                            <a href="{{ route('website.job.details', $job->slug) }}" class="job-title">
                                {{ $job->title }}
                            </a>
                            <div class="mt-1 text-gray-500" style="font-size:12px;">
                                {{ ucfirst($job->job_type->name) }} | {{ $job->days_remaining }} {{ __('remaining') }}
                            </div>
                        </td>
                        <td>
                            @if($job->status == 'active')
                                <span class="badge badge-success">{{ __('active') }}</span>
                            @elseif($job->status == 'pending')
                                <span class="badge badge-warning">{{ __('pending') }}</span>
                            @else
                                <span class="badge badge-danger">{{ __('job_expire') }}</span>
                            @endif
                        </td>
                        <td>{{ $job->applied_jobs_count }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ __('actions') }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('website.job.details', $job->slug) }}">
                                            <i class="fas fa-info-circle"></i> {{ __('view_details') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('company.job.application', ['job'=>$job->id]) }}">
                                            <i class="fas fa-file-alt"></i> {{ __('view_applications') }}
                                        </a>
                                    </li>
                                    @if($job->status == 'active')
                                        <li>
                                            <form method="POST" action="{{ route('company.job.make.expire', $job->id) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-times-circle"></i> {{ __('make_it_expire') }}
                                                </button>
                                            </form>
                                        </li>
                                    @elseif($job->status == 'expired')
                                        <li>
                                            <form method="POST" action="{{ route('company.job.make.active', $job->id) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-check-circle"></i> {{ __('make_it_active') }}
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ route('company.job.edit', $job->slug) }}">
                                            <i class="fas fa-edit"></i> {{ __('edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('company.promote', $job->slug) }}">
                                            <i class="fas fa-bullhorn"></i> {{ __('promote') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('company.clone', $job->slug) }}">
                                            <i class="fas fa-copy"></i> {{ __('clone') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-website.not-found /></td></tr>
                @endforelse
            </tbody>
        </table>

        @if($myJobs->hasPages())
            <div class="mt-4">
                {{ $myJobs->links('vendor.pagination.frontend') }}
            </div>
        @endif
    </div>

    {{-- Mobile Card View --}}
    <div class="mobile-view container mt-3">
        <h4>{{ __('my_jobs') }} <span class="text-gray-400">({{ $myJobs->total() }})</span></h4>
        @forelse($myJobs as $job)
            <div class="job-listing-card">
                <a href="{{ route('website.job.details', $job->slug) }}" class="job-title mb-2 d-block">{{ $job->title }}</a>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ ucfirst($job->job_type->name) }}</span>
                    <span>{{ $job->days_remaining }} {{ __('remaining') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    @if($job->status == 'active')
                        <span class="badge badge-success">{{ __('active') }}</span>
                    @elseif($job->status == 'pending')
                        <span class="badge badge-warning">{{ __('pending') }}</span>
                    @else
                        <span class="badge badge-danger">{{ __('job_expire') }}</span>
                    @endif
                    <span>{{ $job->applied_jobs_count }} {{ __('applications') }}</span>
                </div>
                <div class="dropdown mt-2">
                    <button class="btn btn-outline-secondary btn-sm w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ __('actions') }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('website.job.details', $job->slug) }}">
                                <i class="fas fa-info-circle"></i> {{ __('view_details') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('company.job.application', ['job'=>$job->id]) }}">
                                <i class="fas fa-file-alt"></i> {{ __('view_applications') }}
                            </a>
                        </li>
                        @if($job->status == 'active')
                            <li>
                                <form method="POST" action="{{ route('company.job.make.expire', $job->id) }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-times-circle"></i> {{ __('make_it_expire') }}
                                    </button>
                                </form>
                            </li>
                        @elseif($job->status == 'expired')
                            <li>
                                <form method="POST" action="{{ route('company.job.make.active', $job->id) }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-check-circle"></i> {{ __('make_it_active') }}
                                    </button>
                                </form>
                            </li>
                        @endif
                        <li>
                            <a class="dropdown-item" href="{{ route('company.job.edit', $job->slug) }}">
                                <i class="fas fa-edit"></i> {{ __('edit') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('company.promote', $job->slug) }}">
                                <i class="fas fa-bullhorn"></i> {{ __('promote') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('company.clone', $job->slug) }}">
                                <i class="fas fa-copy"></i> {{ __('clone') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        @empty
            <x-website.not-found />
        @endforelse
    </div>
@endsection

@section('script')
<script>
    $('#status-filter').on('change', function() { this.submit(); });
</script>
@endsection