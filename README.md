# Booking Platform

A booking management application built with Laravel and Livewire.

The project demonstrates customer-facing appointment booking alongside an authenticated workspace for managing services, customers, bookings, and business hours.

## Features

- **Authentication**
    - Registration and login
    - Email verification
    - Forgot-password and password-reset flows

- **Service management**
    - Manage the services offered by a business
    - Support for active and inactive services

- **Booking management**
    - Create, update, confirm, cancel, and complete bookings
    - Filter bookings by date and status where supported by the application
    - Reschedule bookings through the public booking-management flow

- **Customer management**
    - Manage customer records associated with bookings

- **Business hours**
    - Configure opening hours by day of the week
    - Mark individual days as closed

- **Public booking flows**
    - Public booking page for a business
    - Signed links for managing or cancelling a booking

- **Reminders**
    - Booking reminder functionality

- **Testing**
    - Automated test suite written with Pest
    - 300+ tests covering application behavior and business rules

## Tech Stack

- PHP 8.4
- Laravel 13
- Livewire 4
- Tailwind CSS 4
- MySQL
- Pest

## Requirements

Before installing the application, make sure your development environment includes:

- PHP 8.4 and the PHP extensions required by Laravel
- Composer
- Node.js and npm
- MySQL
- A local web server or Laravel-compatible development environment

## Installation

### 1. Clone the repository

```bash
git clone <REPOSITORY_URL>
cd booking-platform
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create your environment file

```bash
cp .env.example .env
```

On Windows, you can copy `.env.example` to `.env` manually or use:

```powershell
Copy-Item .env.example .env
```

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Configure the database

Create a MySQL database and configure the following values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_platform
DB_USERNAME=root
DB_PASSWORD=
```

Adjust the database name, username, and password to match your local environment.

### 6. Run migrations and seed demo data

```bash
php artisan migrate --seed
```

The default database seeder runs `DemoDataSeeder`, which creates the demo business, customers, services, business hours, and bookings.

### 7. Install and build frontend assets

```bash
npm install
npm run build
```

### 8. Start the local development server

```bash
php artisan serve
```

For frontend development with hot reload, run:

```bash
npm run dev
```

in a separate terminal.

## Demo Account

The seeded database includes a demo business account.

**Email:**

```text
demo@booking-platform.test
```

**Password:**

```text
password
```

The demo account is created with a verified email address so the application can be explored immediately after seeding.

> **Note:** These credentials are intended only for local development and portfolio demonstration. Do not reuse this password for a real account.

## Demo Data

The demo seeder creates a realistic sample dataset including:

- 1 demo business
- 12 customers
- 5 services
- Active and inactive services
- Business hours for the full week
- Closed Sunday
- Historical completed and cancelled bookings
- Upcoming pending and confirmed bookings
- Booking notes and different service durations

To recreate the complete demo database from scratch:

```bash
php artisan migrate:fresh --seed
```

> **Warning:** `migrate:fresh` drops all tables in the configured database. Use a development database only.

## Running Tests

Run the complete automated test suite with:

```bash
php artisan test
```

The project currently contains **335 tests**, covering authentication, booking rules, customer and service management, business hours, public booking flows, signed URLs, dashboard behavior, tenant isolation, validation, and other application behavior.

## Security Notes

- Keep `.env` out of version control.
- Never commit passwords, API keys, tokens, or other secrets.
- Use HTTPS and review application configuration before deploying publicly.
- Signed booking-management and cancellation links are time-limited; treat them as private links.
- The demo credentials are intended only for local development and demonstration.

## Project Structure

The application follows Laravel's standard project structure with domain logic organized into actions, queries, models, Livewire components, and feature tests.

Key areas include:

```text
app/
├── Actions/
├── Enums/
├── Exceptions/
├── Livewire/
└── Models/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
└── views/

routes/

tests/
├── Feature/
└── Unit/
```

## Project Status

This repository is a demonstration and portfolio project.

The application is covered by an automated test suite and is intended to demonstrate Laravel, Livewire, authentication, booking workflows, multi-tenant data isolation, validation, and business-rule implementation.

Review the code and configuration before using the project in a production environment.

## License

No license has been specified yet.

Add a license file if you intend to permit others to use, modify, or distribute this project.
