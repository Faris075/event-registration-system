<nav x-data="{ open: false }" class="navbar">
    <div class="navbar-inner">
        {{-- Brand --}}
        <a href="{{ route('home') }}" class="navbar-brand">EventHub</a>

        {{-- Desktop links --}}
        <ul class="navbar-links" style="display:none;" id="nav-desktop">
            @auth
                <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a></li>
            @endauth
            <li><a href="{{ route('events.index') }}" class="{{ request()->routeIs('events.*') ? 'active' : '' }}">Events</a></li>
            @auth
                @if(auth()->user()->is_admin)
                    <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a></li>
                @endif
            @endauth
        </ul>

        {{-- Right side --}}
        <div style="display:flex;align-items:center;gap:0.5rem;">
            {{-- Dark / Light toggle --}}
            <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode" type="button">
                <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75 9.75 9.75 0 0 1 8.25 6a9.72 9.72 0 0 1 .75-3.752 9.753 9.753 0 0 0-9 9.749c0 5.385 4.365 9.75 9.75 9.75 4.282 0 7.93-2.765 9.002-6.745Z"/></svg>
                <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
            </button>
            @auth
                <span class="navbar-user">Welcome, {{ Auth::user()->name }}</span>
                <a href="{{ route('profile.edit') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('profile.*') ? 'active' : '' }}">Profile</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Log Out</button>
                </form>
            @else
                @if(Route::has('login'))
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Log In</a>
                @endif
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Sign Up</a>
                @endif
            @endauth
        </div>
    </div>

    {{-- Mobile: show nav links below --}}
    <div style="border-top:1px solid #f3f4f6;padding:0.5rem 1.5rem;display:flex;flex-wrap:wrap;gap:0.25rem;">
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
        @endauth
        <a href="{{ route('events.index') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('events.*') ? 'active' : '' }}">Events</a>
        @auth
            @if(auth()->user()->is_admin)
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
            @endif
            <a href="{{ route('profile.edit') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('profile.*') ? 'active' : '' }}">Profile</a>
        @endauth
    </div>
</nav>
