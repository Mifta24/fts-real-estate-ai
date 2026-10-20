/**
 * Client-side filtering for the listing catalogue: buy / rent, property type
 * and sort order. Every card is already in the page, so filtering is instant
 * and works the same for search engines and visitors without JavaScript.
 */
function initListings() {
    const root = document.querySelector('[data-listings]');
    const grid = root?.querySelector('[data-listing-grid]');
    if (!root || !grid) return;

    const cards = [...grid.querySelectorAll('[data-property-card]')];
    const listingButtons = [...root.querySelectorAll('[data-filter-listing]')];
    const typeButtons = [...root.querySelectorAll('[data-filter-type]')];
    const sort = root.querySelector('[data-filter-sort]');
    const count = root.querySelector('[data-filter-count]');
    const empty = root.querySelector('[data-filter-empty]');

    const state = { listing: root.dataset.initialType || '', types: new Set(), sort: 'featured' };

    const sorters = {
        featured: (a, b) => Number(a.dataset.order) - Number(b.dataset.order),
        price_asc: (a, b) => Number(a.dataset.price) - Number(b.dataset.price),
        price_desc: (a, b) => Number(b.dataset.price) - Number(a.dataset.price),
        size_desc: (a, b) => Number(b.dataset.size) - Number(a.dataset.size),
    };

    function apply() {
        let visible = 0;

        cards.forEach((card) => {
            const show = (!state.listing || card.dataset.listingType === state.listing)
                && (state.types.size === 0 || state.types.has(card.dataset.propertyType));
            card.hidden = !show;
            if (show) visible++;
        });

        [...cards].sort(sorters[state.sort] || sorters.featured).forEach((card) => grid.appendChild(card));

        listingButtons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.filterListing === state.listing)));
        typeButtons.forEach((button) => button.setAttribute('aria-pressed', String(state.types.has(button.dataset.filterType))));
        count.textContent = root.dataset.countTemplate.replace(':count', visible);
        empty.hidden = visible > 0;
    }

    listingButtons.forEach((button) => button.addEventListener('click', () => {
        state.listing = button.dataset.filterListing;
        apply();
    }));

    typeButtons.forEach((button) => button.addEventListener('click', () => {
        const type = button.dataset.filterType;
        if (state.types.has(type)) state.types.delete(type);
        else state.types.add(type);
        apply();
    }));

    sort?.addEventListener('change', () => {
        state.sort = sort.value;
        apply();
    });

    apply();
}

/**
 * The KPR widget on a listing page. The server prices it from the listing,
 * so the browser only ever sends the down payment and the tenor.
 */
function initMortgage() {
    document.querySelectorAll('[data-mortgage]').forEach((widget) => {
        const dp = widget.querySelector('input[name="down_payment_percent"]');
        const tenor = widget.querySelector('select[name="tenor_years"]');
        const dpLabel = widget.querySelector('[data-mortgage-dp-label]');
        const note = widget.querySelector('[data-mortgage-note]');
        const disclaimer = note?.textContent;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const numberLocale = { ja: 'ja-JP', en: 'en-US' }[widget.dataset.locale] || 'id-ID';
        const money = (value) => `${widget.dataset.currency} ${new Intl.NumberFormat(numberLocale, { maximumFractionDigits: 0 }).format(Number(value) || 0)}`;
        let timer = null;
        let request = 0;

        async function simulate() {
            const id = ++request;
            widget.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(widget.dataset.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
                    body: JSON.stringify({ property_slug: widget.dataset.property, down_payment_percent: Number(dp.value), tenor_years: Number(tenor.value) }),
                });
                if (!response.ok) throw new Error('simulation_failed');
                const result = await response.json();
                if (id !== request) return;

                widget.querySelectorAll('[data-mortgage-out]').forEach((output) => {
                    output.textContent = money(result[output.dataset.mortgageOut]);
                });
                if (note) note.textContent = disclaimer;
            } catch {
                if (note && id === request) note.textContent = widget.dataset.error;
            } finally {
                if (id === request) widget.removeAttribute('aria-busy');
            }
        }

        dp.addEventListener('input', () => {
            dpLabel.textContent = dp.value;
            window.clearTimeout(timer);
            timer = window.setTimeout(simulate, 250);
        });
        tenor.addEventListener('change', simulate);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initListings();
    initMortgage();
});
