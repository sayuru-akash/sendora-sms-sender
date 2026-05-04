<p align="center">
  <h1 align="center">📨 Sendora</h1>
  <p align="center">Simple contact lists. Smarter SMS campaigns.</p>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Vue-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white" alt="Vue 3">
  <img src="https://img.shields.io/badge/TypeScript-5-3178C6?style=flat-square&logo=typescript&logoColor=white" alt="TypeScript">
  <img src="https://img.shields.io/badge/Inertia.js-2-0D47A1?style=flat-square&logo=inertia&logoColor=white" alt="Inertia.js">
  <img src="https://img.shields.io/badge/Tailwind-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/PostgreSQL-18-4169E1?style=flat-square&logo=postgresql&logoColor=white" alt="PostgreSQL">
</p>

---

Sendora is a production-grade SMS CRM and campaign management platform for managing contacts, segmenting audiences, sending SMS campaigns, and tracking delivery performance from one organised dashboard.

## Features

### Core Modules

- **Contact Management** — Full CRUD, soft deletes, bulk actions, import/export
- **Contact Lists** — Organise contacts into audience groups
- **Tags** — Flexible labels for contact segmentation
- **Bulk Imports** — CSV/XLSX upload with column mapping, duplicate handling, and queue-based processing
- **SMS Templates** — Reusable message templates with variable insertion
- **SMS Campaigns** — Multi-step campaign builder with audience filtering, scheduling, and real-time progress tracking
- **Campaign Reports** — Delivery stats, success/failure rates, recipient tables
- **SMS Logs** — Full delivery log with provider responses
- **Saved Segments** — Reusable audience filters for campaigns
- **Dashboard** — Real-time analytics, charts, recent activity
- **User Management** — Role-based access control (Owner, Admin, Manager, Staff, Viewer)
- **Settings** — System configuration, SMS settings, test SMS
- **Activity Logs** — Track all important user and system actions

### SMS Provider

- Pluggable SMS provider adapter architecture
- Text-Ware as the first implementation
- Configurable via environment variables
- Rate limiting and retry logic
- Provider response logging

### Technical Highlights

- **Queue-based SMS sending** — All bulk operations processed through Laravel database queues
- **Phone normalisation** — Sri Lankan numbers normalised to `94XXXXXXXXX` format
- **Duplicate prevention** — No duplicate sends, deduplication by normalised phone
- **Exclusion rules** — Automatically excludes unsubscribed, blocked, and invalid contacts
- **Campaign pause/resume/cancel** — Full campaign lifecycle management
- **Rate limiting** — Configurable SMS rate limits
- **Server-side filtering** — All filtering and pagination handled on the backend
- **Activity logging** — Comprehensive audit trail via Spatie Activitylog

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Vue 3, TypeScript, Inertia.js |
| Database | PostgreSQL |
| CSS | Tailwind CSS v4 |
| UI Components | Custom shadcn-vue style components with Reka UI |
| Tables | TanStack Table for Vue |
| Charts | ECharts (vue-echarts) |
| Validation | VeeValidate + Zod |
| Icons | Lucide Vue |
| Animations | Motion for Vue |
| Queues | Laravel Database Queues |
| Activity Log | Spatie Laravel Activitylog |

## Requirements

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- npm 10+
- PostgreSQL 14+

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-org/sendora-sms-sender.git
cd sendora-sms-sender
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Environment configuration

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sendora
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Configure SMS (Text-Ware):

```env
SMS_PROVIDER=textware
SMS_USERNAME=your_textware_username
SMS_PASSWORD=your_textware_password
SMS_SOURCE=YOUR_SENDER_ID
SMS_API_URL=https://msg.text-ware.com/send_sms.php
SMS_RATE_LIMIT_PER_MINUTE=300
SMS_DEFAULT_COUNTRY_CODE=94
SMS_TIMEOUT_SECONDS=30
```

### 4. Create the database

```bash
createdb sendora
```

### 5. Run migrations and seed

```bash
php artisan migrate --seed
```

### 6. Build frontend

```bash
npm run build
```

### 7. Start the application

```bash
# Development (runs server, queue, logs, and vite concurrently)
composer dev

# Or individually:
php artisan serve
php artisan queue:work
npm run dev
```

## Default Users

| Email | Password | Role |
|-------|----------|------|
| owner@sendora.com | password | Owner |
| admin@sendora.com | password | Admin |
| manager@sendora.com | password | Manager |
| staff@sendora.com | password | Staff |
| viewer@sendora.com | password | Viewer |

## Scheduled Tasks

Add to your crontab for scheduled campaigns:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler checks for due scheduled campaigns every minute and dispatches them to the queue.

## Queue Workers

For production, run the queue worker:

```bash
php artisan queue:work --tries=3 --timeout=300
```

For future Redis/Horizon upgrade, simply change `QUEUE_CONNECTION=redis` in `.env` and install Laravel Horizon. No code changes required.

## Project Structure

```
sendora/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Thin controllers
│   │   ├── Middleware/        # Active user check, role-based access
│   │   └── Requests/         # Form request validation
│   ├── Jobs/                 # Queued jobs (imports, campaigns, SMS)
│   ├── Models/               # Eloquent models
│   ├── Policies/             # Authorization policies
│   ├── Providers/            # Service providers
│   └── Services/             # Business logic services
│       ├── Sms/              # SMS provider adapter system
│       ├── PhoneNormalizer.php
│       └── ActivityLogger.php
├── database/
│   ├── factories/            # Model factories
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── resources/
│   ├── css/                  # Tailwind CSS
│   ├── js/
│   │   ├── Components/       # Vue components (UI, layout, common)
│   │   ├── Pages/            # Inertia page components
│   │   ├── composables/      # Vue composables
│   │   ├── lib/              # Utility functions
│   │   └── types/            # TypeScript type definitions
│   └── views/                # Blade templates (app shell)
├── routes/
│   ├── web.php               # Web routes
│   ├── auth.php              # Auth routes
│   └── console.php           # Scheduled commands
└── tests/
    ├── Feature/              # Feature tests
    └── Unit/                 # Unit tests
```

## SMS Provider Architecture

Sendora uses a pluggable SMS provider adapter pattern:

```
SmsProviderInterface        # Contract for all providers
├── TextWareProvider         # Text-Ware implementation
└── [Future providers]       # Add new providers without changing campaign logic

SmsService                  # Orchestrator service
├── Uses PhoneNormalizer
├── Uses SmsProviderInterface
└── Handles logging and error management
```

To add a new SMS provider:

1. Create a class implementing `SmsProviderInterface`
2. Add the provider configuration to `config/sms.php`
3. Register the binding in `SmsServiceProvider`

## Testing

```bash
php artisan test
```

Tests cover:
- Phone normalisation and validation
- Contact CRUD and filtering
- Campaign creation, sending, and status transitions
- Import processing and duplicate handling
- SMS provider adapter responses
- Permission restrictions

## Design Principles

- **Premium, minimal UI** — Clean white/grey base, indigo accent, calm spacing
- **Thin controllers** — Business logic lives in services and jobs
- **Queue-first** — All bulk operations processed through database queues
- **Security-first** — Policies, request validation, credential protection
- **Provider-agnostic** — SMS sending through adapter pattern
- **Production-ready** — Soft deletes, activity logs, rate limiting, error handling

## License

This project is licensed under the [MIT License](LICENSE).

Copyright (c) 2026 Sayuru Akash Amarasinghe.
