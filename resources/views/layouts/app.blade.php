<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div id="generation-bar" class="hidden fixed top-0 inset-x-0 z-50 bg-indigo-600 text-white text-sm">
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center gap-3">
                <span id="generation-bar-label">자동 생성 중...</span>
                <div class="flex-1 h-1.5 bg-indigo-400 rounded overflow-hidden">
                    <div id="generation-bar-fill" class="h-full bg-white transition-all duration-500" style="width: 0%"></div>
                </div>
                <span id="generation-bar-count" class="tabular-nums"></span>
            </div>
        </div>

        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <script>
            (function () {
                var bar = document.getElementById('generation-bar');
                var fill = document.getElementById('generation-bar-fill');
                var count = document.getElementById('generation-bar-count');
                var label = document.getElementById('generation-bar-label');
                var wrapper = document.querySelector('.min-h-screen');
                var hideTimer = null;

                function setVisible(visible) {
                    bar.classList.toggle('hidden', ! visible);
                    wrapper.style.paddingTop = visible ? '2.5rem' : '0';
                }

                function poll() {
                    fetch('{{ route('admin.articles.generate.status') }}', { headers: { Accept: 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data.status === 'running') {
                                clearTimeout(hideTimer);
                                setVisible(true);
                                bar.classList.remove('bg-green-600');
                                bar.classList.add('bg-indigo-600');
                                label.textContent = '자동 생성 중...';
                                var pct = Math.min(100, Math.round((data.completed / data.target) * 100));
                                fill.style.width = pct + '%';
                                count.textContent = data.completed + ' / ' + data.target;
                            } else if (data.status === 'done') {
                                setVisible(true);
                                bar.classList.remove('bg-indigo-600');
                                bar.classList.add('bg-green-600');
                                label.textContent = '완료: ' + (data.message || '');
                                fill.style.width = '100%';
                                count.textContent = data.completed + ' / ' + data.target;
                                if (! hideTimer) {
                                    hideTimer = setTimeout(function () { setVisible(false); hideTimer = null; }, 15000);
                                }
                            } else {
                                setVisible(false);
                            }
                        })
                        .catch(function () {});
                }

                poll();
                setInterval(poll, 3000);
            })();
        </script>
    </body>
</html>
