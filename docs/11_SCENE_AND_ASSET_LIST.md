# FTS REAL ESTATE AI — Scenes & Assets

## 1. Scenes
Each scene is a Blade view with the same stage. `data-scene` and `data-page` tell the scripts and the advisor where the visitor is.

| Scene | View | Backdrop |
|---|---|---|
| Opening | `opening.blade.php` | agency cover |
| Home | `site/home.blade.php` | first photo of the first featured listing (falls back to cover) |
| Listings | `site/listings.blade.php` | agency cover |
| Listing detail | `site/listing.blade.php` | the listing's primary photo (falls back to cover) |
| Neighbourhood / place | `site/area.blade.php`, `place.blade.php` | agency cover / place photo |
| Guide | `site/info.blade.php` | agency cover |
| Agents | `site/agents.blade.php` | agency cover |
| Viewing | `site/viewing.blade.php` | agency cover |
| Advisor drawer | `site/advisor.blade.php` | included on every page |

If an agency has no cover, a default stock photo is used.

## 2. Images
All images are referenced by URL; nothing is uploaded or resized by the app.

Per agency:
- `cover_path`: backdrop for the showroom (landscape, at least 1920×1080)
- `logo_path`: optional; the brand mark falls back to the agency's initial

Per listing (`property_images`):
- an exterior shot first (it becomes the card and backdrop image)
- interior shots tagged `kitchen`, `bedroom`, `exterior`, `pool` … so the advisor can filter by `image_tag`
- `alt_text` for accessibility

Per neighbourhood place: one photo URL (optional).

## 3. Sound
No audio files. `resources/js/sound.js` synthesises a door chime (page change), a "ding" (incoming message) and a tick (send) with the Web Audio API. The toggle is stored under `showroom_sound` in `localStorage`.

## 4. Narration
`resources/js/narrator.js` types the page brief and, on request, reads it with the browser's speech synthesis. It picks a voice for the page language and prefers a female voice when one exists. The text comes from `lang/{id,en,ja}/site.php` (`narration.*`), filled with real counts, prices and the cheapest instalment.

## 5. Icons and Fonts
Icons are inline SVG in `components/icon.blade.php`. Fonts are loaded through the `@fonts` directive (`fonts-manifest.dev.json` in development).

## 6. Scripts and Styles

| File | Role |
|---|---|
| `resources/js/page.js` | page transitions, tour captions, handing a topic to the advisor |
| `resources/js/advisor.js` | chat drawer, history, cards, drafts |
| `resources/js/listings.js` | catalogue filters and sorting |
| `resources/js/viewing.js` | viewing wizard |
| `resources/js/narrator.js` | brief and speech |
| `resources/js/sound.js` | UI sounds |
| `resources/css/app.css` | all styles (Tailwind CSS 4) |

Built with Vite (`npm run build`).

## 7. Performance
- the first backdrop has `fetchpriority="high"`
- listing filtering needs no requests
- the history is loaded only when the drawer has a stored token
- avoid giant source images; prefer compressed WebP or JPEG at 1920 px wide

## 8. Asset Checklist per Agency
- [ ] Cover image
- [ ] Logo (optional)
- [ ] At least 3 active listings, each with 3+ photos
- [ ] Neighbourhood places with distances
- [ ] Knowledge items for every category
- [ ] English and Japanese translations for the main texts
- [ ] WhatsApp, phone and email filled in
