@if (session('success') || session('error'))
    <div class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-3">
        @if (session('success'))
            <div data-toast class="pointer-events-auto rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p>{{ session('success') }}</p>
                    <button type="button" data-toast-close class="rounded p-1 text-emerald-700 hover:bg-emerald-100" aria-label="Dismiss notification">
                        <x-icon name="x-circle" class="size-4" />
                    </button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div data-toast class="pointer-events-auto rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p>{{ session('error') }}</p>
                    <button type="button" data-toast-close class="rounded p-1 text-red-700 hover:bg-red-100" aria-label="Dismiss notification">
                        <x-icon name="x-circle" class="size-4" />
                    </button>
                </div>
            </div>
        @endif
    </div>
    <script>
        (() => {
            const toasts = Array.from(document.querySelectorAll('[data-toast]'));

            toasts.forEach((toast) => {
                const close = () => {
                    toast.classList.add('opacity-0', 'translate-x-2', 'transition', 'duration-200');
                    window.setTimeout(() => toast.remove(), 220);
                };

                toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
                window.setTimeout(close, 4000);
            });
        })();
    </script>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
