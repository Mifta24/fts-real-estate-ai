# FTS REAL ESTATE AI — Project Concept

## 1. Project Name
**FTS Real Estate AI**

## 2. Product Vision
FTS Real Estate AI is an interactive property showroom for real estate agencies.

A visitor should feel they are walking into an agency's showroom and being helped by a sharp, honest property consultant — **Rumi, the AI Property Advisor** — who can pull up real listings, photos, mortgage simulations and viewing slots on demand.

This is **not** a classic listing portal with filters, long pages and a contact form. Every page is a fixed full-screen stage: a photo backdrop, a numbered menu, one content panel that scrolls on its own, and the advisor drawer that is available on every page.

One installation serves many agencies. Each agency owns its pages at `/{agencySlug}` (or a custom domain) and its own data.

## 3. Core Experience

```text
Open "/"
    ↓
Opening page (choose an agency)
    ↓
Agency home (stage + Rumi)
    ↓
Describe needs / browse
    ├── Listings → Listing detail (photos, specs, KPR simulation)
    ├── Neighbourhood → Place detail
    ├── Guide (process, financing, legal, FAQ)
    ├── Agents
    └── Book a viewing
    ↓
Human handover when needed
```

## 4. Main UX Principle
The visitor describes what they want in everyday words ("3-bedroom house under 2.5 billion near a school") and the advisor answers from controlled data. Browsing and chatting are one flow: the advisor knows which page and which listing is on screen, and the pages can hand a question to the advisor.

## 5. Pages (scenes)

| Scene | Route | Purpose |
|---|---|---|
| Opening | `/` | Pick an agency |
| Home | `/{agencySlug}` | Hero prompt, featured listings, how Rumi helps |
| Listings | `/{agencySlug}/listings` | Catalogue with sale/rent, type and sort filters |
| Listing detail | `/{agencySlug}/listings/{propertySlug}` | Gallery, specs, amenities, KPR simulator |
| Neighbourhood | `/{agencySlug}/area`, `/area/{placeId}` | Schools, hospitals, malls, stations with distances |
| Guide | `/{agencySlug}/guide` | About the agency; process, financing, legal, FAQ tabs |
| Agents | `/{agencySlug}/agents` | Handover to an agent, WhatsApp, phone, email |
| Viewing | `/{agencySlug}/viewing` | Four-step viewing request wizard |

Admin lives at `/admin`.

## 6. AI Property Advisor Role
Responsibilities:
- recommend listings from real, live data
- show photos and specifications
- run indicative KPR (mortgage) simulations
- explain the neighbourhood, buying process, financing and legal topics from the knowledge base
- check free viewing times and create viewing requests
- hand over to a human agent when needed

The advisor must never invent agency facts, prices, stock, instalments or viewing times.

## 7. Language
Indonesian (`id`), English (`en`) and Japanese (`ja`), chosen with `?lang=`. The advisor answers in the language the visitor writes in, falling back to the page language.

## 8. Current Scope

### Included
- multi-agency showroom with a fixed-stage layout
- AI advisor with tool calling against controlled data
- listing catalogue, listing detail and KPR simulator
- neighbourhood and guide pages driven by the knowledge base
- viewing request wizard with slot locking
- agent handover with replies from the admin panel
- admin panel: properties, knowledge items, viewings, handovers
- optional narrator (browser speech) and synthesised UI sounds
- deterministic content guard

### Not included
- online payment of any kind
- automatic confirmation of viewings or mortgages
- bank / developer / portal integrations
- visitor accounts
- file uploads (images are referenced by URL)

## 9. Success Criteria
A visitor can:
1. Open an agency page and see a property showroom.
2. Describe what they need and get real listings.
3. Open a listing, see photos and specifications.
4. Simulate a mortgage.
5. Read about the neighbourhood and the buying process.
6. Request a viewing and get a reference number.
7. Reach a human agent by chat handover, WhatsApp, phone or email.
