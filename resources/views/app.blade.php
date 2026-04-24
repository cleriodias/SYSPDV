<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $shareTitle = 'PDV: Padaria de Verdade';
            $shareDescription = 'Sistema completo para padarias: vendas, fiscal, financeiro, funcionarios e relatorios.';
            $shareImage = asset('images/share-card.png');
            $shareUrl = url('/');
        @endphp

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <meta name="description" content="{{ $shareDescription }}">
        <meta property="og:site_name" content="{{ $shareTitle }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $shareTitle }}">
        <meta property="og:description" content="{{ $shareDescription }}">
        <meta property="og:url" content="{{ $shareUrl }}">
        <meta property="og:image" content="{{ $shareImage }}">
        <meta property="og:image:secure_url" content="{{ $shareImage }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $shareTitle }}">
        <meta name="twitter:description" content="{{ $shareDescription }}">
        <meta name="twitter:image" content="{{ $shareImage }}">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/share-card.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- Scripts -->
        @routes
        <script>
            if (typeof Ziggy !== 'undefined' && typeof window !== 'undefined') {
                Ziggy.url = window.location.origin;
                Ziggy.port = null;
            }

            if (typeof window !== 'undefined' && typeof window.route === 'function') {
                const originalRoute = window.route;

                window.route = function route(name, params, absolute = false) {
                    return originalRoute(name, params, absolute);
                };
            }
        </script>
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
