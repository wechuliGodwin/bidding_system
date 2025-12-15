# Bidding System - AI Agent Instructions

## Project Overview
Laravel 12 application for managing government asset disposal through a dual-role bidding system. Two user types: **Admins** (manage events/assets) and **Bidders** (register, place bids).

## Architecture

### Domain Model (Core Entities)
- **DisposalEvent**: Auction events with configurable bid types (`highest_wins`/`lowest_wins`), increments, cut-off prices, and lifecycle states (`draft` → `published` → `closed` → `completed`)
- **Asset**: Items within events that receive bids; tracks `current_highest_bid` and `winner_bidder_id`
- **Bidder**: User profile requiring approval (`pending` → `approved`/`rejected`); uses soft deletes
- **Bid**: Individual bid records with validation against event rules
- **User**: Authentication entity with `role` field (`admin`/`bidder`)

### Key Relationships
```
DisposalEvent hasMany Assets
Asset belongsTo DisposalEvent, hasMany Bids
Bidder (hasOne User) hasMany Bids, hasMany Documents
User hasOne Bidder
```

## Development Workflow

### Initial Setup
```bash
composer run setup     # Full setup: dependencies, .env, migrations, frontend build
composer run dev       # Concurrent: server + queue + logs + vite (auto-restarts)
composer run test      # Run PHPUnit tests
```

### Common Commands
- **Migrations**: `php artisan migrate` (default: SQLite at `database/database.sqlite`)
- **Seed admin**: `php artisan db:seed --class=AdminSeeder` (admin@bidding.com / password)
- **Code style**: `./vendor/bin/pint` (Laravel Pint for PSR-12)
- **Assets**: `npm run build` (production) or `npm run dev` (watch mode)

## Conventions & Patterns

### Route Organization
- Admin routes: `prefix('admin')` + resource controllers in `app/Http/Controllers/Admin/`
- Bidder routes: `prefix('bidder')` + shared controllers (e.g., `BiddingController`)
- Post-login redirect via `/home` route checks `User->isAdmin()` for role-based routing

### Status-Driven Validation
Controllers enforce business rules via model status checks:
- `DisposalEvent->isActive()`: Validates `status === 'published'` AND within date range
- `Bidder->isApproved()`: Gates bidding actions
- `Asset->getHighestBid()`: Queries only `status === 'valid'` bids

### Transaction Patterns
Use DB transactions for multi-step operations (see [DisposalEventController](app/Http/Controllers/Admin/DisposalEventController.php#L50-L70)):
```php
DB::beginTransaction();
try {
    // Multiple operations
    DB::commit();
    Log::info(...);
} catch (\Exception $e) {
    DB::rollBack();
    Log::error(...);
}
```

### File Uploads
Document handling in `BidderRegistrationController`: validates `pdf|jpg|jpeg|png`, max 5MB, stores in `storage/app/public` via `Storage::put('documents/', $file)`.

## Frontend Stack
- **Vite + Laravel Plugin**: Hot reload on `npm run dev`
- **Hybrid CSS**: Tailwind 4.0 + Bootstrap 5.2 + custom Sass (see [resources/sass/](resources/sass/))
- **Views**: Blade templates in `resources/views/` with `admin/` and `auth/` subdirectories

## Database Notes
- Default connection: SQLite (`config/database.php`)
- Migration timestamps follow pattern: `YYYY_MM_DD_HHMMSS_description.php`
- Decimal precision: `12,2` for monetary fields

## Testing Approach
PHPUnit configured ([phpunit.xml](phpunit.xml)). Prefer feature tests for controller workflows over unit tests.

## When Adding Features
1. **New event statuses**: Update enum in [migration](database/migrations/2025_12_15_075835_create_disposal_events_table.php#L19) and model cast
2. **Bid validation**: Extend logic in [BiddingController->placeBid()](app/Http/Controllers/BiddingController.php#L30-L70)
3. **Admin actions**: Add routes to `admin.*` group with resource or custom actions (e.g., `events.publish`)
