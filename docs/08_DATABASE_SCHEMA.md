# FTS REAL ESTATE AI — Database Schema

SQLite by default. Migrations are in `database/migrations/` (`2026_09_17_0154xx_*`). Session, cache and queue use the `database` driver.

## 1. Core Tables

### agencies
`id`, `name`, `slug` (unique), `custom_domain` (unique, nullable), `tagline`, `description`, `translations` (json: `en`/`ja` tagline and description), `address`, `city`, `country`, `latitude`, `longitude`, `phone`, `whatsapp`, `email`, `timezone` (Asia/Jakarta), `currency` (IDR), `default_locale` (id), `office_opens_at`, `office_closes_at`, `mortgage_rate` (default 7.5), `logo_path`, `cover_path`, `public_status` (`draft` | `published`), soft deletes, timestamps.

### agency_users
`agency_id`, `user_id`, `role` (`owner` | `agent`), `status` (`active`), unique on agency + user. Pivot model `AgencyUser`.

### properties
`agency_id`, `name`, `slug` (unique per agency), `description`, `translations` (json), `listing_type` (`sale` | `rent`), `property_type` (`house`, `townhouse`, `apartment`, `shophouse`, `land`), `district`, `price` (decimal 15,2; rent is per year), `building_size_sqm`, `land_size_sqm`, `bedrooms`, `bathrooms`, `carports`, `floors`, `certificate` (`shm`, `hgb`, `strata`, `girik`), `furnishing` (`unfurnished`, `semi`, `full`), `year_built`, `amenities` (json list of keys), `units_available`, `is_featured`, `is_active`, `sort_order`, soft deletes.

A property is available when it is active and has units left (`isAvailable()`).

### property_images
`property_id`, `image_path`, `image_url`, `tags` (json: kitchen, bedroom, exterior, pool …), `alt_text`, `sort_order`. The admin currently adds images by URL.

### agency_knowledge_items
`agency_id`, `category` (`general`, `neighbourhood`, `buying`, `financing`, `legal`, `faq`), `title`, `body`, `translations`, `tags`, `image_url`, `distance`, `is_active`, `sort_order`. Index on agency + category.

### conversations
`agency_id`, `visitor_token` (uuid, unique), `visitor_name`, `visitor_email`, `locale`, `status` (`active` | `handed_over` | `closed`), `current_scene`, `selected_property_id`, `selected_place_id` (→ knowledge item), `viewing_state` (json draft), `handover_summary`, `last_message_at`.

### conversation_messages
`conversation_id`, `role` (`visitor`, `assistant`, `agent`, `system`), `content`, `ui_payload` (json cards), `tool_calls` (json log), timestamps.

### viewing_appointments
`reference` (unique, `SV-XXXXXX`), `agency_id`, `property_id`, `conversation_id` (nullable), `visitor_name`, `visitor_email`, `visitor_phone`, `contact_type` (`whatsapp`, `phone`, `email`), `viewing_date`, `viewing_time` (`HH:MM`), `party_size`, `payment_plan` (`cash`, `mortgage`, `undecided`), `status` (`pending`, `confirmed`, `completed`, `cancelled`), `notes`. Index on property + date + time.

### handover_requests
`conversation_id`, `reason` (`price_negotiation`, `mortgage_help`, `legal_question`, `special_request`, `complaint`, `payment_issue`, `low_confidence`), `summary`, `status` (`open` | `resolved`), `assigned_to` (user), `resolved_at`.

### users, cache, jobs
Laravel defaults.

## 2. Relationships

```text
Agency ─┬─< Property ──< PropertyImage
        ├─< AgencyKnowledgeItem
        ├─< Conversation ─┬─< ConversationMessage
        │                 ├─< ViewingAppointment (optional link)
        │                 └─< HandoverRequest
        ├─< ViewingAppointment >─ Property
        └─>< User (agency_users: role, status)
```

## 3. Scene State
Stored on the conversation: `current_scene`, `selected_property_id`, `selected_place_id` and `viewing_state`. They are refreshed on every advisor message and put into the prompt.

## 4. Seed Data
`DemoAgencySeeder` (local and testing only) creates the owner `owner@ftsrealty.test`, the agency **FTS Realty** (`fts-realty`), seven listings (houses, townhouse, apartments, a shophouse and a land plot, for sale and for rent) with photos by URL, and knowledge items for each category.

## 5. Not Stored
Payments, mortgage applications, visitor accounts and uploaded files do not exist in this schema.
