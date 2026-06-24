<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">

<div class="container">

<a class="navbar-brand" href="{{ route('website.home') }}">
<img src="{{ $setting->dark_logo_url }}" height="60">
</a>

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="navbarMenu">

<ul class="navbar-nav me-auto">

@if(isset($public_menu_lists))
@foreach ($public_menu_lists as $menu)

<li class="nav-item">
<a class="nav-link
{{ urlMatch(url()->current(), url($menu['url'])) ? 'active' : '' }}"
href="{{ $menu['url'] }}">

{{ $menu['title'] ?? $menu['en_title'] }}

</a>
</li>

@endforeach
@endif

</ul>


<div class="d-flex gap-2">

@guest

<a href="{{ route('login') }}" class="btn btn-outline-primary">
Login
</a>

<a href="{{ route('register') }}" class="btn btn-primary">
Register
</a>

<a href="{{ route('company.job.create') }}" class="btn btn-success">
Post Job
</a>

@endguest


@auth

<div class="dropdown">

<a class="profile-btn" data-bs-toggle="dropdown">

<img src="{{ auth()->user()->candidate?->photo ?? auth()->user()->company?->logo_url }}"
class="profile-img">

</a>

<ul class="dropdown-menu">

<li>
<a class="dropdown-item" href="{{ route('user.dashboard') }}">
Dashboard
</a>
</li>

<li>
<a class="dropdown-item" href="{{ route('logout') }}"
onclick="event.preventDefault();document.getElementById('logout-form').submit();">
Logout
</a>
</li>

</ul>

</div>

@endauth

</div>

</div>

</div>

</nav>