# FTS REAL ESTATE AI — UI/UX Specification

## 1. Core Layout Rule
Every showroom page uses the same fixed stage (`components/showroom.blade.php`):

- full-screen photo backdrop with a shade overlay
- header: agency brand, language switch, sound toggle
- numbered side menu (desktop) / bottom navigation (mobile)
- one content panel that scrolls on its own
- the advisor drawer

The document itself does not scroll; only the content panel and the chat history do. The home page uses a different composition (`data-page-kind="home"`) than inner pages (`panel`).

## 2. Opening Page
- brand and language switch
- headline, tagline and four feature bullets
- one card per published agency (cover or initial, city, active listing count)
- a large cover image on the right with the advisor's name and role

## 3. Menu
Items, in order: Home, Listings, Neighbourhood, Guide, Agents, Book a viewing.

Rules:
- the current item has `aria-current="page"`; listing and place pages highlight their parent
- each item carries a caption read as a "tour line" while the page changes

## 4. Advisor Drawer
Elements:
- name (Rumi) and role (AI Property Advisor)
- intro message and quick actions: Find a property, Mortgage simulation, Neighbourhood info, Book a viewing, Talk to an agent
- message list with typing indicator
- text input and send button (max 2000 characters)
- cards inside messages: property cards, mortgage tables, viewing slots, handover notice
- action buttons that follow the cards (view details, book a viewing, talk to an agent)
- draft protection when closing with unsent text

## 5. Listing Detail
Layout:
- gallery with photo counter and "no photo yet" fallback
- price (per year for rentals), status (units left / sold out)
- specification grid and amenities
- description
- KPR simulator (sale only)
- actions: Book a viewing, Ask Rumi
- previous / next and similar listings

## 6. Viewing Wizard
Four steps with a step counter: Property → Schedule → Your details → Summary. Each field validates immediately; the summary has Edit buttons. After submit the reference and the WhatsApp / call / email buttons appear.

## 7. Transitions and Sound
- Page change: the page eases out (`is-leaving`) with a short caption, then loads the next page. `prefers-reduced-motion` skips the animation.
- Sounds are synthesised with the Web Audio API: a door chime on page change, a "ding" for incoming messages, a tick on send. They follow a visitor action only and can be muted; the choice is stored per browser.
- The narrator types an intro like speech and reads it aloud with browser speech synthesis only when the visitor presses Listen.

## 8. Responsive Behaviour
- Desktop: side menu + content panel; advisor drawer on the right.
- Mobile: bottom navigation; the advisor opens as a full-width sheet; wizard steps stack.
- The page never scrolls horizontally.

## 9. Loading and Error States
- Chat: "Rumi is typing…" while waiting.
- Chat error: short message with Retry and Talk to an agent.
- Rate limit (HTTP 429): "You are sending messages very quickly."
- Viewing: network and generic error messages keep the entered data.
- No JavaScript: a notice explains that the advisor and the viewing form need JavaScript; the agency phone is shown.

## 10. Accessibility
- full text of narrations in the DOM
- visible focus states and labelled controls
- alt text for gallery images
- language attributes on the document and the language switch
- information is never conveyed by colour alone

## 11. Visual Style
Keywords: warm, modern, trustworthy, photographic, calm.

Avoid: crowded filter bars, dashboard look in the public pages, urgency tricks.

Styles live in `resources/css/app.css` (Tailwind CSS 4).
