# FTS REAL ESTATE AI — User Flow

## 1. Global User Flow

```text
Open "/"
    ↓
Choose agency  →  "/{agencySlug}"
    ↓
Home: hero prompt + featured listings
    ↓
Visitor:
    ├── asks Rumi
    ├── browses listings
    ├── reads the neighbourhood
    ├── reads the guide
    ├── books a viewing
    └── asks for an agent
```

## 2. Opening Flow
1. The opening page lists every published agency with its city and active listing count.
2. The visitor chooses an agency and enters its showroom.
3. If no agency is published, a "showroom is being prepared" message is shown.

## 3. Home Flow
- The hero offers a free-text prompt ("Tell us what you are looking for") and example questions. Submitting it opens the advisor drawer with that question.
- Featured listings link to their detail pages.
- Each page change plays a short door chime and a one-line caption from the advisor (if sound is on).

## 4. Listing Flow

```text
Listings
    ↓
Filter: sale / rent, property type; sort
    ↓
Open a listing
    ↓
Gallery · specs · amenities · KPR simulator
    ↓
Visitor can:
    ├── Ask Rumi about this listing
    ├── Discuss the mortgage with Rumi
    ├── Book a viewing
    ├── Go to the previous / next listing
    └── Back to all listings
```

Filtering is client side: every card is already in the page.

## 5. Mortgage Flow
- Only listings for sale have a simulator.
- The visitor chooses the down payment percentage and tenor; the server calculates the result from the listing price and the agency's rate. The browser never sends a price.
- The result is indicative and always shows a disclaimer.

## 6. Neighbourhood and Guide Flow
- Neighbourhood lists active knowledge items of category `neighbourhood`; each has a detail page with distance and travel time.
- The guide shows the agency profile and tabs for process (`buying`), `financing`, `legal` and `faq`.
- Both offer "Ask Rumi" buttons that pass a question to the advisor.

## 7. Viewing Flow

```text
Choose property
    ↓
Pick date → free times appear
    ↓
Name · contact method · number of visitors · payment plan · notes
    ↓
Summary
    ↓
Send viewing request → reference "SV-XXXXXX"
    ↓
Send summary to the agent on WhatsApp / call / email
```

The draft survives a language switch or page change (kept in `sessionStorage`).

## 8. Human Handover Flow

Triggers:
- visitor asks for an agent
- price negotiation or offer
- mortgage application help
- legal question not in the knowledge base
- special request, complaint or payment problem
- the advisor is not confident

```text
Advisor calls request_human_handover(reason, summary)
    ↓
Conversation status → handed_over, handover request created (open)
    ↓
Agent opens it in /admin/handovers, reads the summary and history
    ↓
Agent replies from the panel; the visitor sees "An agent has replied"
    ↓
Agent resolves it → conversation returns to the AI
```

The Agents page also offers WhatsApp, phone and email buttons.

## 9. Back Navigation
Every page is reachable from the numbered menu (and the bottom navigation on mobile). Listing and place pages have previous/next and "back to all" links. The conversation survives page changes through a visitor token stored in `localStorage`.

## 10. Unknown Question Flow

```text
Visitor asks something outside the data
    ↓
Advisor searches the knowledge base
    ↓
Nothing found → says an agent will confirm, or hands over
```

The advisor never guesses.

## 11. Off-topic or Offensive Messages
- Off-topic requests (general knowledge, translation, role-play, calculations unrelated to the property) are declined briefly with an offer of what the advisor can do.
- Rude, sexual or illegal messages are answered with a fixed refusal by the code, without reaching the model.

## 12. Error Flow
If the model server fails or is slow, the chat shows a short error with Retry and the "Talk to an agent" option. The visitor's message is kept; the page stays where it is.

## 13. No Matching Listing
The advisor says no exact match exists, suggests nearby alternatives from the search results, and offers an agent.
