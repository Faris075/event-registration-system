
# MIMOCODES Developer Assessment Task 2026

## Task Breakdown & Implementation Checklist

Follow these smaller, ordered tasks to build a better end product. Work one item at a time and commit frequently.

### Phase 0 — Repo & Setup

- [x] Initialize Git repository and push to GitHub (create private/public repo as instructed)
- [x] Add `.gitignore`, `README.md`, and LICENSE (optional)

### Phase 1 — Dependencies & Auth

- [x] Install Composer & NPM dependencies (`composer install`, `npm install`)
- [x] Install and configure Laravel Breeze for authentication
- [x] Create initial admin user seeder and seed credentials

### Phase 2 — Database Schema

- [x] Create migrations for `events`, `attendees`, and `registrations` with required fields and indexes
- [x] Add foreign keys and enum columns as specified
- [x] Run `php artisan migrate` and verify tables exist

### Phase 3 — Models & Relationships

- [x] Create Eloquent models: `Event`, `Attendee`, `Registration`
- [x] Define relationships: `Event->registrations()`, `Attendee->registrations()`, `Registration->event()`, `Registration->attendee()`
- [x] Add scopes or accessors for `remaining_spots` and `confirmed_count`

### Phase 4 — Factories & Seeders

- [x] Create model factories for sample data
- [x] Build seeders to populate sample events, attendees, and registrations

### Phase 5 — Auth & Authorization

- [x] Implement admin role (simple `is_admin` boolean or gates/policies)
- [x] Protect admin routes with middleware

### Phase 6 — Admin CRUD & Views

- [x] Admin controllers & routes for events (index, create, edit, delete, show)
- [x] Admin view to list registrations per event and change registration status
- [x] Add export-to-CSV button if choosing export bonus

### Phase 7 — Public Site & Registration Flow

- [x] Public listing of published events, paginated
- [x] Event detail page with remaining capacity and registration form
- [x] Registration endpoint: create or reuse `Attendee`, enforce duplicate prevention

### Phase 8 — Capacity & Waitlist Logic

- [x] Implement transactional registration logic:
  - If confirmed_count < capacity → create confirmed registration
  - Else → create waitlisted registration with position order
- [x] On cancellation: promote earliest waitlisted registration to confirmed (in a transaction)

### Phase 9 — Validation & Business Rules

- [x] Add request validation for emails, basic phone regex, and date-in-future for events
- [x] Prevent registrations after event date
- [x] Add server-side tests for validation and duplicate-reg prevention

### Phase 10 — Background Tasks & Scheduling

- [x] Add scheduled command to mark past events as `completed` (`php artisan schedule:run`)
- [x] If using emails, queue notification jobs and configure mail driver

### Phase 11 — UI & UX

- [x] Build admin UI with Blade + Tailwind; add clear status badges
- [x] Build responsive public UI; show "Fully Booked" when full
- [x] System shall have consistent styling

### Phase 12 — Tests & CI

- [x] Write feature tests for registration flows, capacity behavior, and waitlist promotion
- [x] Add GitHub Actions workflow to run tests on push/PR

### Phase 13 — Documentation & Submission

- [x] Complete `README.md` with setup, environment variables, admin credentials, and demo instructions
- [x] Provide local run instructions and (optionally) a short demo video
- [x] Summarize challenges and future improvements in docs
- [x] add comments in all core application code files that are clear and concise

### Bonus — Option B (Implemented)

- [x] Add CSV export for registrations
- [x] Add filtering support for export and list views

---

### Manual Submission Checks

- [ ] Push final commits to your GitHub repository
- [ ] Attach/share demo video link if required by reviewer

---

### Additional Requirements (User Requested)

- [x] The user shall be able to view all existing events to be able to attend any events.
- [x] The user shall be able to receive email confirmation for his/her registration.
- [x] The first page shall be a home page in which the user shall enter their information, and only Faris Nabil (email: <Farisnabil075@gmail.com>, password: 1234) is the admin and can directly change event status, add events, and view attendees.
- [x] The admin can also promote other users to become admins and have the same permissions.
- [x] The user information shall be updated and added to the database upon registration.
- [x] The homepage shall redirect users to their desired pages. Login shall redirect to the events page; create-account shall redirect to a new account-creation form that saves to the database and then redirects to events page.
- [x] A logout button shall be added on the events page to allow users to log out from their accounts.
- [x] The database shall be updated when a new attendee registers for an event.
- [x] If the attendee has already registered for the event, a prompt shall inform them that they are already registered.

---

Tips:

- Use database transactions for any multi-step state changes (registration + waitlist promotion).
- Seed a small dataset for reviewers to test flows quickly (1-2 events, some confirmed and waitlisted entries).
- Run `php artisan test` locally and fix failing tests before pushing.
- Keep commits small and descriptive (feature: create events migration; feat: registration promotion logic).

When you'd like, I can scaffold the migrations, models, controllers, or tests for any phase — tell me which phase to start.

#### Requirements Check

### Phase 1 — Database & Models (The Foundation)

- [x] Create the database: use phpMyAdmin to create `eventregistrationsystem`.
- [x] Generate models & migrations:
  php artisan make:model Event -m
  php artisan make:model Attendee -m
  php artisan make:model Registration -m
- [x] Define relationships:
  - In `Event.php`: add `public function registrations() { return $this->hasMany(Registration::class); }`
  - In `Attendee.php`: add `public function registrations() { return $this->hasMany(Registration::class); }`
  - In `Registration.php`: add `belongsTo` for both `Event` and `Attendee`.

### Phase 2 — Admin Features (Event Management)

- [x] Middleware: ensure event creation is wrapped in auth middleware so only admins can access it.
- [x] CRUD controller: create an `EventController` with logic to verify that `event_date` is in the future.
- [x] Status logic: add a helper method to the `Event` model to check whether an event is `completed` based on the current system date.

### Phase 3 — Public Display & Capacity Logic

- [x] Public listing: create a view to display only published events.
- [x] Capacity calculation: add a method in the `Event` model:
  $event->capacity - $event->registrations()->where('status', 'confirmed')->count()
- [x] Conditional UI: use `@if` in Blade to show the **Register** button or a **Fully Booked** badge.

### Phase 4 — Registration System (The "Brain")

- [x] Validation: validate email format and check for duplicates:
  Registration::where('event_id', $eid)->where('attendee_id', $aid)->exists()
- [x] Waitlist logic:
  - If `capacity > 0` → set status to `confirmed`.
  - Else → set status to `waitlisted`.
- [x] Auto-promotion logic: if a confirmed user cancels, promote the first waitlisted attendee for that event to `confirmed`.

### Phase 5 — User Experience & Styling

- [x] Layout setup: place custom CSS in `resources/css/app.css` and use `@yield('content')` in the main layout.
- [x] Personalisation: on the dashboard, use `Auth::user()->name` to welcome the user (e.g. *"Welcome back, Alex!"*).
- [x] Redirects: after registration, execute:

  return redirect()->route('dashboard')->with('success', 'You are booked!');

- [x] System shall allow admin to remove user accounts.
- [x] Styling shall be visible in both light and dark mode.
- [x] All attendees shall be registered users.
- [x] A payment page shall be created.
- [x] System shall have a security question upon sign-up; existing users shall be prompted to set one.
- [x] Security question shall serve as an alternative login method in case of a forgotten password.
