<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <script src="{{ asset('js/portal.js') }}" defer></script>
    @stack('styles')
    @stack('scripts')
</head>
<body>
    {{ $slot }}
</body>
</html>
