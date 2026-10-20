/**
 * Showroom chrome shared by every page: the header gains a border once the
 * page scrolls, and internal links ease the page out (with a short caption
 * from the advisor) before the next page loads, so moving around feels like
 * being walked through the showroom rather than clicking between documents.
 *
 * A link may carry `data-topic`: a question for the AI advisor, raised once
 * the next page is up (or straight away when the visitor is already there).
 */
const TOPIC_KEY = 'advisor_pending_topic';

function initPage() {
    const body = document.body;
    const header = document.querySelector('[data-site-header]');
    const tour = document.querySelector('[data-page-tour]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let leaving = false;

    if (header) {
        const sync = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
        sync();
        window.addEventListener('scroll', sync, { passive: true });
    }

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            leaving = false;
            body.classList.remove('is-leaving');
            tour?.classList.remove('is-visible');
        }
    });

    /** Ease the page out, then follow `href`. */
    function leave(href, line = null, topic = null) {
        if (leaving) return;
        leaving = true;

        try {
            if (topic) sessionStorage.setItem(TOPIC_KEY, topic);
            else sessionStorage.removeItem(TOPIC_KEY);
        } catch { /* private mode: the page still changes, only the topic is dropped */ }

        if (line && tour) {
            tour.textContent = line;
            tour.classList.add('is-visible');
        }

        body.classList.add('is-leaving');

        const chime = window.siteSound?.isEnabled() ?? false;
        if (chime) window.siteSound.play('enter');

        const hold = reducedMotion ? 0 : line ? 520 : 240;
        window.setTimeout(() => { window.location.href = href; }, hold);
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-page-link]');
        if (!link) return;

        const modified = event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
        if (event.defaultPrevented || modified || link.target === '_blank' || link.origin !== window.location.origin) return;

        event.preventDefault();

        const samePlace = link.pathname === window.location.pathname && link.search === window.location.search;
        if (samePlace && link.dataset.topic) {
            window.dispatchEvent(new CustomEvent('advisor:ask', { detail: { message: link.dataset.topic } }));
            return;
        }
        if (samePlace) return;

        leave(link.href, link.dataset.tourLine, link.dataset.topic);
    });

    window.showroomPage = { leave };
}

/** The topic the visitor picked on the previous page, if any (read once). */
window.takePendingAdvisorTopic = () => {
    try {
        const topic = sessionStorage.getItem(TOPIC_KEY);
        sessionStorage.removeItem(TOPIC_KEY);
        return topic;
    } catch {
        return null;
    }
};

function initGallery() {
    document.querySelectorAll('[data-gallery]').forEach((gallery) => {
        const main = gallery.querySelector('[data-gallery-main]');
        const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
        if (!main) return;

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                main.src = thumb.dataset.src;
                main.alt = thumb.dataset.alt || '';
                thumbs.forEach((other) => other.toggleAttribute('aria-current', other === thumb));
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initPage();
    initGallery();
});
