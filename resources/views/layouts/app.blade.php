<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'EventHub') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- Apply saved theme before first paint; default is light --}}
        <script>
            (function(){
                if(localStorage.getItem('theme')==='dark'){
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
    </head>
    <body>
        <div class="app-shell">
            @include('layouts.navigation')
            <main style="flex:1;">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
            <footer style="text-align:center;padding:1.5rem;font-size:0.8rem;color:var(--muted);border-top:1px solid var(--border);background:var(--surface);">
                &copy; {{ date('Y') }} EventHub &mdash; All rights reserved.
            </footer>
        </div>
    </body>
</html>
