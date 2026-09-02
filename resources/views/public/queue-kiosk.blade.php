<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#092650">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $mppBranding['name'] }} — Ambil Nomor Antrean</title>
    @vite(['resources/css/app.css'])
    @include('kiosk.partials.styles')
</head>
<body>
    @include('kiosk.partials.catalog', ['interactionMode' => 'public'])
    @include('kiosk.partials.scripts')
</body>
</html>
