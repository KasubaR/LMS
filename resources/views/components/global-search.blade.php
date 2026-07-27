@canany(['view customers', 'view loans'])
<div
    x-data="{ mobileExpanded: false }"
    @keydown.escape.window="mobileExpanded = false"
    @click.outside="mobileExpanded = false"
>
    <button
        type="button"
        class="btn btn-icon btn-secondary sm:!hidden"
        @click="mobileExpanded = true"
        aria-label="{{ __('Search') }}"
    >
        <i class="ph ph-magnifying-glass text-lg"></i>
    </button>

    <div
        class="global-search sm:flex"
        :class="{ 'hidden': !mobileExpanded, 'flex global-search--expanded': mobileExpanded }"
        x-data="globalSearch({
            suggestUrl: {{ Js::from(route('search.suggest')) }},
            resultsUrl: {{ Js::from(route('search')) }},
            initialQuery: {{ Js::from(request('q', '')) }},
        })"
        x-effect="mobileExpanded && $nextTick(() => $refs.globalSearchInput.focus())"
        @keydown.escape="close()"
    >
        <form class="global-search__form" method="GET" :action="resultsUrl" @submit="onSubmit">
            <label class="sr-only" for="global-search-q">{{ __('Search') }}</label>
            <i class="ph ph-magnifying-glass global-search__icon" aria-hidden="true"></i>
            <input
                id="global-search-q"
                x-ref="globalSearchInput"
                name="q"
                type="search"
                class="global-search__input"
                placeholder="{{ __('Search name, phone, NRC, loan…') }}"
                autocomplete="off"
                x-model="query"
                @input.debounce.250ms="fetchSuggestions()"
                @focus="open = query.trim().length >= 2"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter="onEnter($event)"
            />
            <button
                type="button"
                class="global-search__close"
                @click="mobileExpanded = false"
                aria-label="{{ __('Close search') }}"
            >
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </form>

        <div
            class="global-search__panel"
            x-show="open && (loading || hasResults || query.trim().length >= 2)"
            x-cloak
            @mousedown.outside="close()"
        >
        <template x-if="loading">
            <p class="global-search__empty">{{ __('Searching…') }}</p>
        </template>

        <template x-if="!loading && query.trim().length >= 2 && !hasResults">
            <p class="global-search__empty">{{ __('No matches found.') }}</p>
        </template>

        <template x-if="!loading && customers.length">
            <div class="global-search__group">
                <div class="global-search__group-label">{{ __('Customers') }}</div>
                <template x-for="(item, index) in customers" :key="'c-' + item.id">
                    <a
                        :href="item.url"
                        class="global-search__hit"
                        :class="{ 'is-active': flatIndex(index, 'customer') === activeIndex }"
                        @mouseenter="activeIndex = flatIndex(index, 'customer')"
                    >
                        <span class="global-search__hit-label" x-text="item.label"></span>
                        <span class="global-search__hit-meta" x-text="item.meta"></span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="!loading && loans.length">
            <div class="global-search__group">
                <div class="global-search__group-label">{{ __('Loans') }}</div>
                <template x-for="(item, index) in loans" :key="'l-' + item.id">
                    <a
                        :href="item.url"
                        class="global-search__hit"
                        :class="{ 'is-active': flatIndex(index, 'loan') === activeIndex }"
                        @mouseenter="activeIndex = flatIndex(index, 'loan')"
                    >
                        <span class="global-search__hit-label" x-text="item.label"></span>
                        <span class="global-search__hit-meta" x-text="item.meta"></span>
                    </a>
                </template>
            </div>
        </template>

        <a
            class="global-search__footer"
            :href="resultsUrl + '?q=' + encodeURIComponent(query.trim())"
            x-show="query.trim().length >= 1"
        >
            {{ __('View all results') }}
        </a>
        </div>
    </div>
</div>
@endcanany
