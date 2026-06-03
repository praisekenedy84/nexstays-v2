@props([
    'name',
    'toggleClass' => 'text-ink-muted hover:text-ink focus-visible:ring-primary/30',
])

<div class="relative" data-password-field>
    <input
        type="password"
        name="{{ $name }}"
        {{ $attributes->class(['pr-11']) }}
    />
    <button
        type="button"
        data-password-toggle
        class="absolute right-3 top-1/2 -translate-y-1/2 rounded p-1 transition focus:outline-none focus-visible:ring-2 {{ $toggleClass }}"
        aria-label="Show password"
        aria-pressed="false"
    >
        <i data-lucide="eye" class="size-5" aria-hidden="true"></i>
        <i data-lucide="eye-off" class="size-5 hidden" aria-hidden="true"></i>
    </button>
</div>
