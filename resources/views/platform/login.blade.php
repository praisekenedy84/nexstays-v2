<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platform Admin — NexStay</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-900 px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <span class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white ring-1 ring-white/20">NS</span>
            <h1 class="text-xl font-bold text-white">NexStay Platform</h1>
            <p class="mt-1 text-sm text-slate-400">Platform administration</p>
        </div>

        @if ($errors->any())
            <ul class="mb-4 rounded-xl bg-red-900/50 px-4 py-3 text-sm text-red-300 ring-1 ring-red-700/50">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('platform.login.store') }}" class="space-y-4 rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block text-xs font-medium text-slate-400">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-xs font-medium text-slate-400">Password</label>
                <input id="password" type="password" name="password" required
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" value="1" class="rounded border-white/20 bg-white/5 text-indigo-500">
                Remember me
            </label>
            <button type="submit"
                class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                Sign in
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-600">
            <a href="{{ route('tenant.login') }}" class="hover:text-slate-400 transition">← Hotel staff login</a>
        </p>
    </div>
</body>
</html>
