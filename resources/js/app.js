import './bootstrap';
import { createIcons, icons } from 'lucide';

const renderLucideIcons = () => {
    createIcons({
        icons,
        attrs: {
            'stroke-width': '1.8',
        },
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderLucideIcons);
} else {
    renderLucideIcons();
}

document.querySelectorAll('[data-stepper]').forEach((stepper) => {
    const input = stepper.querySelector('[data-stepper-value]');
    const min = Number(stepper.dataset.min ?? 0);
    const max = Number(stepper.dataset.max ?? 99);

    stepper.querySelectorAll('[data-stepper-btn]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const delta = Number(btn.dataset.stepperBtn);
            const next = Math.min(max, Math.max(min, Number(input.value) + delta));
            input.value = next;
        });
    });
});

document.querySelectorAll('[data-password-field]').forEach((wrapper) => {
    const input = wrapper.querySelector('input');
    const btn = wrapper.querySelector('[data-password-toggle]');
    if (!input || !btn) return;

    const iconShow = btn.querySelector('[data-lucide="eye"]');
    const iconHide = btn.querySelector('[data-lucide="eye-off"]');

    btn.addEventListener('click', () => {
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        btn.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        btn.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
        iconShow?.classList.toggle('hidden', reveal);
        iconHide?.classList.toggle('hidden', !reveal);
    });
});

document.querySelectorAll('[data-filter-tabs]').forEach((group) => {
    group.querySelectorAll('[data-filter-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            group.querySelectorAll('[data-filter-tab]').forEach((t) => {
                t.classList.remove('filter-pill-active');
                t.classList.add('filter-pill');
            });
            tab.classList.remove('filter-pill');
            tab.classList.add('filter-pill-active');
        });
    });
});

window.refreshLucideIcons = renderLucideIcons;

// Sync browser timezone to the server (used for local-time operations and scheduling).
(function () {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (!timezone) return;

    const storageKey = 'nexstay-timezone-synced';
    if (localStorage.getItem(storageKey) === timezone) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrf) return;

    fetch('/timezone', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ timezone }),
        credentials: 'same-origin',
    })
        .then((response) => {
            if (response.ok) {
                localStorage.setItem(storageKey, timezone);
            }
        })
        .catch(() => {});
})();

// Dark / light mode toggle
(function () {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;

    btn.addEventListener('click', function () {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('nexstay-theme', isDark ? 'dark' : 'light');
        // Re-render icons so the moon/sun swap picks up the new visibility classes
        window.refreshLucideIcons?.();
    });
})();
