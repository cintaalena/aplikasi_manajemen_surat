<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- PWA Meta -->
        <meta name="application-name" content="Surat Fatubesi">
        <meta name="description" content="Sistem manajemen surat keterangan Kelurahan Fatubesi">
        <meta name="theme-color" content="#2E7D32">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Surat Fatubesi">
        <meta name="msapplication-TileColor" content="#2E7D32">
        <meta name="msapplication-TileImage" content="/images/icons/icon-144x144.png">

        <!-- Manifest -->
        <link rel="manifest" href="/manifest.json">

        <!-- Apple Touch Icons -->
        <link rel="apple-touch-icon" href="/images/icons/icon-152x152.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
        <link rel="apple-touch-icon" sizes="192x192" href="/images/icons/icon-192x192.png">

        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/icon-72x72.png">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link href="/css/figtree.css" rel="stylesheet" />

        <!-- Scripts -->
        @routes(nonce: $cspNonce ?? null)
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
