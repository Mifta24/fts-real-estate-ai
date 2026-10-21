/**
 * AI Property Advisor drawer. Vanilla JS, no framework: sends visitor
 * messages to the Laravel backend and renders both plain text and the
 * structured ui_payload the AI's tools attach (listing cards, mortgage
 * simulations, viewing slots, confirmations, hand-over notices) as real DOM.
 * All server text is inserted with textContent, never as HTML.
 */
function initAdvisor() {
    const root = document.querySelector('[data-advisor]');
    if (!root) return;

    const labels = JSON.parse(root.querySelector('[data-advisor-labels]').textContent);
    const topics = JSON.parse(root.querySelector('[data-advisor-topics]').textContent);
    const config = {
        startUrl: root.dataset.startUrl,
        messageUrl: root.dataset.messageUrl,
        historyUrl: root.dataset.historyUrl,
        storageKey: root.dataset.storageKey,
        locale: root.dataset.locale,
        currency: root.dataset.currency,
        propertyUrlTemplate: root.dataset.propertyUrl,
        viewingUrlTemplate: root.dataset.viewingUrl,
        agentsUrl: root.dataset.agentsUrl,
    };

    const panel = root.querySelector('.advisor-panel');
    const launcher = root.querySelector('[data-advisor-launcher]');
    const closeButton = root.querySelector('[data-advisor-close]');
    const messagesEl = root.querySelector('[data-advisor-messages]');
    const formEl = root.querySelector('[data-advisor-form]');
    const inputEl = root.querySelector('[data-advisor-input]');
    const submitEl = root.querySelector('[data-advisor-submit]');
    const banner = root.querySelector('[data-advisor-banner]');
    const statusEl = root.querySelector('[data-advisor-status]');
    const thinking = root.querySelector('[data-advisor-thinking]');
    const draftDialog = root.querySelector('[data-advisor-draft]');
    const draftKeep = root.querySelector('[data-advisor-draft-keep]');
    const draftDiscard = root.querySelector('[data-advisor-draft-discard]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const numberLocale = { ja: 'ja-JP', en: 'en-US' }[config.locale] || 'id-ID';
    const avatarLetter = root.querySelector('.advisor-avatar')?.textContent.trim() || 'R';

    // Cloudflare drops a request that is still unanswered after 100s, so give
    // up just before that and let the visitor retry instead of waiting forever.
    const API_TIMEOUT_MS = 95000;

    let ready = false;
    let busy = false;
    let handedOver = false;
    let knownMessageCount = 0;
    const POLL_MIN_MS = 4000;
    const POLL_MAX_MS = 30000;
    let pollTimer = null;
    let pollDelay = POLL_MIN_MS;
    let polling = false;
    let isOpen = false;

    // --- small DOM helpers ---

    function el(tag, className = '', text = null) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== null && text !== undefined) node.textContent = text;
        return node;
    }

    const money = (value) => `${config.currency} ${new Intl.NumberFormat(numberLocale, { maximumFractionDigits: 0 }).format(Number(value) || 0)}`;
    const compactMoney = (value) => `${config.currency} ${new Intl.NumberFormat(numberLocale, { notation: 'compact', maximumFractionDigits: 2 }).format(Number(value) || 0)}`;
    const fill = (template, values) => Object.entries(values).reduce((out, [key, value]) => out.replaceAll(`:${key}`, value), template);
    const propertyUrl = (slug) => config.propertyUrlTemplate.replace('__SLUG__', encodeURIComponent(slug));
    const viewingUrl = (slug) => config.viewingUrlTemplate.replace('__SLUG__', encodeURIComponent(slug));

    function formatDate(iso) {
        const [year, month, day] = iso.split('-').map(Number);
        return new Intl.DateTimeFormat(numberLocale, { weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(year, month - 1, day));
    }

    function avatar() {
        const node = el('span', 'advisor-avatar', avatarLetter);
        node.setAttribute('aria-hidden', 'true');
        return node;
    }

    /** A link that walks to another page the same way the menu does. */
    function pageLink(href, text, className = '') {
        const link = el('a', className, text);
        link.href = href;
        link.addEventListener('click', (event) => {
            if (link.pathname === window.location.pathname && link.search === window.location.search) return;
            closePanel({ confirmDraft: false, restoreFocus: false });
            if (window.showroomPage) {
                event.preventDefault();
                window.showroomPage.leave(link.href);
            }
        });
        return link;
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // --- panel state ---

    function setStatus(text, tone = '') {
        statusEl.textContent = text || '';
        statusEl.className = `advisor-status${text ? ` is-${tone}` : ''}`;
        statusEl.hidden = !text;
    }

    function syncPanel() {
        root.classList.toggle('is-open', isOpen);
        panel.setAttribute('aria-hidden', String(!isOpen));
        launcher.setAttribute('aria-expanded', String(isOpen));
    }

    function openPanel({ focus = false } = {}) {
        isOpen = true;
        syncPanel();
        scrollToBottom();
        if (focus) requestAnimationFrame(() => inputEl.focus());
    }

    function finishClose({ restoreFocus = true } = {}) {
        isOpen = false;
        draftDialog.hidden = true;
        syncPanel();
        if (restoreFocus) launcher.focus();
    }

    function closePanel({ confirmDraft = true, restoreFocus = true } = {}) {
        if (confirmDraft && inputEl.value.trim()) {
            draftDialog.hidden = false;
            requestAnimationFrame(() => draftKeep.focus());
            return;
        }
        finishClose({ restoreFocus });
    }

    launcher.addEventListener('click', () => openPanel({ focus: true }));
    closeButton.addEventListener('click', () => closePanel());
    draftKeep.addEventListener('click', () => { draftDialog.hidden = true; inputEl.focus(); });
    draftDiscard.addEventListener('click', () => { inputEl.value = ''; finishClose(); });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (!draftDialog.hidden) {
            draftDialog.hidden = true;
            inputEl.focus();
        } else if (isOpen) {
            closePanel();
        }
    });
    syncPanel();

    // --- rendering ---

    function bubble(role, text) {
        const row = el('div', `msg msg-${role}`);
        if (role !== 'visitor') row.appendChild(avatar());
        const body = el('div', 'msg-bubble');
        if (role === 'agent') body.appendChild(el('span', 'msg-agent-label', labels.agent));
        body.appendChild(document.createTextNode(text));
        row.appendChild(body);
        return row;
    }

    function propertyCard(property) {
        const card = el('article', 'chat-card');
        if (property.thumbnail_url) {
            const img = el('img');
            img.src = property.thumbnail_url;
            img.alt = '';
            img.loading = 'lazy';
            card.appendChild(img);
        }

        const body = el('div', 'chat-card-body');
        const price = el('p', 'chat-card-price', compactMoney(property.price));
        if (property.price_period === 'year') price.appendChild(el('span', 'text-xs font-medium text-muted', ` ${labels.perYear}`));
        body.appendChild(price);
        body.appendChild(el('p', 'chat-card-name', property.name));

        const meta = [labels.propertyTypes[property.property_type] || property.property_type, property.district].filter(Boolean);
        if (property.bedrooms) meta.push(`${property.bedrooms} ${labels.bedroomsShort}`);
        if (property.building_size_sqm) meta.push(`${property.building_size_sqm} m²`);
        body.appendChild(el('p', 'chat-card-meta', meta.join(' · ')));

        const actions = el('div', 'chat-card-actions');
        const details = el('button', '', labels.viewDetails);
        details.type = 'button';
        details.addEventListener('click', () => sendMessage(fill(labels.detailsQuestion, { name: property.name })));
        actions.append(details, pageLink(propertyUrl(property.property_slug), '↗'));
        body.appendChild(actions);

        card.appendChild(body);
        return card;
    }

    function detailPanel(property) {
        const box = el('div', 'chat-panel');
        box.appendChild(el('p', 'chat-panel-title', property.name));
        const meta = [labels.propertyTypes[property.property_type] || property.property_type, property.district, compactMoney(property.price) + (property.price_period === 'year' ? ` ${labels.perYear}` : '')];
        box.appendChild(el('p', 'chat-panel-sub', meta.filter(Boolean).join(' · ')));

        const images = (property.images || []).slice(0, 3);
        if (images.length) {
            const gallery = el('div', 'chat-gallery');
            images.forEach((image) => {
                const img = el('img');
                img.src = image.url;
                img.alt = image.alt || property.name;
                img.loading = 'lazy';
                gallery.appendChild(img);
            });
            box.appendChild(gallery);
        }

        const facts = [
            property.bedrooms ? `${property.bedrooms} ${labels.bedroomsShort}` : null,
            property.bathrooms ? `${property.bathrooms} ${labels.bathroomsShort}` : null,
            property.building_size_sqm ? `LB ${property.building_size_sqm} m²` : null,
            property.land_size_sqm ? `LT ${property.land_size_sqm} m²` : null,
            property.certificate ? property.certificate.toUpperCase() : null,
        ].filter(Boolean);
        if (facts.length) box.appendChild(el('p', 'mt-2 text-xs font-semibold', facts.join(' · ')));
        return box;
    }

    function mortgagePanel(simulation) {
        const box = el('div', 'chat-panel');
        box.appendChild(el('p', 'chat-panel-title', labels.mortgageTitle));
        box.appendChild(el('p', 'chat-panel-sub', simulation.name));
        box.appendChild(el('p', 'chat-figure', money(simulation.monthly_installment)));
        box.appendChild(el('p', 'text-xs text-muted', labels.monthly));

        const list = el('dl', 'chat-dl');
        [
            [labels.downPayment, `${simulation.down_payment_percent}% · ${money(simulation.down_payment)}`],
            [labels.loan, money(simulation.loan_amount)],
            [labels.tenor, `${simulation.tenor_years} ${labels.years}`],
            [labels.rate, `${simulation.annual_rate}%`],
            [labels.income, money(simulation.minimum_monthly_income)],
        ].forEach(([term, value]) => {
            const row = el('div');
            row.append(el('dt', '', term), el('dd', '', value));
            list.appendChild(row);
        });
        box.appendChild(list);
        return box;
    }

    function slotsPanel(slots) {
        const box = el('div', 'chat-panel');
        box.appendChild(el('p', 'chat-panel-title', labels.slotsTitle));
        box.appendChild(el('p', 'chat-panel-sub', `${slots.name} · ${formatDate(slots.date)}`));

        if (!slots.available_times?.length) {
            box.appendChild(el('p', 'mt-2 text-sm', labels.noSlots));
            return box;
        }

        const list = el('div', 'chat-slots');
        slots.available_times.forEach((time) => {
            const button = el('button', '', time);
            button.type = 'button';
            button.addEventListener('click', () => sendMessage(fill(labels.scheduleQuestion, { name: slots.name, date: formatDate(slots.date), time })));
            list.appendChild(button);
        });
        box.appendChild(list);
        return box;
    }

    function confirmationPanel(viewing) {
        const box = el('div', 'chat-panel chat-success');
        box.appendChild(el('p', 'chat-panel-title', labels.viewingReceived));
        box.appendChild(el('p', 'chat-panel-sub', `${labels.reference}: ${viewing.viewing_reference}`));
        box.appendChild(el('p', 'mt-2 font-semibold', viewing.name));
        box.appendChild(el('p', 'text-sm', `${formatDate(viewing.date)} · ${viewing.time}`));
        return box;
    }

    function stack(children, className = 'msg-stack') {
        const wrap = el('div', className);
        children.forEach((child) => wrap.appendChild(child));
        return wrap;
    }

    function renderUiPayload(payloads) {
        (payloads || []).forEach((payload) => {
            if (payload.type === 'property_results') {
                messagesEl.appendChild(stack(payload.properties.map(propertyCard), 'msg-cards'));
            } else if (payload.type === 'property_detail') {
                messagesEl.appendChild(stack([detailPanel(payload.property)]));
            } else if (payload.type === 'mortgage') {
                messagesEl.appendChild(stack([mortgagePanel(payload.simulation)]));
            } else if (payload.type === 'viewing_slots') {
                messagesEl.appendChild(stack([slotsPanel(payload.slots)]));
            } else if (payload.type === 'viewing_confirmation') {
                messagesEl.appendChild(stack([confirmationPanel(payload.viewing)]));
            } else if (payload.type === 'handover') {
                messagesEl.appendChild(stack([el('div', 'chat-panel chat-warning', labels.handedOver)]));
            }
        });
    }

    function actionChips(actions) {
        if (!actions?.length) return null;

        const targets = {
            view_property: (action) => [labels.viewDetails, propertyUrl(action.property)],
            schedule: (action) => [labels.schedule, viewingUrl(action.property)],
            agent: () => [labels.talkAgent, config.agentsUrl],
        };

        const wrap = el('div', 'chat-actions');
        actions.forEach((action) => {
            const target = targets[action.action]?.(action);
            if (target) wrap.appendChild(pageLink(target[1], target[0]));
        });

        return wrap.childElementCount ? wrap : null;
    }

    function renderMessage(message) {
        if (message.role === 'visitor') {
            messagesEl.appendChild(bubble('visitor', message.content));
        } else if (message.role === 'assistant') {
            if (message.content) messagesEl.appendChild(bubble('advisor', message.content));
            renderUiPayload(message.ui_payload);
            const chips = actionChips(message.suggested_actions);
            if (chips) messagesEl.appendChild(chips);
        } else if (message.role === 'agent') {
            messagesEl.appendChild(bubble('agent', message.content));
            setStatus(labels.statusReplied, 'replied');
        } else if (message.role === 'system') {
            messagesEl.appendChild(bubble('advisor', message.content));
        }
    }

    function suggestions() {
        const wrap = el('div', 'chat-suggestions');
        wrap.appendChild(el('p', '', labels.suggestions));
        topics.forEach((topic) => {
            const button = el('button');
            button.type = 'button';
            button.append(el('span', '', topic.label), el('span', 'text-muted', '›'));
            button.addEventListener('click', () => sendMessage(topic.message));
            wrap.appendChild(button);
        });
        return wrap;
    }

    function greet() {
        messagesEl.appendChild(bubble('advisor', labels.intro));
        messagesEl.appendChild(suggestions());
    }

    function errorPanel(text, err) {
        const box = el('div', 'chat-panel chat-error');
        box.appendChild(el('p', '', err.status === 429 ? labels.slow : labels.error));

        const actions = el('div', 'mt-3 flex flex-wrap gap-2');
        const retry = el('button', 'btn btn-dark btn-sm', labels.retry);
        retry.type = 'button';
        retry.addEventListener('click', () => {
            box.parentElement.remove();
            sendMessage(text, { retry: true });
        });
        actions.append(retry, pageLink(config.agentsUrl, labels.talkAgent, 'btn btn-ghost btn-sm'));
        box.appendChild(actions);
        return stack([box]);
    }

    // --- networking ---

    async function api(url, body) {
        let response;

        try {
            response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
                body: JSON.stringify(body || {}),
                signal: AbortSignal.timeout(API_TIMEOUT_MS),
            });
        } catch (err) {
            const error = new Error(err.name === 'TimeoutError' ? 'Request timed out' : 'Network error');
            error.status = err.name === 'TimeoutError' ? 408 : 0;
            throw error;
        }

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            const error = new Error(payload.message || `Request failed (${response.status})`);
            error.status = response.status;
            throw error;
        }

        return response.json();
    }

    async function fetchHistory(token) {
        const response = await fetch(`${config.historyUrl}?visitor_token=${encodeURIComponent(token)}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            const error = new Error('history_unavailable');
            error.status = response.status;
            throw error;
        }
        return response.json();
    }

    function setBusy(value) {
        busy = value;
        const disabled = busy || !ready || handedOver;
        inputEl.disabled = disabled;
        submitEl.disabled = disabled;
        root.setAttribute('aria-busy', String(busy));
        document.body.classList.toggle('is-thinking', busy);
        thinking.hidden = !busy;
        document.querySelectorAll('[data-hero-quick-message], [data-advisor-prompt] button').forEach((button) => { button.disabled = disabled; });
        if (busy) scrollToBottom();
    }

    function showHandedOver() {
        handedOver = true;
        banner.textContent = labels.handedOver;
        banner.hidden = false;
        setStatus(labels.statusWaiting, 'waiting');
        setBusy(busy);
        startPolling();
    }

    function clearHandedOver() {
        handedOver = false;
        banner.hidden = true;
        setBusy(busy);
        stopPolling();
    }

    // Agent replies are fetched by polling. The delay grows while nothing new
    // arrives, polling pauses while the tab is hidden, and a failing or
    // rate-limited request backs off, so idle conversations stay cheap.
    function startPolling() {
        if (pollTimer || polling || document.hidden) return;
        pollDelay = POLL_MIN_MS;
        schedulePoll();
    }

    function stopPolling() {
        clearTimeout(pollTimer);
        pollTimer = null;
    }

    function schedulePoll() {
        pollTimer = setTimeout(pollForAgentReplies, pollDelay);
    }

    document.addEventListener('visibilitychange', () => {
        if (!handedOver) return;

        if (document.hidden) {
            stopPolling();
        } else if (!pollTimer && !polling) {
            pollDelay = POLL_MIN_MS;
            pollForAgentReplies();
        }
    });

    async function pollForAgentReplies() {
        pollTimer = null;
        const token = localStorage.getItem(config.storageKey);
        if (!token || !handedOver) return;

        polling = true;

        try {
            const data = await fetchHistory(token);

            if (data.messages.length > knownMessageCount) {
                data.messages.slice(knownMessageCount).forEach(renderMessage);
                window.siteSound?.play('incoming');
                knownMessageCount = data.messages.length;
                scrollToBottom();
                pollDelay = POLL_MIN_MS;
            } else {
                pollDelay = Math.min(pollDelay * 1.5, POLL_MAX_MS);
            }

            if (data.status !== 'handed_over') {
                clearHandedOver();
                return;
            }
        } catch (error) {
            pollDelay = error.status === 429 ? POLL_MAX_MS : Math.min(pollDelay * 2, POLL_MAX_MS);
        } finally {
            polling = false;
        }

        if (handedOver && !document.hidden) schedulePoll();
    }

    const sceneOf = () => document.body.dataset.scene || 'home';

    /**
     * Where the visitor is in the showroom, so the advisor can answer for
     * "this house" or the viewing on screen. Never includes name or contact data.
     */
    function uiContext() {
        const context = { scene: sceneOf() };
        const lastSegment = decodeURIComponent(window.location.pathname.split('/').pop());

        if (context.scene === 'listing_detail') context.selected_property = lastSegment;
        if (context.scene === 'area_detail') context.selected_place = Number(lastSegment);

        if (context.scene === 'viewing') {
            try {
                const draft = JSON.parse(sessionStorage.getItem(`viewing_draft_${root.dataset.agencySlug}`) || 'null');
                const { property_slug, date, time, party_size, payment_plan } = draft?.values || {};
                context.viewing = { property_slug, date, time, party_size, payment_plan };
                if (property_slug) context.selected_property = property_slug;
            } catch { /* no draft to share */ }
        }

        return context;
    }

    async function sendMessage(text, { retry = false } = {}) {
        if (!text?.trim() || handedOver || busy || !ready) return;

        const token = localStorage.getItem(config.storageKey);
        if (!token) return;

        openPanel();
        if (!retry) messagesEl.appendChild(bubble('visitor', text));
        window.siteSound?.play('sent');
        setBusy(true);

        try {
            const data = await api(config.messageUrl, { visitor_token: token, message: text, ...uiContext() });
            renderMessage(data.message);
            setStatus(labels.statusSent, 'sent');
            window.siteSound?.play('incoming');
            knownMessageCount += 2; // the visitor message just sent + the reply just rendered
            if (data.status === 'handed_over') showHandedOver();
        } catch (err) {
            messagesEl.appendChild(errorPanel(text, err));
        } finally {
            setBusy(false);
            scrollToBottom();
        }
    }

    async function boot() {
        let token = localStorage.getItem(config.storageKey);

        if (!token) {
            const data = await api(config.startUrl, { locale: config.locale });
            token = data.visitor_token;
            localStorage.setItem(config.storageKey, token);
            greet();
            return;
        }

        try {
            const data = await fetchHistory(token);

            if (!data.messages.length) greet();
            else data.messages.forEach(renderMessage);

            knownMessageCount = data.messages.length;
            if (data.status === 'handed_over') showHandedOver();
            if (data.messages.some((message) => message.role === 'agent')) setStatus(labels.statusReplied, 'replied');
        } catch {
            localStorage.removeItem(config.storageKey);
            messagesEl.replaceChildren();
            return boot();
        }
    }

    formEl.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!ready || busy || handedOver || !inputEl.value.trim()) return;
        const text = inputEl.value;
        inputEl.value = '';
        sendMessage(text);
    });

    // Any "ask Rumi" button on the page sends its question straight into the chat.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-hero-quick-message]');
        if (!button) return;
        openPanel();
        sendMessage(button.dataset.heroQuickMessage);
    });

    document.querySelectorAll('[data-advisor-prompt]').forEach((form) => {
        const field = form.querySelector('textarea, input');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const text = field.value.trim();
            if (!text) return;
            field.value = '';
            openPanel();
            sendMessage(text);
        });
        field.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        });
    });

    window.addEventListener('advisor:ask', (event) => sendMessage(event.detail.message));

    setBusy(true);
    boot().then(() => {
        ready = true;
    }).catch(() => {
        banner.textContent = labels.connectionError;
        banner.hidden = false;
        greet();
    }).finally(() => {
        setBusy(false);
        scrollToBottom();

        // The visitor picked this topic on the previous page: let the page
        // settle for a beat, then carry on the conversation.
        const topic = window.takePendingAdvisorTopic?.();
        if (topic && ready) window.setTimeout(() => sendMessage(topic), 500);
    });
}

document.addEventListener('DOMContentLoaded', initAdvisor);
