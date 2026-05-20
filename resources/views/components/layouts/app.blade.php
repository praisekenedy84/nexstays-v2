@props([
    'activeNav' => 'dashboard',
    'title' => null,
    'subtitle' => null,
    'showHeaderSearch' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? "{$title} — NexStay" : 'NexStay' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen">
        <x-layout.sidebar :active="$activeNav" />

        <div class="flex min-w-0 flex-1 flex-col">
            @isset($header)
                {{ $header }}
            @else
                <x-layout.header
                    :title="$title ?? ''"
                    :subtitle="$subtitle"
                    :show-search="$showHeaderSearch"
                />
            @endisset

            <main class="flex-1 overflow-auto px-6 pb-8 pt-2 lg:px-8">
                <x-ui.flash />
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
