# Non-Functional Requirements

This document specifies the quality attributes and constraints that govern how the Event Registration System behaves, independent of specific features or business logic.

---

## 1. Performance

| ID | Requirement |
| --- | --- |
| NFR-P01 | Event listing pages shall load within an acceptable time by paginating results rather than fetching the entire events table in a single query. |
| NFR-P02 | Relationship data (registrations, attendees) shall be loaded via Eloquent eager loading (`with()`) to prevent N+1 query problems. |
| NFR-P03 | Email confirmation jobs shall be dispatched to a background queue so that the HTTP response is returned to the user immediately, without waiting for mail delivery. |
| NFR-P04 | Database indexes shall be defined on foreign-key columns (`event_id`, `attendee_id`) and frequently filtered columns (`status`, `email`) to keep query execution times consistent as data grows. |

---

## 2. Security

| ID | Requirement |
| --- | --- |
| NFR-S01 | All forms shall include Laravel's CSRF token protection (`@csrf`) to prevent cross-site request forgery attacks. |
| NFR-S02 | Admin-only routes shall be protected by a dedicated `admin` middleware that aborts with HTTP 403 if the authenticated user does not hold the admin role. |
| NFR-S03 | User passwords shall be stored as bcrypt hashes (rounds configurable via `BCRYPT_ROUNDS`) and never in plain text. |
| NFR-S04 | Security question answers shall be stored as hashed values and compared with `Hash::check()`, ensuring they are never stored or compared in plain text. |
| NFR-S05 | Input from all request forms shall be validated using Laravel Form Request classes or inline `$request->validate()` calls before any database write occurs. |
| NFR-S06 | Route-model binding shall be used for all resource routes so that accessing a non-existent resource returns HTTP 404 automatically. |
| NFR-S07 | The application key (`APP_KEY`) shall be kept secret and never committed to version control; it is loaded exclusively from the `.env` file. |

---

## 3. Reliability & Data Integrity

| ID | Requirement |
| --- | --- |
| NFR-R01 | All multi-step state changes (registration creation combined with waitlist position assignment, cancellation combined with waitlist promotion) shall be wrapped in a single `DB::transaction()` to ensure atomicity. |
| NFR-R02 | Pessimistic locking (`lockForUpdate()`) shall be applied when reading the waitlist head inside a transaction to prevent concurrent requests from promoting the same record twice. |
| NFR-R03 | Database migrations shall enforce referential integrity via foreign-key constraints (`ON DELETE CASCADE` where appropriate). |
| NFR-R04 | Enum-typed columns (`status` on `events` and `registrations`) shall restrict values to the defined set at the database level, preventing invalid states. |
| NFR-R05 | The scheduled command that marks past events as `completed` shall be idempotent — running it multiple times shall not corrupt already-completed records. |

---

## 4. Maintainability

| ID | Requirement |
| --- | --- |
| NFR-M01 | The application shall follow the MVC pattern: controllers handle HTTP concerns, models encapsulate business logic and relationships, and Blade views handle presentation only. |
| NFR-M02 | All core application files (controllers, models, middleware, seeders) shall contain clear, concise inline comments explaining non-obvious logic. |
| NFR-M03 | CSS design tokens (colours, spacing, shadows, border radii) shall be defined in one place (`:root` custom properties in `app.css`) so that global style changes require only a single edit. |
| NFR-M04 | Environment-specific configuration (database credentials, mail settings, app URL) shall be managed exclusively through the `.env` file and never hard-coded in source files. |
| NFR-M05 | Shared UI structure shall be encapsulated in reusable Blade layout files (`layouts/app.blade.php`, `layouts/guest.blade.php`) so that header and footer changes propagate to all pages automatically. |

---

## 5. Usability

| ID | Requirement |
| --- | --- |
| NFR-U01 | The UI shall be fully responsive and usable on mobile (≥ 375 px), tablet, and desktop viewports. |
| NFR-U02 | The system shall support both light and dark colour schemes by detecting the user's OS preference (`prefers-color-scheme: dark`) and switching CSS custom property values accordingly. |
| NFR-U03 | All interactive status indicators (event status, registration status) shall use colour-coded badges to communicate state at a glance without relying solely on colour. |
| NFR-U04 | Form validation errors shall be displayed inline, adjacent to the relevant field, so users can correct mistakes without losing other entered data. |
| NFR-U05 | Flash messages (success, error) shall be shown at the top of the relevant page immediately after an action so that the outcome is always visible to the user. |
| NFR-U06 | Redirect behaviour shall be predictable: login redirects to the events listing; registration redirects to the confirmation page; admin actions redirect back to the relevant management page. |

---

## 6. Testability

| ID | Requirement |
| --- | --- |
| NFR-T01 | The application shall include PHPUnit feature tests covering the critical registration flows: successful registration, duplicate prevention, capacity enforcement, and waitlist promotion. |
| NFR-T02 | Tests shall use an in-memory or dedicated test database (configured via `phpunit.xml`) and shall never modify the production database. |
| NFR-T03 | A GitHub Actions CI workflow shall execute the full test suite automatically on every push and every pull request to the main branch. |
| NFR-T04 | Model factories shall be provided for all core models (`User`, `Event`, `Attendee`, `Registration`) to enable test data generation without manual database seeding in tests. |

---

## 7. Scalability

| ID | Requirement |
| --- | --- |
| NFR-SC01 | Email delivery shall use Laravel's queue system so that high registration volumes do not degrade HTTP response times. The queue driver is configurable via `QUEUE_CONNECTION` in `.env`. |
| NFR-SC02 | The database schema shall use indexed foreign keys and typed enums to remain performant as the number of events, attendees, and registrations grows. |
| NFR-SC03 | The application shall not store session or cache data in the file system by default; `SESSION_DRIVER` and `CACHE_STORE` are configurable and set to `database` to support multi-server deployments. |

---

## 8. Compatibility

| ID | Requirement |
| --- | --- |
| NFR-C01 | The application shall require PHP 8.2 or higher and Laravel 12. |
| NFR-C02 | The application shall support MySQL as the primary database engine. SQLite may be used for local development or testing. |
| NFR-C03 | Frontend assets shall be compiled by Vite and shall not rely on the Vite HMR dev server in production; the built artefacts in `public/build/` shall be used. |
| NFR-C04 | The application shall function correctly in modern evergreen browsers (Chrome, Firefox, Edge, Safari). |
