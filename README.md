# Event Registration System

A Laravel 12 application for event discovery, registration, waitlist handling, and admin management.

## Implemented Scope

- Public events listing and event details
- Dedicated registration page and separate registration confirmation page
- Attendee data persistence (create/update by email)
- Duplicate registration prevention per attendee per event
- Capacity + waitlist handling with deterministic promotion on cancellation
- Email confirmation dispatch on registration
- Admin CRUD for events and registration status management
- Admin user promotion (grant admin role to other users)
- CSV export of registrations with filters
- Scheduled command to mark past published events as completed
- Feature tests + GitHub Actions CI

## Tech Stack

- PHP 8.2+
- Laravel 12
- Blade + Tailwind + Vite
- MySQL or SQLite

## Local Setup

1. Install dependencies:

```bash
composer install
npm install
```

1. Configure app:

```bash
cp .env.example .env
php artisan key:generate
```

1. Configure database in `.env`.

1. Migrate and seed:

```bash
php artisan migrate --seed
```

1. Run app:

```bash
php artisan serve
npm run dev
```

## Environment Variables (Key)

- `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`
- `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`

## Admin Credentials

Seeded by `CreateAdminUserSeeder`:

- Name: `Faris Nabil`
- Email: `Farisnabil075@gmail.com`
- Password: `Admin@1234`

## App Flow (Current)

1. Open `/` (home/login entry page).
1. Login redirects to `/events`.
1. Account creation (`/register`) saves user to DB and redirects to `/events`.
1. From event details, open registration page (`/events/{event}/register`).
1. Submit registration and land on confirmation page (`/events/{event}/registration-confirmation`).
1. Admin can manage event registrations and promote users from `/admin/users`.

## Bonus Option B

- CSV export from admin registrations page
- Export respects status/payment filters

## Scheduler

Run once manually:

```bash
php artisan events:mark-completed
```

Run scheduler tick manually:

```bash
php artisan schedule:run
```

## Tests

```bash
php artisan test
```

## CI

Workflow file: `.github/workflows/tests.yml` (runs tests on push/PR).

## Challenges

- Preserving attendee updates while preventing duplicate registrations
- Keeping transactional waitlist promotion consistent
- Maintaining clear admin/public boundaries

## Future Improvements

- Queue email confirmation and waitlist promotion notifications
- Expand role/permission controls with policies
- Add broader end-to-end coverage for auth + registration flows
