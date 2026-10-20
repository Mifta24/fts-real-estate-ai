# FTS REAL ESTATE AI — API & Backend Specification

## 1. Main Principle
The public site is server-rendered Blade with small JavaScript modules. The backend adds JSON endpoints for the advisor, viewing slots, viewing requests and the mortgage simulator. There is no `/api` prefix and no API versioning; routes are in `routes/web.php` and use the web session and CSRF token.

Only agencies with `public_status = published` are reachable; others return 404.

## 2. Public Pages (GET)

| Path | Route name | Notes |
|---|---|---|
| `/` | `home` | Agency picker |
| `/{agencySlug}` | `agency.home` | |
| `/{agencySlug}/listings` | `agency.listings` | |
| `/{agencySlug}/listings/{propertySlug}` | `agency.listing` | |
| `/{agencySlug}/area` | `agency.area` | |
| `/{agencySlug}/area/{placeId}` | `agency.place` | numeric id |
| `/{agencySlug}/guide` | `agency.info` | |
| `/{agencySlug}/agents` | `agency.agents` | |
| `/{agencySlug}/viewing` | `agency.viewing` | |

The language comes from `?lang=` (`id`, `en`, `ja`), otherwise the agency's `default_locale`.

## 3. Advisor

Rate limits are defined in `AppServiceProvider`.

### POST `/{agencySlug}/advisor/start` (`advisor-start`: 10/min per IP)
Request: `{ "locale": "en" }` (optional)

Response:
```json
{ "visitor_token": "uuid", "locale": "en" }
```
The token is kept in `localStorage` and identifies the conversation.

### POST `/{agencySlug}/advisor/message` (`advisor-message`: 12/min per token, 40/min per IP)
Request:
```json
{
  "visitor_token": "uuid",
  "message": "3-bedroom house under 2.5 billion",
  "scene": "listings",
  "selected_property": "rumah-cluster-asri-greenwich",
  "selected_place": 4,
  "viewing": { "property_slug": "...", "date": "2026-11-02", "time": "10:30", "party_size": 2, "payment_plan": "mortgage" }
}
```
`scene` must be one of `home, listings, listing_detail, area, area_detail, info, viewing, agents`. `message` is limited to 2000 characters.

Response:
```json
{
  "message": {
    "role": "assistant",
    "content": "…",
    "ui_payload": [ { "type": "property_results", "properties": [ … ] } ],
    "suggested_actions": [ { "action": "schedule", "property": "slug" } ],
    "created_at": "2026-10-20T10:00:00+07:00"
  },
  "status": "active"
}
```
`ui_payload` types: `property_results`, `property_detail`, `mortgage`, `viewing_slots`, `viewing_confirmation`, `handover`.

On failure: HTTP 503 `{ "error": "advisor_unavailable", "message": "…" }`.

### GET `/{agencySlug}/advisor/history` (`advisor-history`: 60/min per IP)
Query: `visitor_token`. Returns `{ "messages": [...], "status": "active|handed_over|closed" }` including agent replies, so a handed-over visitor sees them.

## 4. Viewing (throttle 30/min)

### POST `/{agencySlug}/viewing/slots`
Request: `{ "property_slug", "date": "YYYY-MM-DD", "locale" }`
Response: `{ "date", "slots": ["09:00", …], "all_slots": [...] }`

### POST `/{agencySlug}/viewing`
Request: `property_slug`, `date`, `time`, `visitor_name`, `contact_type`, `contact_value`, `party_size`, `payment_plan`, `notes?`, `visitor_token?`, `locale?`

Response `201`:
```json
{ "reference": "SV-ABC234", "status": "pending",
  "contact": { "whatsapp_url": "…", "phone_url": "…", "email_url": "…" } }
```
Errors: `422` with `errors` per field. A slot taken in the meantime returns `errors.time` and the remaining `slots`.

## 5. Mortgage (throttle 30/min)

### POST `/{agencySlug}/mortgage`
Request: `{ "property_slug", "down_payment_percent": 20, "tenor_years": 15 }` (10–90 %, 1–30 years)

Response: price, down payment (percent and amount), loan amount, tenor, annual rate, monthly instalment, total payment, total interest, `minimum_monthly_income` (instalment divided by 0.3) and `currency`. Only listings for sale; others return 404. The price always comes from the database.

## 6. Admin (`/admin`, session auth)

| Path | Purpose |
|---|---|
| `login` (guest), `logout` | `POST login` is limited to 5 attempts/min |
| `/` | Dashboard |
| `properties` (resource, no show) | Listings |
| `knowledge-items` (resource, no show) | Knowledge base |
| `viewings`, `viewings/{viewing}/status` (PATCH) | Viewing requests |
| `handovers`, `handovers/{handover}`, `…/reply`, `…/resolve` | Handover queue |

Every admin action resolves the user's current agency (`User::currentAgency()`, first active membership). Records of another agency return 404; users without an agency get 403.

## 7. Advisor Flow

```text
Receive message
    ↓
Validate, rate limit
    ↓
Remember page context (scene, selection, viewing draft)
    ↓
ContentGuard on the message → fixed refusal if offensive
    ↓
Build system prompt + history + scope reminder
    ↓
Call the OpenAI-compatible endpoint (reasoning_effort=none)
    ↓
Tool-calling loop (max 6 rounds) via PropertyAdvisorTools
    ↓
ContentGuard on the reply; unbacked price check
    ↓
Save message with ui_payload and tool_calls
    ↓
Return message + suggested actions
```

The whole turn has an 85-second budget (Cloudflare closes at 100 s). While a conversation is `handed_over`, the visitor's message is saved and answered only with a system notice ("This conversation is with a property agent now…"); the AI stays out until the agent resolves the handover.

## 8. Configuration
`config/services.php` → `local_llm`: `LOCAL_LLM_BASE_URL`, `LOCAL_LLM_API_KEY`, `LOCAL_LLM_MODEL`. Without them the pages work but the advisor returns 503.

## 9. Security
- admin authentication and per-agency scoping on every query
- rate limits on advisor, viewing, mortgage and login
- server-side validation of every input; prices never come from the browser
- image URLs restricted to `http` / `https`
- model credentials only on the server
- conversations are matched on both agency and visitor token

## 10. Logging
Advisor failures are logged with the agency id and error message. Tool calls are stored with each assistant message (`tool_calls`) for later review.
