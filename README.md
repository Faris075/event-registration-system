# Event Registration System

A full-stack Laravel 11 web application for event discovery, attendee registration, waitlist management, and admin controls — built as part of the MIMO Codes Developer Assessment 2026.

---

## Table of Contents

1. [Features Implemented](#features-implemented)
2. [Tech Stack](#tech-stack)
3. [Setup Instructions](#setup-instructions)
4. [Database Configuration](#database-configuration)
5. [Admin Credentials](#admin-credentials)
6. [Application Flow](#application-flow)
7. [Approach & Architecture](#approach--architecture)
8. [Challenges Faced & How They Were Solved](#challenges-faced--how-they-were-solved)
9. [Future Improvements](#future-improvements)

---

## Features Implemented

### Public / Attendee
- Browse upcoming published events (paginated, with spot count and price)
- View event detail page with remaining capacity and price in user's preferred currency
- Multi-step registration flow: details → mock payment → confirmation
- Waitlist system: auto-placed when event is full; promoted automatically on cancellation
- "Already Registered" badge replaces the Register button on event cards
- Cancel own registration with a styled confirmation modal; paid registrations auto-marked as refunded
- Same-day conflict warning when registering for overlapping events
- Security question setup and 6-step password recovery (email → question → answer → reset)
- Preferred display currency selector (USD, EUR, GBP, SAR, AED, JPY, CAD, AUD, EGP)
- "Try Another Way" collapsible panel on the forgot-password page

### Admin
- Full event CRUD (create, edit, update, delete)
- Event cancellation sends bulk `EventCancelledMail` to all confirmed/waitlisted attendees
- Admin registration listing with status/payment filters
- Admin force-add (bypass capacity, up to 5 overrides per event)
- Registration status management with automatic waitlist promotion on cancellation
- CSV export of registrations with active filters applied
- User management: promote users to admin or delete accounts
- Scheduled artisan command (`events:mark-completed`) marks past events as completed

### Security & Reliability
- Pessimistic locking (`lockForUpdate`) inside DB transactions prevents double-booking race conditions
- DB-level unique index on `(event_id, attendee_id)` as a second layer of duplicate-prevention
- Mail failures caught with `report()` — never abort a successful registration
- Session-gated multi-step flows prevent step-skipping
- Anti-enumeration on password recovery (same error for missing email and missing security question)
- Payment immutability rule: `paid` status cannot be reverted by admin

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2, Laravel 11 |
| Frontend | Blade templates, custom CSS (CSS custom properties + dark/light mode), Vite |
| Database | MySQL (SQLite supported for local dev) |
| Mail | SMTP (configurable via `.env`; Mailtrap recommended for local dev) |
| Testing | Pest / PHPUnit, GitHub Actions CI |

---

## Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL 8+ (or SQLite for quick local setup)

### 1. Clone the repository

```bash
git clone https://github.com/Faris075/event-registration-system.git
cd event-registration-system/EventRegistrationSystem/Event_Registation_System
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure database

Edit `.env` with your database credentials (see [Database Configuration](#database-configuration) below).

### 5. Run migrations and seed

```bash
php artisan migrate --seed
```

This creates all tables and seeds:
- One admin user (`CreateAdminUserSeeder`)
- 8 real-world concert events (`EventSeeder`)
- 10 sample regular users with registrations (`AttendeeRegistrationSeeder`)

### 6. Build frontend assets

```bash
npm run build
# or for development with hot reload:
npm run dev
```

### 7. Start the development server

```bash
php artisan serve
```

Visit `http://127.0.0.1:8000`

### 8. (Optional) Run the scheduler manually

```bash
php artisan events:mark-completed
```

---

## Database Configuration

Edit the following keys in your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_registration
DB_USERNAME=root
DB_PASSWORD=your_password
```

**For SQLite (quickest local setup):**

```env
DB_CONNECTION=sqlite
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD can be removed or left blank
```

Then create the file:
```bash
touch database/database.sqlite
```

**Mail configuration (Mailtrap recommended for local dev):**

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=noreply@eventregistration.com
MAIL_FROM_NAME="Event Registration System"
```

**Schema overview:**

| Table | Purpose |
|-------|---------|
| `users` | Authentication identities with admin flag, currency preference, security question |
| `attendees` | Contact records (name, email, phone, company); linked to users by email |
| `events` | Event metadata (title, location, datetime, capacity, price, status) |
| `registrations` | Join table: event ↔ attendee with status, payment_status, waitlist_position |

---

## Admin Credentials

Seeded automatically by `CreateAdminUserSeeder`:

| Field | Value |
|-------|-------|
| **Email** | `Farisnabil075@gmail.com` |
| **Password** | `Admin@1234` |
| **Role** | Admin |

> To re-seed without losing data: `php artisan db:seed --class=CreateAdminUserSeeder`
> This uses `updateOrCreate` so it is safe to run multiple times.

---

## Application Flow

```
/ (home)  →  /login or /register
                    ↓
             /events  (browse listing)
                    ↓
        /events/{id}  (event detail)
                    ↓
    /events/{id}/register  (step 1: attendee details)
                    ↓
    /events/{id}/payment   (step 2: mock card payment)
                    ↓
    /events/{id}/registration-confirmation  (step 3: success)
                    ↓
             /dashboard  (view + cancel registrations)
```

Password recovery flow:
```
/forgot-password  →  "Try Another Way"
        ↓
/recover-password          (enter email)
/recover-password/answer   (answer security question)
/recover-password/reset    (set new password)
        ↓
Auto-login → /dashboard
```

---

## Approach & Architecture

The project follows **standard Laravel MVC** with deliberate separation between authentication identity (`User`) and event contact records (`Attendee`). This allows a single person to register as an attendee without needing a user account, while still linking history when they are logged in — via a shared email field.

**Session-staged registration** was chosen over single-step form submission so that no orphaned DB records are created if a user abandons the payment page. Attendee and Registration rows are only written after payment validation succeeds (Step 4), with session data cleared immediately after.

**Pessimistic locking** (`SELECT ... FOR UPDATE` inside `DB::transaction`) was used for all capacity decisions. This is more appropriate than optimistic locking here because the window between "check capacity" and "insert registration" must be atomic under concurrent load — something optimistic locking cannot guarantee without retries.

**Shared controller helpers** (`cancelAndPromote`, `sameDayConflicts`) extract logic used by both admin and user-facing flows to avoid duplication and keep individual action methods small and readable.

**CSS custom properties** with a `html.dark` class toggle provide the theme system, with `localStorage` persisting user preference across reloads. The default was intentionally set to light mode for readability.

---

## Challenges Faced & How They Were Solved

### 1. Race conditions in registration (double-booking)
**Problem:** Two users could simultaneously pass the "seats available" check and both create confirmed registrations, exceeding capacity.  
**Solution:** Wrapped all capacity checks and `Registration::create()` calls in `DB::transaction()` with `lockForUpdate()` on the event row. This acquires a row-level exclusive lock, forcing concurrent requests to queue and re-read count after the first commit.

### 2. Waitlist position consistency
**Problem:** `cancelAndPromote()` needed to atomically cancel one registration *and* promote the next waitlisted attendee without another request sneaking in between.  
**Solution:** Both the cancellation update and the promotion update run inside the same transaction, with `lockForUpdate()` on both the cancelled row and the promoted row. The `COALESCE(waitlist_position, 999999)` ordering handles any rows where position is null.

### 3. Attendee / User identity gap
**Problem:** The `Attendee` model (contact record) and `User` model (auth identity) are separate tables. Linking "is this logged-in user already registered?" required joining them without a direct FK.  
**Solution:** Both tables share an `email` column. `EventRegistrationController` and `EventController` look up `Attendee::where('email', Auth::user()->email)` as the linking key. `updateOrCreate` on `Attendee` keeps the contact record up-to-date without creating duplicates.

### 4. Keeping fully-booked events visible for waitlist signup
**Problem:** The original event listing query filtered out fully-booked events entirely, making the "Join Waitlist" button unreachable from the listing page.  
**Solution:** Changed the `whereRaw` clause to an OR condition: show the event if `confirmed < capacity` OR if `waitlisted < waitlistCapacity` (25% of capacity). Both sub-queries use bound parameters to prevent SQL injection.

### 5. Mail failures breaking flows
**Problem:** A mail-driver failure (e.g. SMTP timeout) at the end of registration would return a 500 to the user even though the DB record was already committed.  
**Solution:** Wrapped all `Mail::to()->send()` calls in `try/catch (\Throwable)` blocks. On failure, `report($exception)` logs to Laravel's error channel (and forwards to Sentry/Bugsnag if configured) without re-throwing, so the user always sees the confirmation page.

### 6. Duplicate class declaration breaking the controller
**Problem:** During annotation, a second `class EventRegistrationController` declaration was accidentally introduced, causing a PHP fatal error.  
**Solution:** Isolated the duplicate lines with PowerShell, trimmed the file to the correct line range, and stripped the inadvertently introduced UTF-8 BOM using `[System.IO.File]::WriteAllText` with `new UTF8Encoding($false)`.

---

## Future Improvements

- **Queued mailables** — move `RegistrationConfirmationMail`, `WaitlistPromotedMail`, and `EventCancelledMail` to Laravel queues so email sending is non-blocking and retried on failure
- **Real payment gateway** — swap the mock card form for Stripe or PayPal using webhook-confirmed payment status rather than trusting the client-side form
- **Soft deletes** on `Event` and `Registration` for a full audit trail; hard deletes currently lose history permanently
- **Policy-based authorisation** — replace `is_admin` boolean flag checks with Laravel Gates/Policies for finer-grained per-resource permissions
- **Rate limiting** on registration and password recovery routes to prevent abuse
- **Event image upload** — allow admins to attach a cover image to events
- **Ticket/QR code generation** — generate a unique QR code per confirmed registration for event check-in
- **Broader test coverage** — add feature tests for the full registration flow, cancellation, waitlist promotion, and security question recovery
- **API layer** — expose a JSON API for the event listing and registration flow to support a future mobile app
