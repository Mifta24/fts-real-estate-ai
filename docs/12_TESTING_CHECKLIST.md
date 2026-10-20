# FTS REAL ESTATE AI — Testing Checklist

## 1. Automated
```bash
composer test
```
Covers admin access (login redirect, per-agency isolation of viewings and handovers, reply and resolve, login rate limit, image URL validation) and `ContentGuard` (offensive and ordinary messages, language detection, localised refusal).

Run `vendor/bin/pint --dirty` before committing PHP changes.

## 2. Opening and Navigation
- [ ] `/` lists only published agencies, with listing counts.
- [ ] Entering an agency opens its home page.
- [ ] Every menu item opens the right page and shows as current.
- [ ] Language switch keeps the page and works for `id`, `en`, `ja`.
- [ ] Unpublished or unknown slugs give 404.
- [ ] No JavaScript: the notice and the agency phone are visible.

## 3. Listings
- [ ] Sale / rent and type filters, sort and count work.
- [ ] The empty state appears when nothing matches.
- [ ] Listing detail shows photos, specs, amenities, price (rent per year).
- [ ] Previous / next and similar listings work.
- [ ] A sold-out listing shows the notice and cannot be viewed.

## 4. Mortgage
- [ ] Only sale listings show the simulator.
- [ ] Down payment (10–90 %) and tenor (1–30) are enforced.
- [ ] Results match the database price and the agency rate.
- [ ] Disclaimer is visible.

## 5. Neighbourhood and Guide
- [ ] Places list, detail, distance and photo.
- [ ] Guide tabs show the matching categories.
- [ ] "Ask Rumi" buttons open the drawer with the question.

## 6. Advisor
- [ ] Greeting and quick actions appear.
- [ ] Asking for a house with a budget returns real cards.
- [ ] "This house" on a listing page refers to that listing.
- [ ] Mortgage questions call the simulator (figures match the page).
- [ ] Unknown facts lead to "an agent will confirm" or a handover.
- [ ] Replies follow the language of the message (id, en, ja).
- [ ] Off-topic requests (translation, role-play, general questions) are declined.
- [ ] Offensive messages get the fixed refusal.
- [ ] Prices not from a tool are not stated.
- [ ] History is restored after a reload.
- [ ] 429 shows the "too quickly" message; a model outage shows Retry and Talk to an agent.

## 7. Viewing
- [ ] Dates before tomorrow and after 60 days are rejected.
- [ ] Taken times are not offered; a race returns the remaining slots.
- [ ] Invalid phone or email is rejected.
- [ ] A request returns a reference and prefilled WhatsApp / phone / email links.
- [ ] The draft survives a language switch.
- [ ] The advisor can create a viewing and the admin sees it.

## 8. Handover
- [ ] Asking for an agent creates an open handover with a useful summary.
- [ ] While handed over, the AI stays silent and the visitor sees the agent notice.
- [ ] An agent reply appears in the visitor's chat.
- [ ] Resolve returns the conversation to the AI.

## 9. Admin
- [ ] Admin screens redirect guests to login.
- [ ] Staff only see their own agency's data; foreign records return 404.
- [ ] Create, edit and delete a property; photos by URL; sold-out behaviour.
- [ ] Knowledge items accept only http(s) image URLs.
- [ ] Viewing status changes free or hold the slot.
- [ ] Login is throttled after five attempts.

## 10. Responsive
Check 1920×1080, 1440×900, 1366×768, 1024×768, 768×1024, 390×844 and 360×800:
- [ ] no horizontal scroll
- [ ] menu becomes bottom navigation on mobile
- [ ] advisor drawer usable on mobile
- [ ] viewing wizard fits

## 11. Sound and Narration
- [ ] Nothing plays before a visitor action.
- [ ] The toggle mutes and is remembered.
- [ ] Listen reads the brief in the page language; Stop works.
- [ ] Reduced-motion disables the page transition.

## 12. Browsers
- [ ] Chrome
- [ ] Safari
- [ ] Edge
- [ ] Mobile Safari
- [ ] Mobile Chrome

## 13. Deployment
- [ ] `npm run build` and `php artisan migrate --force` succeed.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] The demo seeder does not run in production.
- [ ] The server can reach `LOCAL_LLM_BASE_URL`.
- [ ] The advisor stays within the 85-second budget.
- [ ] No secrets in the frontend source or the repository.
