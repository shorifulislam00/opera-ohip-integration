# Opera OHIP Integration — PMS Sync

Seamless two-way synchronization between Oracle Opera Cloud (OHIP API) and a PHP/CI3 Property Management System (PMS). Syncs reservations, registrations, charges, and folio services in real-time or on-demand via CLI.

## Features

- **Reservation Sync (Opera → PMS)** — All Opera reservations (Reserved, Cancelled, InHouse) mirrored into `fo_reservations` with status mapping, guest records, and room type matching.
- **Registration Sync (Opera → PMS)** — In-house Opera reservations become PMS registrations with room assignment, per-night room bills, guest profiles, and linked reservations.
- **Restaurant Charge Posting (PMS → Opera)** — Paid restaurant bills posted to Opera guest folios via the OHIP charges endpoint, with configurable transaction codes per outlet.
- **Folio Service Sync (Opera → PMS)** — Opera folio postings (mini-bar, laundry, telephone, etc.) imported as PMS service bills with dedup protection.

## Architecture

```
┌──────────────┐      OHIP REST API      ┌──────────────────────────┐
│  Opera Cloud  │ ◄──────────────────────► │      PMS (CI3)            │
│ (Oracle HMS)  │                          │                            │
└──────────────┘                          │  OhipClient (Auth/HTTP)     │
                                           │  OhipReservationSync        │
                                           │  OhipFolioSync              │
                                           │  OhipPosting                │
                                           │  Ohip_sync_model (DB)       │
                                           │  OhipSync (CLI Controller)  │
                                           └────────────────────────────┘
```

### Sync Directions

| Direction | Data | PMS → Opera | Opera → PMS |
|-----------|------|:-----------:|:-----------:|
| Reservation List | All reservations (any status) | | ✓ |
| InHouse Registration | Checked-in guests | | ✓ |
| Restaurant Charges | Paid restaurant bills | ✓ | |
| Folio Services | Mini-bar, laundry, etc. | | ✓ |

## Prerequisites

- PHP 7.4+ with cURL
- MySQL 5.7+ / MariaDB 10.3+
- Oracle Opera Cloud account with OHIP API access
- Opera API credentials: Client ID, Client Secret, App Key, Enterprise ID, Hotel ID, Cashier ID

## Installation

### 1. Database Configuration

Create the `sys_opera_config` table:

```sql
CREATE TABLE `sys_opera_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `hostname` VARCHAR(255) NOT NULL COMMENT 'OHIP base URL',
    `hotel_id` VARCHAR(50) NOT NULL,
    `app_key` VARCHAR(255) NOT NULL,
    `enterprise_id` VARCHAR(100) NOT NULL,
    `client_id` VARCHAR(255) NOT NULL,
    `client_secret` VARCHAR(255) NOT NULL,
    `cashier_id` VARCHAR(50) NOT NULL
);
```

Create the OAuth token cache table:

```sql
CREATE TABLE `ohip_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `access_token` TEXT NOT NULL,
    `expires_at` INT NOT NULL,
    `created_at` DATETIME NOT NULL
);
```

### 2. Run Migrations

Apply these schema changes (order matters):

| # | Migration | Change |
|---|-----------|--------|
| 1 | `fo_registrations` | Add `opera_id` VARCHAR(50) UNIQUE |
| 2 | `tbl_tablefloor` | Add `transaction_code` VARCHAR(50) |
| 3 | `multipay_bill` | Add `opera_sync` TINYINT(1) DEFAULT 0 |
| 4 | `fo_services` | Add `transaction_code` VARCHAR(50) |
| 5 | `housekeeping_services` | Add `transaction_code` VARCHAR(50) |
| 6 | `fo_service_bills` | Add `opera_posting_no` VARCHAR(50) |
| 7 | `housekeeping_service_bills` | Add `opera_posting_no` VARCHAR(50) |
| 8 | `fo_reservations` | Add `opera_id` VARCHAR(50) |
| 9 | `roomdetails` | Add `opera_code` VARCHAR(50) |

See `application/migrations/` for the standalone SQL migration files.

### 3. Deploy Files

Copy these files into your CI3 project:

```
application/
├── controllers/
│   └── OhipSync.php                    # CLI entry points
├── models/
│   └── Ohip_sync_model.php             # All DB operations
└── libraries/
    ├── OhipClient.php                  # Base: auth, token, HTTP
    ├── OhipReservation.php             # Reservation API calls
    ├── OhipPosting.php                 # Charge posting
    ├── OhipFolioSync.php               # Folio fetch + service sync
    └── OhipReservationSync.php         # Reservation list + sync
```

### 4. Populate Mapping Data

These tables require manual data to match Opera codes with PMS records:

