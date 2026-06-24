@extends('components.website.company.layout.app')

@section('title', __('Dashboard'))

@section('main')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-4">

    <!-- Header + Notifications -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold">Hello, {{ ucfirst(auth()->user()->name) }}</h4>
            <p class="text-muted mb-0">{{ __('Your dashboard overview') }}</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
                <i class="fas fa-bell fa-lg"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ $unreadNotifications ?? 0 }}
                </span>
            </div>
            <img src="{{ asset('images/Admin_logo.jpeg') }}" class="rounded-circle" width="50" alt="Avatar">
        </div>
    </div>

    <!-- KPI Cards with Trend Sparkline -->
    <div class="row g-3 mb-4">
        @php
            $metrics = [
                ['label'=>__('Open Jobs'), 'value'=>$openJobCount, 'trend'=>5, 'icon'=>'ph-suitcase-simple', 'color'=>'text-primary'],
                ['label'=>__('Pending Jobs'), 'value'=>$pendingJobCount, 'trend'=>-2, 'icon'=>'ph-hourglass', 'color'=>'text-warning'],
                ['label'=>__('Saved Candidates'), 'value'=>$savedCandidates, 'trend'=>8, 'icon'=>'ph-identification-card', 'color'=>'text-success'],
                ['label'=>__('Applicants'), 'value'=>$applicants, 'trend'=>12, 'icon'=>'ph-users', 'color'=>'text-info'],
            ];
        @endphp
        @foreach($metrics as $metric)
        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="fw-bold">{{ $metric['value'] }}</h5>
                        <small class="text-muted">{{ $metric['label'] }}</small>
                    </div>
                    <i class="{{ $metric['icon'] }} fs-2 {{ $metric['color'] }}"></i>
                </div>
                <small class="d-flex align-items-center">
                    @if($metric['trend'] > 0)
                        <i class="fas fa-arrow-up text-success me-1"></i>
                        {{ $metric['trend'] }}% since last week
                    @elseif($metric['trend'] < 0)
                        <i class="fas fa-arrow-down text-danger me-1"></i>
                        {{ abs($metric['trend']) }}% since last week
                    @else
                        <i class="fas fa-minus text-muted me-1"></i>
                        No change
                    @endif
                </small>
                <!-- Sparkline Chart -->
                <canvas id="sparkline{{ $loop->index }}" height="50"></canvas>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm p-4 h-100">
                <h6 class="fw-semibold mb-3">{{ __('Daily Applications') }}</h6>
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card shadow-sm p-4 h-100 text-center">
                <h6 class="fw-semibold mb-3">{{ __('Applications by Country') }}</h6>
                <canvas id="applicationsByCountryChart"></canvas>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card shadow-sm p-4 h-100 text-center">
                <h6 class="fw-semibold mb-3">{{ __('Gender Distribution') }}</h6>
                <canvas id="genderChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Jobs Table -->
    <div class="card shadow-sm p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>{{ __('Recent Jobs') }}</h5>
            <input type="text" class="form-control w-25" placeholder="Search Job...">
        </div>
        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Job') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Applications') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentJobs as $job)
                        <tr>
                            <td>{{ Str::limit($job->title, 40) }}</td>
                            <td>
                                <span class="badge {{ $job->status == 'active' ? 'bg-success' : ($job->status=='pending'? 'bg-primary' : 'bg-danger') }}">
                                    {{ __(ucfirst($job->status)) }}
                                </span>
                            </td>
                            <td>{{ $job->applied_jobs_count }}</td>
                            <td class="d-flex gap-2">
                                <a href="{{ route('company.job.application', ['job' => $job->id]) }}" class="btn btn-sm btn-outline-primary">{{ __('View Applications') }}</a>
                                <a href="{{ route('company.promote', $job->slug) }}" class="btn btn-sm btn-outline-warning">{{ __('Promote') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">{{ __('No Jobs Found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const dates = @json($chartDates);
    const counts = @json($chartCounts);
    const countryLabels = @json($countryNames);
    const countryData = @json($countryApplications);
    const genderLabels = @json($genderLabels);
    const genderData = @json($genderCounts);

    // Daily Applications
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: { labels: dates, datasets:[{label:'Daily Applications', data:counts, borderColor:'#6366F1', backgroundColor:'rgba(99,102,241,0.2)', tension:0.4, fill:true}]},
        options: { responsive:true, plugins:{ legend:{ display:false } } }
    });

    // Applications by Country
    new Chart(document.getElementById('applicationsByCountryChart'), {
        type:'pie', data:{labels:countryLabels,datasets:[{data:countryData,backgroundColor:['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF']}]},
        options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
    });

    // Gender Chart
    new Chart(document.getElementById('genderChart'), {
        type:'doughnut', data:{labels:genderLabels,datasets:[{data:genderData,backgroundColor:['#1E3A8A','#22D3EE'],borderWidth:1}]},
        options:{ responsive:true, cutout:'70%', plugins:{ legend:{ display:true, position:'bottom' } } }
    });

    // Sparkline Charts for KPI cards
    @foreach($metrics as $index => $metric)
        new Chart(document.getElementById('sparkline{{ $index }}'), {
            type:'line',
            data:{ labels:dates, datasets:[{data:counts, borderColor:'#6366F1', backgroundColor:'rgba(99,102,241,0.1)', fill:true, tension:0.4, pointRadius:0}] },
            options:{ responsive:true, plugins:{ legend:{ display:false } }, scales:{x:{display:false},y:{display:false}} }
        });
    @endforeach
</script>
@endsection