# FTS REAL ESTATE AI — Viewing Flow

The product does not take reservations or payments. The equivalent of a booking is a **viewing request** (a site visit), handled by `ViewingService`, which both the wizard and the advisor use so they can never disagree.

## 1. UX Principle
A viewing is requested inside the showroom through a four-step wizard (`/{agencySlug}/viewing`) or through the advisor. The visitor is never sent to an external form.

## 2. Entry Points
- Menu item "Book a viewing"
- "Book a viewing" button on a listing
- Advisor cards after a listing detail or a mortgage simulation
- The advisor's `create_viewing_request` tool

## 3. Fields

Required:
- property (an active listing that is still available)
- viewing date
- viewing time
- name
- contact method (`whatsapp`, `phone` or `email`) and its value
- number of visitors
- payment plan (`cash`, `mortgage`, `undecided`)

Optional: notes (max 500 characters).

## 4. Step Flow
1. **Property** — choose a listing.
2. **Schedule** — choose a date; free times load from `POST /{agencySlug}/viewing/slots`.
3. **Your details** — name, contact, visitors, payment plan, notes.
4. **Summary** — review, Edit, Send.

## 5. Rules

| Rule | Value |
|---|---|
| Time slots | 09:00, 10:30, 13:00, 14:30, 16:00 |
| Earliest date | tomorrow (agency timezone) |
| Latest date | 60 days ahead |
| Max visitors | 6 |
| Slot capacity | one request per listing, date and time |
| Counted as taken | `pending` and `confirmed` |

Phone numbers must match `+?[0-9 -().]{6,20}`; emails must be valid.

## 6. Availability
Slots are computed server side from existing requests. When a request is created, the slot is re-checked inside a transaction with a row lock. If it was taken in the meantime the API answers 422 with the remaining slots, and the visitor picks another time. Sold-out or inactive listings have no slots.

## 7. Result
The response contains:
- `reference` (`SV-` plus six characters)
- `status` (`pending`)
- contact links: WhatsApp (`wa.me`), phone (`tel:`), email (`mailto:`), each prefilled with the summary in the visitor's language

The page states: "This is a viewing request. Our agent will confirm it. No payment is taken."

## 8. WhatsApp Message
Prefilled text (localised):

```text
Hello, I would like to schedule a property viewing.

Name: {visitor_name}
Property: {property}
Date: {date}
Time: {time}
Visitors: {party_size}
Payment plan: {payment_plan}
Notes: {notes}

Reference: {reference}
```

## 9. Statuses
`pending` → `confirmed` → `completed`, or `cancelled`. Agents change the status in `/admin/viewings`; pending and confirmed requests hold the slot, completed and cancelled ones free it.

## 10. Payment Rule
Never collect card or bank data in the chat or the wizard. Payment-related questions are handed over to an agent (`payment_issue`).

## 11. Draft Persistence
The wizard keeps its step and values in `sessionStorage`, so a language switch or a page change does not lose them. The advisor also receives the draft so it can help with what is on screen.