| Table | Column | Purpose |
|-------|--------|---------|
| `tbl_tablefloor` | `transaction_code` | Opera transaction code per restaurant outlet |
| `fo_services` | `transaction_code` | Opera transaction code per FO service item |
| `housekeeping_services` | `transaction_code` | Opera transaction code per HK service item |
| `roomdetails` | `opera_code` | Opera room type name (e.g. `"DBL"`, `"SUI"`) |

## Usage

All sync operations are CLI commands:

```bash
# 1. Sync in-house Opera reservations → PMS registrations
php index.php OhipSync registrations

# 2. Sync all Opera reservations → PMS reservations
php index.php OhipSync reservations 2026-01-01

# 3. Post paid restaurant bills → Opera folios
php index.php OhipSync restaurant_charges

# 4. Import Opera folio postings → PMS service bills
php index.php OhipSync services
```

### Scheduling (Cron)

```cron
*/5 * * * * php /path/to/index.php OhipSync registrations
*/5 * * * * php /path/to/index.php OhipSync restaurant_charges
*/5 * * * * php /path/to/index.php OhipSync services
0 */6 * * * php /path/to/index.php OhipSync reservations $(date +\%Y-\%m-\%d)
```

## API Reference

All calls use the Oracle Hospitality Integration Platform (OHIP) REST API.

### Authentication

```
POST /oauth/v1/tokens
Headers: Authorization: Basic <base64(client_id:client_secret)>, x-app-key, enterpriseId
Body: grant_type=client_credentials&scope=urn:opc:hgbu:ws:__myscopes__
```

### Reservation List

```
GET /rsv/v1/hotels/{hotelId}/reservations?createdOnStartDate={date}&limit=200
```

### Reservation Detail

```
GET /rsv/v1/hotels/{hotelId}/reservations/{reservationId}
```

### InHouse List

```
GET /rsv/v1/hotels/{hotelId}/reservations?reservationStatus=InHouse&limit=100
```

### Post Charges

```
POST /csh/v1/hotels/{hotelId}/reservations/{reservationId}/charges
```

### Fetch Folio

```
GET /csh/v1/hotels/{hotelId}/reservations/{reservationId}/folios?folioWindowNo=1&limit=50&fetchInstructions=Postings&fetchInstructions=Totalbalance&fetchInstructions=Transactioncodes&fetchInstructions=Windowbalances
```

## Status Mappings

| Opera Status | PMS `fo_reservations.status` | PMS `fo_registrations.checkout_status` |
|-------------|:---------------------------:|:-------------------------------------:|
| Reserved | 1 (Confirmed) | N/A |
| InHouse | N/A | 0 (Active) |
| CheckedOut | N/A | 1 (Checked out) |
| Cancelled | 3 (Cancelled) | N/A |

## Numbering Conventions

| Entity | Prefix | Example | Pattern |
|--------|--------|---------|---------|
| Reservation | `GR` | `GR00000007` | GR + 8-digit sequential |
| Registration | `RR` | `RR00000015` | RR + 8-digit sequential |
| FO Service Bill | `SB` | `SB00000023` | SB + 8-digit sequential |
| HK Service Bill | `EB` | `EB00000005` | EB + 8-digit sequential |

## Idempotency

| Sync | Mechanism |
|------|-----------|
| Registration | `fo_registrations.opera_id` UNIQUE index |
| Reservation | `fo_reservations.opera_id` — updates existing row |
| Restaurant charge | `multipay_bill.opera_sync` flag (0=unsent, 1=sent) |
| Folio service | `fo_service_bills.opera_posting_no` — skips duplicates |

## File Reference

| File | Responsibility |
|------|---------------|
| `application/libraries/OhipClient.php` | OAuth2 authentication, token caching, cURL HTTP wrapper |
| `application/libraries/OhipReservation.php` | OHIP reservation detail/folio API endpoints |
| `application/libraries/OhipPosting.php` | OHIP charge posting endpoint |
| `application/libraries/OhipFolioSync.php` | Folio fetch, posting extraction, service bill orchestration |
| `application/libraries/OhipReservationSync.php` | Reservation list fetch, create/update orchestration |
| `application/models/Ohip_sync_model.php` | All DB queries, inserts, updates, guest/service/rate extraction |
| `application/controllers/OhipSync.php` | CLI controller dispatching all 4 sync commands |
| `application/migrations/*.php` | Standalone SQL migration files |

## Troubleshooting

**No reservations found** — Verify `sys_opera_config` credentials and that the Oracle account has reservations.

**Charge posting returns error** — Confirm `tbl_tablefloor.transaction_code` is set and matches an Opera transaction code. Check `cashier_id` in config.

**Folio sync skips all postings** — Populate `fo_services.transaction_code` and `housekeeping_services.transaction_code` with the exact Opera transaction codes appearing in folio postings.

**Room type resolves to 0** — Set `roomdetails.opera_code` to match the Opera room type name (e.g. `"DBL"`, `"KIN"`, `"SUI"`).

## License

MIT
