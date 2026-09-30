<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', config('app.tagline', ''))">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', '')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="ko_KR">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
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
</head>
<body class="bg-white text-gray-900 antialiased">
    <header class="border-b border-gray-100">
        <div class="max-w-5xl mx-auto px-4 py-5 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight">{{ config('app.name') }}</a>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-10">
        @yield('content')
    </main>

    <footer class="border-t border-gray-100 mt-16">
        <div class="max-w-5xl mx-auto px-4 py-8 text-sm text-gray-400 flex flex-wrap items-center gap-x-4 gap-y-2">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <a href="{{ route('about') }}" class="hover:text-gray-600">소개</a>
            <a href="{{ route('privacy') }}" class="hover:text-gray-600">개인정보처리방침</a>
        </div>
    </footer>
</body>
</html>
