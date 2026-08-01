<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System unavailable — NexStay</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-surface px-4">
    <div class="card w-full max-w-md p-8 text-center">
        <span class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-navy text-sm font-bold text-white">NS</span>

        <div class="mx-auto mb-5 flex size-12 items-center justify-center rounded-full bg-red-50 text-red-600">
            <x-icon name="alert" class="size-6" />
        </div>

        <h1 class="text-xl font-bold text-ink">System unavailable</h1>
        <p class="mt-2 text-sm leading-relaxed text-ink-muted">
            This property is currently suspended and cannot be accessed.
            Please contact the developer or NexStay support for assistance.
        </p>

        @if (! empty($propertyCode))
            <p class="mt-4 text-xs text-ink-subtle">
                Property code: <code class="rounded bg-slate-100 px-1.5 py-0.5 font-medium text-ink-muted">{{ $propertyCode }}</code>
            </p>
        @endif

        <a href="{{ route('tenant.login') }}" class="btn-secondary mt-8 inline-flex w-full">
            Back to sign in
        </a>
    </div>
</body>
</html>
