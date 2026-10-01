<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', config('app.tagline', ''))">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', '')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'ko_KR' }}">
    <meta name="twitter:title" content="@yield('title', config('app.name'))">
    <meta name="twitter:description" content="@yield('meta_description', '')">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="@yield('og_image')">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }}" href="{{ route('sitemap') }}">
    @if (config('services.google.site_verification'))
        <meta name="google-site-verification" content="{{ config('services.google.site_verification') }}">
    @endif
    @if (config('services.naver.site_verification'))
        <meta name="naver-site-verification" content="{{ config('services.naver.site_verification') }}">
    @endif
    @if (config('services.google.adsense_client'))
        <meta name="google-adsense-account" content="{{ config('services.google.adsense_client') }}">
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ config('services.google.adsense_client') }}" crossorigin="anonymous"></script>
    @endif
    @vite(['resources/css/app.css'])
    @stack('head')
    <style>
        #translate-btn {
            display: inline-block; background: #fff; border: 1px solid #e5e7eb; border-radius: 4px;
            padding: 2px 8px; font-size: 12px; color: #374151; text-decoration: none;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var weatherEl = document.getElementById('topbar-weather');
            var fxEl = document.getElementById('topbar-fx');

            function weatherIcon(code) {
                if (code === 0) return '☀️';
                if ([1, 2, 3].indexOf(code) !== -1) return '⛅';
                if ([45, 48].indexOf(code) !== -1) return '🌫️';
                if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82].indexOf(code) !== -1) return '🌧️';
                if ([71, 73, 75, 77, 85, 86].indexOf(code) !== -1) return '❄️';
                if ([95, 96, 99].indexOf(code) !== -1) return '⛈️';
                return '🌡️';
            }

            function loadWeather(lat, lon, label) {
                fetch('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon + '&current=temperature_2m,weather_code&temperature_unit=fahrenheit')
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d.current) return;
                        var t = Math.round(d.current.temperature_2m);
                        weatherEl.textContent = weatherIcon(d.current.weather_code) + ' ' + label + ' ' + t + '°F';
                    })
                    .catch(function () {});
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (pos) { loadWeather(pos.coords.latitude.toFixed(2), pos.coords.longitude.toFixed(2), '내 위치'); },
                    function () { loadWeather(33.749, -84.388, '애틀랜타'); },
                    { timeout: 4000 }
                );
            } else {
                loadWeather(33.749, -84.388, '애틀랜타');
            }

            fetch('https://api.frankfurter.dev/v1/latest?from=USD&to=KRW')
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var rate = d.rates && d.rates.KRW;
                    if (rate) fxEl.textContent = '💱 $1 = ' + Math.round(rate).toLocaleString() + '원';
                })
                .catch(function () {});
        });
    </script>
</head>
<body class="bg-white text-gray-900 antialiased">
    <div class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500">
        <div class="max-w-6xl mx-auto px-4 py-1.5 flex items-center justify-end gap-4">
            <span id="topbar-weather"></span>
            <span id="topbar-fx"></span>
            {{-- EN/한국어 toggle temporarily removed — translation backend on hold pending a reliable API (see App\Services\Translator). --}}
        </div>
    </div>

    <header class="border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 py-5 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight">
                <x-application-logo class="h-7 w-7 fill-current text-gray-800" />
                {{ config('app.name') }}
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-10">
        @yield('content')
    </main>

    <footer class="border-t border-gray-100 mt-16">
        <div class="max-w-6xl mx-auto px-4 py-8 text-sm text-gray-400 flex flex-wrap items-center gap-x-4 gap-y-2">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('site.all_rights_reserved') }}</span>
            <a href="{{ route('about') }}" class="hover:text-gray-600">{{ __('site.about') }}</a>
            <a href="{{ route('privacy') }}" class="hover:text-gray-600">{{ __('site.privacy') }}</a>
        </div>
    </footer>
</body>
</html>
