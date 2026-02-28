# Features

A comprehensive list of all features implemented in the Event Registration System.

---

## Authentication & User Management

- **User Registration** — New users can create an account with name, email, and password. Account is saved to the database and the user is immediately redirected to the events listing.
- **User Login / Logout** — Standard email + password authentication via Laravel Breeze. A logout button is accessible from the events page.
- **Remember Me** — Optional persistent login session via a remember-me checkbox on the login form.
- **Password Reset via Email** — Users can request a password reset link sent to their registered email address.
- **Security Question Setup** — Upon registration (or first login for existing users), users are prompted to set a personal security question and answer.
- **Password Recovery via Security Question** — Users who forget their password can recover access by answering their security question instead of using email, providing an offline fallback recovery path.

---

## Public-Facing Features

- **Home Page** — Entry landing page that routes visitors to login or sign-up.
- **Public Events Listing** — All published events are displayed in a paginated list accessible without authentication. Each entry shows the event name, date, location, and remaining capacity.
- **Event Detail Page** — Full event information including description, date, time, venue, capacity, and a live count of remaining spots.
- **"Fully Booked" Indicator** — When an event reaches capacity, the Register button is replaced with a "Fully Booked" badge.
- **"Completed" Indicator** — Events whose date has passed are automatically marked as completed and visually distinguished in the listing.

---

## Registration Flow

- **Registration Form** — Authenticated users can fill in their name, email, and phone number to register for an event.
- **Duplicate Prevention** — If an attendee has already registered for a given event, a clear message is shown and no duplicate record is created.
- **Payment Page** — After submitting the registration form, users are taken to a payment confirmation page before the registration is committed to the database.
- **Registration Confirmation Page** — After successful payment, users see a dedicated confirmation page with their registration details and status (confirmed or waitlisted).
- **Email Confirmation** — A confirmation email is dispatched to the attendee immediately after a successful registration.
- **Dashboard — Registration History** — Authenticated users can view a list of all their past and current registrations, including event names, dates, and statuses.

---

## Capacity & Waitlist Logic

- **Confirmed Registration** — If the event's confirmed registration count is below capacity, the registration is created with status `confirmed`.
- **Automatic Waitlisting** — If the event is full, the registration is created with status `waitlisted` and assigned a sequential waitlist position.
- **Waitlist Promotion** — When a confirmed registration is cancelled, the earliest waitlisted attendee (by `waitlist_position`) is automatically promoted to `confirmed` within a database transaction.
- **Waitlist Position Tracking** — Each waitlisted registration stores an integer position so attendees know their place in the queue.

---

## Admin — Event Management

- **Create Event** — Admins can create new events with name, description, venue, date, capacity, price, and status fields. Event date must be in the future.
- **Edit Event** — Admins can update any field of an existing event.
- **Delete Event** — Admins can permanently remove an event and its associated registration records.
- **Event Status Control** — Admins can manually set an event's status (`draft`, `published`, `completed`, `cancelled`).
- **Scheduled Auto-Completion** — A scheduled Artisan command runs daily to automatically transition any published event whose date has passed to `completed` status.

---

## Admin — Registration Management

- **View Registrations per Event** — Admins can view a full list of registrations for any event, including attendee details, status, and waitlist position.
- **Change Registration Status** — Admins can manually change a registration's status (e.g., confirm a waitlisted attendee or cancel a registration).
- **Admin Force-Add** — Admins can add a registration for any attendee to any event, bypassing capacity restrictions.
- **CSV Export** — Admins can export all registrations for an event to a CSV file. The export respects active filters (status, date range).
- **Filtered Registration List** — The registrations view supports filtering by status and other criteria for quick lookup.

---

## Admin — User Management

- **User Listing** — Admins can view all registered user accounts.
- **Promote to Admin** — Admins can grant admin privileges to any regular user, giving them full access to event and registration management.
- **Delete User** — Admins can permanently remove a user account from the system.

---

## Profile Management

- **Update Profile** — Authenticated users can update their display name and email address.
- **Change Password** — Users can change their password from the profile page.
- **Delete Account** — Users can permanently delete their own account after confirming their password.

---

## UI & Styling

- **Tailwind CSS Design System** — Consistent visual language using a set of CSS custom properties (design tokens) for colours, spacing, shadows, and border radii.
- **Dark Mode Support** — The UI adapts automatically to the user's system dark mode preference, with full styling for all components in both light and dark themes.
- **Responsive Layout** — All pages are designed to work on desktop and mobile screen sizes.
- **Status Badges** — Colour-coded badges (green for confirmed, amber for waitlisted, red for cancelled, grey for completed) appear consistently across admin and public views.
- **Flash Messages** — Success, error, and informational alerts are shown as transient flash messages after form submissions.

---

## Background Jobs & Scheduling

- **Queued email dispatch** — Registration confirmation, waitlist promotion, and event reminder emails are dispatched as queued jobs to avoid blocking the HTTP request cycle.
- **Scheduled command** — `php artisan schedule:run` is used to trigger the `MarkCompletedEvents` command on a daily schedule.

---

## Testing & CI

- **Feature Tests** — PHPUnit/Pest feature tests covering registration flows, capacity enforcement, waitlist promotion, and duplicate prevention.
- **GitHub Actions Workflow** — Automated CI pipeline runs the full test suite on every push and pull request.
