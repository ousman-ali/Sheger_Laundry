# Shebar Laundry Management System

> A modern, full-featured laundry management system built with Laravel, MySQL, Tailwind CSS, and Docker. This guide will help you set up, run, and understand the project as a junior developer.

---

## Table of Contents

-   [Shebar Laundry Management System](#shebar-laundry-management-system)
    -   [Table of Contents](#table-of-contents)
    -   [Project Overview](#project-overview)
    -   [Features](#features)
    -   [Tech Stack](#tech-stack)
    -   [Getting Started](#getting-started)
        -   [Prerequisites](#prerequisites)
        -   [Local Setup (with Docker)](#local-setup-with-docker)
        -   [Manual Setup (without Docker)](#manual-setup-without-docker)
    -   [Running Tests](#running-tests)
    -   [Project Structure](#project-structure)
    -   [Common Tasks](#common-tasks)
    -   [Exporting Data](#exporting-data)
    -   [Troubleshooting](#troubleshooting)
    -   [Resources](#resources)
    -   [Environment](#environment)
    -   [Queues \& Scheduler](#queues--scheduler)
    -   [Mail](#mail)
    -   [Broadcasting (Realtime)](#broadcasting-realtime)
    -   [Seeding \& Default Access](#seeding--default-access)
    -   [Health Check](#health-check)

---

## Project Overview

Shebar Laundry Management System is designed to streamline laundry operations, including order management, inventory, user roles, and reporting. It supports multiple user roles (Admin, Receptionist, Manager, Operator) and provides robust filtering, exporting, and notification features.

## Features

-   Role-based dashboards and navigation
-   Order management with advanced filters (status, date, customer, operator)
-   Inventory and stock tracking
-   CSV/Excel and PDF export (honors filters)
-   Activity logs and notifications
-   Responsive UI with Tailwind CSS

## Tech Stack

-   **Backend:** Laravel (PHP)
-   **Frontend:** Blade, Tailwind CSS, Vite
-   **Database:** MySQL (default), SQLite (for testing)
-   **Containerization:** Docker, Docker Compose
-   **Other:** Spatie Laravel Permission, Pest (testing)

---

## Getting Started

### Prerequisites

-   [Docker & Docker Compose](https://docs.docker.com/get-docker/) (recommended)
-   PHP >= 8.2 (if running without Docker)
-   Composer
-   Node.js & npm

### Local Setup (with Docker)

1. **Clone the repository:**
    ```bash
    git clone <repo-url>
    cd sheger_automatic_laundry
    ```
2. **Copy environment file:**
    ```bash
    cp .env.example.production .env
    ```
3. **Start containers:**
    ```bash
    docker compose up -d --build
    ```
4. **Install dependencies & generate key:**
    ```bash
    docker compose exec app composer install
    docker compose exec app php artisan key:generate
    docker compose exec app npm install
    docker compose exec app npm run build
    ```
5. **Run migrations & seeders:**
    ```bash
    docker compose exec app php artisan migrate --force
    docker compose exec app php artisan db:seed
    docker compose exec app php artisan storage:link
    ```
6. **Access the app:**
    - Visit [http://localhost](http://localhost)

### Manual Setup (without Docker)

1. **Install PHP, Composer, Node.js, MySQL**
2. **Clone the repository & copy `.env`:**
    ```bash
    git clone <repo-url>
    cd sheger_automatic_laundry
    cp .env.example.production .env
    ```
3. **Install dependencies:**
    ```bash
    composer install
    npm install
    npm run build
    ```
4. **Configure your `.env` database settings**
5. **Generate app key & run migrations:**
    ```bash
    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed
    php artisan storage:link
    ```
6. **Start the server:**
    ```bash
    php artisan serve
    ```

---

## Running Tests

Run all tests:

```bash
docker compose exec app php artisan test
# or, for Pest
docker compose exec app ./vendor/bin/pest
```

## Project Structure

-   `app/` - Main application code (Models, Controllers, Services, etc.)
-   `routes/` - Route definitions (`web.php`, `api.php`)
-   `resources/views/` - Blade templates
-   `public/` - Public assets and entry point
-   `database/` - Migrations, seeders, factories
-   `config/` - Configuration files
-   `tests/` - Feature and unit tests

## Common Tasks

-   **Add a migration:**
    ```bash
    docker compose exec app php artisan make:migration create_example_table
    ```
-   **Run a seeder:**
    ```bash
    docker compose exec app php artisan db:seed --class=YourSeeder
    ```
-   **Queue worker (background jobs):**
    ```bash
    docker compose exec worker php artisan queue:work
    ```

## Exporting Data

On any list page (Orders, Users, Customers, etc.):

-   Set your filters/search as needed
-   Click **Export CSV**, **Export Excel**, or **Export PDF**
-   Exports include all rows matching current filters (not just the current page)

## Troubleshooting

-   **Permission issues:** Ensure `storage/` and `bootstrap/cache/` are writable
-   **Database errors:** Check your `.env` DB settings and that the DB container is running
-   **Assets not updating:** Run `npm run build` or restart Docker containers

## Resources

-   [Laravel Documentation](https://laravel.com/docs)
-   [Tailwind CSS Docs](https://tailwindcss.com/docs)
-   [Pest PHP Testing](https://pestphp.com/docs/introduction)
-   [Docker Docs](https://docs.docker.com/)

---

**License:** MIT

---

## Environment

-   Use `.env.example.production` as the reference for all required variables. It contains examples for production (originally Heroku). Copy it to `.env` and adjust values for local/Docker.
-   For Docker local development, typical DB values:
    -   `DB_CONNECTION=mysql`
    -   `DB_HOST=db` (service name from `docker-compose.yml`)
    -   `DB_PORT=3306`
    -   `DB_DATABASE=shebar_laundry`
    -   `DB_USERNAME` / `DB_PASSWORD` per your `.env` or compose defaults
-   Queues default to the `database` driver. Migrations include `jobs`, `failed_jobs`, and `job_batches` tables.
-   Broadcasting defaults to `log`. Set to `pusher` when enabling real-time notifications.
-   Mail defaults to `log`. Configure SMTP to send real emails.

## Queues & Scheduler

-   Docker services:
    -   `worker`: runs `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
    -   `scheduler`: runs `php artisan schedule:work`
-   You can run these manually if needed:

```bash
docker compose exec app php artisan queue:work
docker compose exec app php artisan schedule:work
```

## Mail

Configure SMTP in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@email
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your@email
MAIL_FROM_NAME="Shebar Laundry"
```

For development, use `MAIL_MAILER=log` to write emails to storage logs instead of sending.

## Broadcasting (Realtime)

Enable Pusher in `.env`:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=ap2
PUSHER_SCHEME=https
PUSHER_HOST=
PUSHER_PORT=

VITE_APP_NAME="Shebar Laundry"
VITE_PUSHER_APP_KEY=your-app-key
VITE_PUSHER_APP_CLUSTER=ap2
VITE_PUSHER_SCHEME=https
VITE_PUSHER_HOST=
VITE_PUSHER_PORT=
```

## Seeding & Default Access

`php artisan db:seed` runs:

-   `RolePermissionSeeder` — roles and granular permissions
-   `AddServiceWorkflowPermissionsSeeder` — workflow-related permissions
-   `InitialDataSeeder` — units, services, cloth items, urgency tiers, pricing, stores, inventory, system settings, and an Admin user

Default Admin user:

-   Email: `admin@shebarlaundry.com`
-   Password: `password`

## Health Check

-   Health endpoint: `GET /healthz` returns `{ status: "ok" }` for uptime checks.
