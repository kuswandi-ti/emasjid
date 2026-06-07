<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'EMasjid')) — {{ config('app.name', 'EMasjid') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="@yield('body-class', 'layout-fixed sidebar-expand-lg bg-body-tertiary')">

    @yield('body')

    {{-- Global confirm handler --}}
    <x-confirm-modal />

    @stack('scripts')

    <x-flash-messages />
</body>
</html>
