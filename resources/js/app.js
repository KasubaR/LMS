import '@phosphor-icons/web/regular';
import '@phosphor-icons/web/fill';

import Alpine from 'alpinejs';
import { initDashboardCharts } from './dashboard';

const THEME_KEY = 'lms-theme';

function normalizeTheme(value) {
    return value === 'dark' ? 'dark' : 'light';
}

function getStoredTheme() {
    try {
        return normalizeTheme(localStorage.getItem(THEME_KEY));
    } catch {
        return 'light';
    }
}

function applyTheme(theme) {
    const next = normalizeTheme(theme);
    document.documentElement.dataset.theme = next;

    try {
        localStorage.setItem(THEME_KEY, next);
    } catch {
        // Ignore quota / private-mode failures; theme still applies for this page.
    }

    window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: next } }));

    return next;
}

function toggleTheme() {
    const current = document.documentElement.dataset.theme || getStoredTheme();
    return applyTheme(current === 'dark' ? 'light' : 'dark');
}

window.lmsTheme = {
    get: () => document.documentElement.dataset.theme || getStoredTheme(),
    set: applyTheme,
    toggle: toggleTheme,
};

Alpine.data('themeToggle', () => ({
    theme: document.documentElement.dataset.theme || getStoredTheme(),
    init() {
        this.theme = window.lmsTheme.get();
        window.addEventListener('themechange', (event) => {
            this.theme = event.detail?.theme || window.lmsTheme.get();
        });
    },
    toggle() {
        this.theme = window.lmsTheme.toggle();
    },
    get isDark() {
        return this.theme === 'dark';
    },
}));

Alpine.data('globalSearch', (config = {}) => ({
    suggestUrl: config.suggestUrl || '/search/suggest',
    resultsUrl: config.resultsUrl || '/search',
    query: config.initialQuery || '',
    open: false,
    loading: false,
    customers: [],
    loans: [],
    activeIndex: -1,
    abortController: null,

    get flatItems() {
        return [
            ...this.customers.map((item) => ({ ...item, kind: 'customer' })),
            ...this.loans.map((item) => ({ ...item, kind: 'loan' })),
        ];
    },

    get hasResults() {
        return this.customers.length > 0 || this.loans.length > 0;
    },

    flatIndex(index, kind) {
        if (kind === 'customer') {
            return index;
        }

        return this.customers.length + index;
    },

    close() {
        this.open = false;
        this.activeIndex = -1;
    },

    move(delta) {
        if (!this.flatItems.length) {
            return;
        }

        this.open = true;
        const total = this.flatItems.length;
        this.activeIndex = (this.activeIndex + delta + total) % total;
    },

    onEnter(event) {
        if (this.open && this.activeIndex >= 0 && this.flatItems[this.activeIndex]) {
            event.preventDefault();
            window.location.href = this.flatItems[this.activeIndex].url;
            return;
        }

        // Allow native form submit to results page.
    },

    onSubmit(event) {
        if (this.open && this.activeIndex >= 0 && this.flatItems[this.activeIndex]) {
            event.preventDefault();
            window.location.href = this.flatItems[this.activeIndex].url;
        }
    },

    async fetchSuggestions() {
        const term = this.query.trim();

        if (term.length < 2) {
            this.customers = [];
            this.loans = [];
            this.open = false;
            this.loading = false;
            this.activeIndex = -1;
            return;
        }

        this.loading = true;
        this.open = true;

        if (this.abortController) {
            this.abortController.abort();
        }

        this.abortController = new AbortController();

        try {
            const response = await fetch(`${this.suggestUrl}?q=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json' },
                signal: this.abortController.signal,
            });

            if (!response.ok) {
                throw new Error('Suggest failed');
            }

            const data = await response.json();
            this.customers = data.customers || [];
            this.loans = data.loans || [];
            this.activeIndex = this.flatItems.length ? 0 : -1;
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.customers = [];
                this.loans = [];
            }
        } finally {
            this.loading = false;
        }
    },
}));

window.Alpine = Alpine;
window.initDashboardCharts = initDashboardCharts;

Alpine.start();
