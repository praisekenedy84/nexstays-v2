<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — NexStay</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-surface px-4">
    <div class="card w-full max-w-md p-8">
        <div class="mb-8 text-center">
            <span class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-navy text-sm font-bold text-white">NS</span>
            <h1 class="text-xl font-bold text-ink">NexStay HBMS</h1>
            <p class="mt-1 text-sm text-ink-muted">
                {{ tenant('id') ? 'Property: '.tenant('id') : 'Hotel back office' }}
            </p>
        </div>

        @if ($errors->any())
            <ul class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('tenant.login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="property_code" class="mb-1.5 block text-xs font-medium text-ink-muted">Property code</label>
                <input id="property_code" type="text" name="property_code" value="{{ old('property_code') }}"
                    required autofocus autocomplete="off" spellcheck="false"
                    placeholder="e.g. demo"
                    class="input-field">
            </div>
            <div>
                <label for="email" class="mb-1.5 block text-xs font-medium text-ink-muted">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="input-field">
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-xs font-medium text-ink-muted">Password</label>
                <x-ui.password-input id="password" name="password" required autocomplete="current-password" class="input-field" />
            </div>
            <label class="flex items-center gap-2 text-sm text-ink-muted">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-primary focus:ring-primary/30">
                Remember me
            </label>
            <button type="submit" class="btn-primary w-full">Sign in</button>
        </form>

        @if (config('nexstay.demo.tenant_id'))
            <div class="mt-6 rounded-lg bg-slate-50 px-4 py-3 text-center text-xs text-ink-muted">
                <span class="font-medium">Demo credentials</span><br>
                Code: <code class="rounded bg-white px-1">{{ config('nexstay.demo.tenant_id') }}</code>
                &nbsp;·&nbsp; Email: <code class="rounded bg-white px-1">{{ config('nexstay.demo.admin_email') }}</code>
                &nbsp;·&nbsp; Password: <code class="rounded bg-white px-1">{{ config('nexstay.demo.password') }}</code>
            </div>
        @endif
    </div>
</body>
</html>
