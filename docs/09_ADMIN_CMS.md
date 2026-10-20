# FTS REAL ESTATE AI — Admin CMS

## 1. Objective
Let an agency manage everything the showroom and the advisor use without touching code. The panel is at `/admin` (login at `/admin/login`).

## 2. Access and Roles
- Staff sign in with email and password; five failed attempts per minute are throttled.
- A user belongs to one or more agencies through `agency_users` with a role (`owner` or `agent`) and a status. The panel works on the first active agency of the user.
- Every query is scoped to that agency; another agency's records return 404, and a user without an agency gets 403.
- Roles currently do not change what the panel allows.

## 3. Modules

### Dashboard
Active listings, knowledge items, pending viewings and open handovers; the next upcoming viewings; the latest open handovers.

### Properties (`/admin/properties`)
List (featured first), create, edit, delete (soft delete).

Fields: name (slug is generated once and kept), listing type, property type, district, price, building and land size, bedrooms, bathrooms, carports, floors, certificate, furnishing, year built, amenities (comma separated, stored as keys), units available, featured, active, sort order, English and Japanese name and description.

Photos: add by URL, delete, ordered. Setting `units_available` to 0 shows "Sold out" and closes viewings for that listing.

### Knowledge items (`/admin/knowledge-items`)
CRUD for the six categories (see `05_KNOWLEDGE_BASE_STRUCTURE.md`): title, body, `en` / `ja` translations, tags, distance, image URL (http or https only), active, sort order.

### Viewings (`/admin/viewings`)
Filter by status; see reference, property, visitor, date, time, contact, payment plan and notes. Actions: mark `confirmed`, `completed` or `cancelled`. Pending and confirmed requests hold their slot.

### Handovers (`/admin/handovers`)
Open and resolved lists. The detail page shows the reason, the summary and the whole conversation. Actions:
- **Reply**: stored as a message with role `agent`; the visitor sees it in the chat.
- **Resolve**: marks the request resolved, records who resolved it, and returns the conversation to the AI.

## 4. Not Managed Here
The agency profile (name, contact, office hours, currency, mortgage rate, translations, cover image, publish status) and user accounts are edited in the database or seeders for now. Image upload is not available; use image URLs.

## 5. Language
The admin interface is in English. Content can carry Indonesian (default), English and Japanese texts.

## 6. Priorities
Must have: properties, knowledge items, viewings, handovers.
Next: agency profile editor, image upload, role-based permissions, conversation log browser.
