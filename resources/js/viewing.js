/**
 * Viewing (site visit) wizard: four short steps — property, schedule, contact,
 * summary — then a reference number and WhatsApp / phone / email hand-over.
 * Free times come from the server for the chosen day; progress survives
 * leaving the page and switching language (sessionStorage), and every rule is
 * enforced again on the server.
 */
function initViewingWizard() {
    const root = document.querySelector('[data-wizard]');
    if (!root) return;

    const labels = JSON.parse(root.querySelector('[data-wizard-labels]').textContent);
    const form = root.querySelector('[data-wizard-form]');
    const flow = root.querySelector('[data-wizard-flow]');
    const done = root.querySelector('[data-wizard-done]');
    const steps = [...root.querySelectorAll('[data-step]')];
    const progress = [...root.querySelectorAll('[data-progress-step]')];
    const stepLabel = root.querySelector('[data-wizard-step-label]');
    const backButton = root.querySelector('[data-wizard-back]');
    const nextButton = root.querySelector('[data-wizard-next]');
    const submitButton = root.querySelector('[data-wizard-submit]');
    const errorBox = root.querySelector('[data-wizard-error]');
    const slotInputs = [...root.querySelectorAll('input[name="time"]')];
    const slotHint = root.querySelector('[data-slot-hint]');
    const propertyOptions = [...root.querySelectorAll('[data-property-option]')];
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const config = {
        slotsUrl: root.dataset.slotsUrl,
        submitUrl: root.dataset.submitUrl,
        locale: root.dataset.locale,
        earliest: root.dataset.earliest,
        latest: root.dataset.latest,
        draftKey: `viewing_draft_${root.dataset.agencySlug}`,
        tokenKey: `advisor_token_${root.dataset.agencySlug}`,
    };

    const fieldStep = { property_slug: 1, date: 2, time: 2, visitor_name: 3, contact_type: 3, contact_value: 3, party_size: 3, payment_plan: 3, notes: 3 };

    let current = 1;
    let busy = false;
    let slotsRequest = 0;

    const fill = (template, values) => Object.entries(values).reduce((out, [key, value]) => out.replace(`:${key}`, value), template);

    function formatDate(iso) {
        const [year, month, day] = iso.split('-').map(Number);
        return new Intl.DateTimeFormat(config.locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(year, month - 1, day));
    }

    function values() {
        const data = new FormData(form);
        return {
            property_slug: data.get('property_slug') || '',
            date: data.get('date') || '',
            time: data.get('time') || '',
            visitor_name: (data.get('visitor_name') || '').trim(),
            contact_type: data.get('contact_type') || 'whatsapp',
            contact_value: (data.get('contact_value') || '').trim(),
            party_size: Number(data.get('party_size')) || 0,
            payment_plan: data.get('payment_plan') || 'undecided',
            notes: (data.get('notes') || '').trim(),
        };
    }

    function saveDraft() {
        try {
            sessionStorage.setItem(config.draftKey, JSON.stringify({ step: current, values: values() }));
        } catch { /* storage unavailable: the wizard still works without it */ }
    }

    function clearDraft() {
        try {
            sessionStorage.removeItem(config.draftKey);
        } catch { /* nothing to clear */ }
    }

    function check(name, value) {
        const input = form.querySelector(`input[name="${name}"][value="${CSS.escape(value)}"]`);
        if (input && !input.disabled) input.checked = true;
        return Boolean(input);
    }

    function restoreDraft() {
        try {
            const draft = JSON.parse(sessionStorage.getItem(config.draftKey) || 'null');
            if (!draft) return null;
            const saved = draft.values;
            ['date', 'visitor_name', 'contact_value', 'party_size', 'notes'].forEach((name) => {
                if (saved[name]) form.elements[name].value = saved[name];
            });
            ['property_slug', 'contact_type', 'payment_plan'].forEach((name) => saved[name] && check(name, saved[name]));
            current = Math.min(Math.max(Number(draft.step) || 1, 1), steps.length);
            return saved.time || null;
        } catch {
            return null;
        }
    }

    function clearError() {
        errorBox.hidden = true;
        errorBox.textContent = '';
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    async function post(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
            body: JSON.stringify({ ...payload, locale: config.locale }),
        });
        const body = await response.json().catch(() => ({}));
        return { ok: response.ok, status: response.status, body };
    }

    function setSlots(available, keep = null) {
        slotInputs.forEach((input) => {
            input.disabled = !available.includes(input.value);
            input.checked = !input.disabled && input.value === keep;
        });
        slotHint.textContent = available.length ? '' : labels.no_slots;
    }

    /** Asks the server which times are still free for this listing and day. */
    async function loadSlots(keep = null) {
        const { property_slug, date } = values();
        const id = ++slotsRequest;

        if (!property_slug || !date) {
            setSlots([]);
            slotHint.textContent = labels.pick_date_first;
            return;
        }

        slotHint.textContent = labels.checking;
        slotInputs.forEach((input) => { input.disabled = true; });

        try {
            const result = await post(config.slotsUrl, { property_slug, date });
            if (id !== slotsRequest) return;

            if (!result.ok) {
                setSlots([]);
                slotHint.textContent = result.body.errors ? Object.values(result.body.errors)[0][0] : labels.error_generic;
                return;
            }

            setSlots(result.body.slots, keep);
        } catch {
            if (id === slotsRequest) slotHint.textContent = labels.error_network;
        }
        saveDraft();
    }

    function validate(step) {
        const data = values();

        if (step === 1 && !data.property_slug) return { field: 'property_slug', message: labels.errors.property_unknown };

        if (step === 2) {
            if (!data.date || data.date < config.earliest || data.date > config.latest) {
                return { field: 'date', message: fill(labels.errors.date_range, { from: formatDate(config.earliest), to: formatDate(config.latest) }) };
            }
            if (!data.time) return { field: 'time', message: labels.errors.slot_required };
        }

        if (step === 3) {
            if (!data.visitor_name) return { field: 'visitor_name', message: labels.errors.invalid };
            const valid = data.contact_type === 'email'
                ? /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.contact_value)
                : /^\+?[0-9\s\-().]{6,20}$/.test(data.contact_value);
            if (!valid) return { field: 'contact_value', message: data.contact_type === 'email' ? labels.errors.contact_email : labels.errors.contact_phone };
            if (data.party_size < 1 || data.party_size > Number(form.elements.party_size.max)) return { field: 'party_size', message: labels.errors.invalid };
        }

        return null;
    }

    function showStep(step, focus = false) {
        current = step;
        steps.forEach((element, index) => { element.hidden = index + 1 !== step; });
        progress.forEach((element, index) => {
            element.classList.toggle('is-current', index + 1 === step);
            element.classList.toggle('is-done', index + 1 < step);
            if (index + 1 === step) element.setAttribute('aria-current', 'step');
            else element.removeAttribute('aria-current');
        });
        stepLabel.textContent = `${fill(labels.step_of, { current: step, total: steps.length })} · ${labels.steps[step - 1]}`;
        backButton.hidden = step === 1;
        nextButton.hidden = step === steps.length;
        submitButton.hidden = step !== steps.length;

        if (step === steps.length) renderSummary();
        if (focus) steps[step - 1].querySelector('input:not([disabled]):not([type="radio"]), input:checked, textarea')?.focus({ preventScroll: true });
        saveDraft();
    }

    function next() {
        if (busy || current >= steps.length) return;
        clearError();

        const problem = validate(current);
        if (problem) {
            showError(problem.message);
            return;
        }

        showStep(current + 1, true);
    }

    function summaryRow(label, value, step) {
        const wrapper = document.createElement('div');
        const term = document.createElement('dt');
        term.textContent = label;
        const detail = document.createElement('dd');
        const span = document.createElement('span');
        span.textContent = value;
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.textContent = labels.edit;
        edit.addEventListener('click', () => showStep(step, true));
        detail.append(span, edit);
        wrapper.append(term, detail);
        return wrapper;
    }

    function renderSummary() {
        const data = values();
        const chosen = propertyOptions.find((option) => option.querySelector('input').value === data.property_slug);
        const rows = [
            summaryRow(labels.property, chosen?.dataset.name || '', 1),
            summaryRow(labels.schedule, `${formatDate(data.date)} · ${data.time}`, 2),
            summaryRow(labels.visitor, `${data.visitor_name} · ${data.party_size} ${labels.people} · ${labels.plans[data.payment_plan]}`, 3),
            summaryRow(labels.contact, `${labels[data.contact_type]}: ${data.contact_value}`, 3),
        ];
        if (data.notes) rows.push(summaryRow(labels.notes, data.notes, 3));

        root.querySelector('[data-summary]').replaceChildren(...rows);
    }

    function showDone(result) {
        flow.hidden = true;
        done.hidden = false;
        root.querySelector('[data-done-reference]').textContent = result.reference;

        [['whatsapp', 'whatsapp_url'], ['phone', 'phone_url'], ['email', 'email_url']].forEach(([name, key]) => {
            const link = root.querySelector(`[data-done-${name}]`);
            const url = result.contact?.[key];
            link.hidden = !url;
            if (url) link.href = url;
        });
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function submit(event) {
        event.preventDefault();
        if (busy || current !== steps.length) return;
        clearError();

        const problem = [1, 2, 3].map(validate).find(Boolean);
        if (problem) {
            showError(problem.message);
            showStep(fieldStep[problem.field] || current, true);
            return;
        }

        busy = true;
        [backButton, submitButton].forEach((button) => { button.disabled = true; });
        submitButton.textContent = labels.sending;

        try {
            const token = (() => { try { return localStorage.getItem(config.tokenKey); } catch { return null; } })();
            const result = await post(config.submitUrl, { ...values(), visitor_token: token || undefined });

            if (!result.ok) {
                if (result.status === 422 && result.body.errors) {
                    const [field, messages] = Object.entries(result.body.errors)[0];
                    if (fieldStep[field] && fieldStep[field] !== current) showStep(fieldStep[field]);
                    if (Array.isArray(result.body.slots)) setSlots(result.body.slots);
                    showError(messages[0]);
                } else {
                    showError(labels.error_generic);
                }
                return;
            }

            clearDraft();
            showDone(result.body);
        } catch {
            showError(labels.error_network);
        } finally {
            busy = false;
            [backButton, submitButton].forEach((button) => { button.disabled = false; });
            submitButton.textContent = labels.submit;
        }
    }

    function reset() {
        form.reset();
        clearError();
        clearDraft();
        done.hidden = true;
        flow.hidden = false;
        setSlots([]);
        slotHint.textContent = labels.pick_date_first;
        showStep(1, true);
    }

    form.addEventListener('change', (event) => {
        if (event.target.name === 'property_slug' || event.target.name === 'date') loadSlots(values().time || null);
        saveDraft();
    });
    form.addEventListener('input', saveDraft);
    form.addEventListener('submit', submit);
    nextButton.addEventListener('click', next);
    backButton.addEventListener('click', () => { clearError(); showStep(Math.max(1, current - 1), true); });
    root.querySelector('[data-wizard-reset]').addEventListener('click', reset);
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA' && current < steps.length) {
            event.preventDefault();
            next();
        }
    });

    const savedTime = restoreDraft();
    if (root.dataset.preselectProperty) check('property_slug', root.dataset.preselectProperty);
    showStep(current);
    if (values().date) loadSlots(savedTime);
}

document.addEventListener('DOMContentLoaded', initViewingWizard);
