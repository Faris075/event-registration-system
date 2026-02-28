# Obstacles Encountered During Implementation

A record of the real technical issues, blockers, and unexpected challenges faced throughout the development of this Event Registration System.

---

## 1. Missing Built Frontend Assets (Unstyled Pages)

**Problem:** After pulling the project and starting the server, all pages appeared completely unstyled. The layout had no Tailwind CSS applied.

**Root Cause (Part 1):** The `public/build/assets/` directory was empty — the compiled `app-*.css` and `app-*.js` files had never been built and were not present.

**Root Cause (Part 2):** After running `npm run build`, the pages were still unstyled. A stale `public/hot` file remained from a previous `npm run dev` session. The `@vite` Blade directive reads this file and redirects all asset requests to the Vite dev server (`http://127.0.0.1:5173`), which was not running — causing all CSS/JS to silently fail to load.

**Fix:** Ran `npm run build` to generate the assets, then deleted `public/hot` so that `@vite` served assets from the built manifest instead.

---

## 2. PowerShell Execution Policy Blocking npm

**Problem:** Running `npm run build` directly in the VS Code integrated terminal (PowerShell) threw a security error:

**Root Cause:** Windows PowerShell's default execution policy (`Restricted`) blocks `.ps1` scripts, which npm uses as its launcher on Windows.

**Fix:** Invoked npm via `powershell -ExecutionPolicy Bypass -Command "..."` to bypass the restriction without permanently changing system policy.

---

## 3. Route Ordering Conflicts (Static vs. Wildcard Routes)

**Problem:** The `events/create` route was being matched by the `events/{event}` wildcard route, causing Laravel to look for an event with the slug `create` and returning a 404.

**Root Cause:** Laravel matches routes in the order they are registered. Placing the wildcard `{event}` route before named static routes like `events/create` caused the static route to never be reached.

**Fix:** Static admin routes (`events/create`, `events/{event}/edit`) were registered before the wildcard `events/{event}` route in `web.php`.

---

## 4. Premature Database Writes During Registration + Payment Flow

**Problem:** When a user submitted the registration form and then abandoned the payment page, a dangling registration record was left in the database with neither a confirmed nor waitlisted status.

**Root Cause:** The initial implementation wrote the `Registration` record to the database at the form submission step, before payment was confirmed.

**Fix:** Registration details (attendee info, event ID) were stored in the session between the registration form and the payment step. The actual database write only happens after the payment confirmation POST is submitted.

---

## 5. Waitlist Promotion Race Condition

**Problem:** In theory, if two cancellations happened simultaneously, the first waitlisted attendee could be promoted twice or skipped.

**Root Cause:** The promotion query (`WHERE status = 'waitlisted' ORDER BY waitlist_position LIMIT 1`) was not atomic.

**Fix:** The entire cancellation + promotion logic was wrapped in a database transaction (`DB::transaction()`), ensuring that the row lock prevents concurrent promotions.

---

## 6. Duplicate Registration Prevention Across Login Sessions

**Problem:** An attendee could register for the same event multiple times, either by creating a second account with a different email or by using the form multiple times.

**Root Cause:** No uniqueness check existed on the `(event_id, attendee_id)` pair at the application or database level.

**Fix:** Added a unique composite index on `registrations(event_id, attendee_id)` in the migration, and a pre-insert check in the controller that returns a clear error message if the combination already exists.

---

## 7. Security Question for Existing Users

**Problem:** The security question feature was added after the initial user seeder had already run. Existing users (including the seeded admin) had no security question set, breaking the password-recovery flow.

**Root Cause:** The `security_question` and `security_answer` columns were added in a separate migration after initial seeding.

**Fix:** A middleware check was added that redirects authenticated users without a security question set to the `/security-question` setup page before they can access any other authenticated route.

---

## 8. Admin Middleware Not Covering All Protected Routes

**Problem:** Some admin-only actions (event deletion, registration status changes) were accessible to regular authenticated users because the `admin` middleware was not applied uniformly.

**Root Cause:** Route groups were added incrementally across phases without a single authoritative review of which routes required admin access.

**Fix:** All admin routes were consolidated into explicit `Route::middleware('admin')->group()` blocks in `web.php`, with the `AdminMiddleware` checking the `is_admin` boolean on the authenticated user.

---

## 9. MySQL Connection Issues During Initial Setup

**Problem:** `php artisan migrate` failed immediately with a connection refused error.

**Root Cause:** MySQL was not running at the time of the first migration attempt. Additionally, the default `.env` had `DB_PASSWORD=` (empty), which required the local MySQL root account to also have no password set.

**Fix:** Started the MySQL service (via XAMPP/MySQL Workbench) and confirmed the root account had no password. For environments requiring a password, the `.env` `DB_PASSWORD` field must be updated accordingly.

---

## 10. Tailwind Classes Not Applying After CSS Changes

**Problem:** After editing `resources/css/app.css` or Blade templates, new Tailwind utility classes would not appear in the browser.

**Root Cause:** Tailwind's JIT compiler only includes classes it can find during the build scan. If the build cache was stale or classes were added dynamically (e.g., via string interpolation), they were purged from the output.

**Fix:** Re-ran `npm run build` after any template or CSS change. Dynamic class names were replaced with full static class strings to ensure they were included by the JIT scanner.

---

## 11. CSV Export Producing Malformed Output

**Problem:** The CSV export file downloaded fine but opened incorrectly in Excel, with some columns merged or split unexpectedly.

**Root Cause:** Attendee names and event titles containing commas were written into the CSV without being wrapped in quotes.

**Fix:** Used Laravel's `fputcsv()` wrapper which automatically escapes and quotes fields containing delimiters, newlines, or quote characters.
