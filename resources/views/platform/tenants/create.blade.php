<x-platform.layout title="Provision hotel" activeNav="tenants">

    <div class="mb-6">
        <a href="{{ route('platform.tenants.index') }}" class="text-sm text-slate-400 hover:text-white transition">← Back to hotels</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-xl bg-red-900/40 px-4 py-3 ring-1 ring-red-700/50">
            <ul class="space-y-1 text-sm text-red-300">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-lg rounded-2xl bg-slate-900 p-6 ring-1 ring-white/10">
        <p class="mb-6 text-sm text-slate-400">
            Provisioning creates the hotel's database schema, runs all migrations, seeds roles &amp; permissions, and creates the first GM account.
        </p>

        <form method="POST" action="{{ route('platform.tenants.store') }}" class="space-y-5">
            @csrf

            <fieldset class="space-y-4">
                <legend class="text-xs font-semibold uppercase tracking-wider text-slate-500">Property</legend>

                <div>
                    <label for="property_code" class="mb-1.5 block text-xs font-medium text-slate-400">
                        Property code <span class="text-slate-500">(unique ID, lowercase, no spaces)</span>
                    </label>
                    <input id="property_code" type="text" name="property_code" value="{{ old('property_code') }}"
                        required autofocus autocomplete="off" spellcheck="false"
                        placeholder="e.g. grandpalace"
                        pattern="[a-z0-9_]+"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                </div>

                <div>
                    <label for="property_name" class="mb-1.5 block text-xs font-medium text-slate-400">Hotel name</label>
                    <input id="property_name" type="text" name="property_name" value="{{ old('property_name') }}"
                        required placeholder="e.g. Grand Palace Hotel"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                </div>
            </fieldset>

            <fieldset class="space-y-4 border-t border-white/5 pt-5">
                <legend class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">First administrator (General Manager)</legend>

                <div>
                    <label for="admin_name" class="mb-1.5 block text-xs font-medium text-slate-400">Full name</label>
                    <input id="admin_name" type="text" name="admin_name" value="{{ old('admin_name') }}"
                        required placeholder="e.g. Alice Mwamba"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                </div>

                <div>
                    <label for="admin_username" class="mb-1.5 block text-xs font-medium text-slate-400">Username</label>
                    <input id="admin_username" type="text" name="admin_username" value="{{ old('admin_username') }}"
                        required placeholder="e.g. gm" pattern="[a-z0-9_.-]+" spellcheck="false"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                </div>

                <div>
                    <label for="admin_email" class="mb-1.5 block text-xs font-medium text-slate-400">
                        Email <span class="text-slate-500">(optional contact)</span>
                    </label>
                    <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}"
                        placeholder="gm@hotel.co.tz"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                </div>

                <div>
                    <label for="admin_password" class="mb-1.5 block text-xs font-medium text-slate-400">
                        Password <span class="text-slate-500">(leave blank to auto-generate)</span>
                    </label>
                    <input id="admin_password" type="text" name="admin_password" value="{{ old('admin_password') }}"
                        placeholder="Auto-generated if empty" autocomplete="new-password"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                </div>
            </fieldset>

            <div class="border-t border-white/5 pt-4">
                <button type="submit"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    Provision hotel
                </button>
            </div>
        </form>
    </div>

</x-platform.layout>
