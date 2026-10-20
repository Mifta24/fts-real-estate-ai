# FTS REAL ESTATE AI — Knowledge Base Structure

## 1. Purpose
The knowledge base is the approved source of agency facts used by the advisor and by the Neighbourhood and Guide pages. It lives in the `agency_knowledge_items` table and is managed at `/admin/knowledge-items`.

Listings are **not** part of the knowledge base; they are structured data (see `08_DATABASE_SCHEMA.md`).

## 2. Entry Structure

| Field | Meaning |
|---|---|
| `category` | One of the categories below |
| `title`, `body` | Content in the agency's default language |
| `translations` | Optional `en` and `ja` title and body; the default is used when empty |
| `tags` | Keywords (comma separated in the admin form) used by search and icons |
| `image_url` | Optional photo; must be an `http` or `https` URL |
| `distance` | Free text such as "1.2 km · 5 minutes", for neighbourhood places |
| `is_active` | Only active entries are used |
| `sort_order` | Display and search order |

## 3. Categories

| Category | Used for | Shown on |
|---|---|---|
| `general` | Agency profile, services, how the agency works | Guide |
| `neighbourhood` | Schools, hospitals, malls, toll roads, stations, parks, airport | Neighbourhood pages |
| `buying` | Buying and renting process, booking fee, handover of keys | Guide → Process |
| `financing` | KPR, down payment, bank partners, required documents | Guide → Financing |
| `legal` | Certificates (SHM, HGB), AJB, taxes, fees | Guide → Legal |
| `faq` | Frequent questions | Guide → FAQ |

Neighbourhood entries get an icon from their title and tags (school, health, mall, road, train, park, airport, or a pin).

## 4. How the Advisor Uses It
`search_knowledge` takes a query and an optional category, matches the query (and its words longer than two characters) against title, body and tags of active entries, and returns up to five results translated into the visitor's language. When nothing matches, the tool tells the model not to guess.

There is no separate index to refresh: a saved change is used on the next message.

## 5. Writing Guidelines
- One topic per entry.
- State facts that are verified; include numbers (fees, percentages, distances) only when they are true today.
- Put the words visitors use in `tags` ("sekolah", "school", "KPR").
- Provide `en` and `ja` translations for entries visitors will ask about.
- Deactivate instead of deleting when information is temporarily wrong.

## 6. Conflicting Information
Structured data wins over the knowledge base: prices, stock and viewing slots always come from the property and viewing tools. Inside the knowledge base, edit the existing entry instead of adding a second one that contradicts it.

## 7. Agency Profile Data
Contact data, office hours, currency, timezone, default language and the reference mortgage rate are columns of the `agencies` table (name, address, phone, WhatsApp, email, `office_opens_at`, `office_closes_at`, `mortgage_rate`, translations of tagline and description). They feed the Guide page, contact buttons and the advisor prompt.
