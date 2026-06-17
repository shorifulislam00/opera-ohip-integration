# Opera (OHIP) — PMS Integration

## Architecture Overview

```
┌──────────────┐      OHIP REST API      ┌──────────────────────┐
│   Opera Cloud │ ◄──────────────────────► │   PMS (CI3)          │
│  (Oracle/HMS) │                          │                      │
└──────────────┘                          │  OhipClient           │
                                           │  OhipReservation      │
                                           │  OhipPosting          │
                                           │  OhipFolioSync        │
                                           │  OhipReservationSync  │
                                           │  Ohip_sync_model      │
                                           └──────────────────────┘
```

Integration direction:

| Direction | Data | PMS → Opera | Opera → PMS |
|-----------|------|:-----------:|:-----------:|
| Reservation list + registration sync | Reservations + registrations (all statuses) | | ✓ |
| Restaurant charge posting | Restaurant bills | ✓ | |
| Folio service sync | Folio charges (mini-bar, laundry, etc.) | | ✓ |

---

## 1. Database Setup

Run the migrations in order on an empty database. Each migration creates one table from scratch with all columns, indexes, and constraints.

| # | File | Table | Key Opera Columns |
|---|------|-------|-------------------|
| 1 | `2026061700001` | `fo_reservations` | `opera_id` (UNIQUE) |
| 2 | `2026061700002` | `fo_reservations_other_info` | — |
| 3 | `2026061700003` | `fo_reservations_room_details` | — |
| 4 | `2026061700004` | `fo_reservation_rate_plan` | — |
| 5 | `2026061700005` | `fo_registrations` | `opera_id` (UNIQUE) |
| 6 | `2026061700006` | `fo_registrations_other_info` | — |
| 7 | `2026061700007` | `fo_registration_rate_plan` | — |
| 8 | `2026061700008` | `fo_registration_room_details` | — |
| 9 | `2026061700009` | `fo_guest_registration_room_no_mapping` | — |
| 10 | `2026061700010` | `tbl_tablefloor` | `transaction_code` |
| 11 | `2026061700011` | `fo_services` | `transaction_code` |
| 12 | `2026061700012` | `fo_service_bills` | `opera_posting_no` |
| 13 | `2026061700013` | `housekeeping_services` | `transaction_code` |
| 14 | `2026061700014` | `housekeeping_service_bills` | `opera_posting_no` |
| 15 | `2026061700015` | `setting` | — |
| 16 | `2026061700016` | `payment_method` | `opera_code` |
| 17 | `2026061700017` | `multipay_bill` | `opera_sync` |
| 18 | `2026061700018` | `sys_opera_config` | OHIP connection config (new) |
| 19 | `2026061700019` | `ohip_tokens` | OAuth2 token cache (new) |
| 20 | `2026061700020` | `roomdetails` | `opera_code` for room type mapping |
| 21 | `2026061700021` | `fo_room_bills` | Per-night room billing (UNIQUE per date/room/registration) |
| 22 | `2026061700022` | `fo_guests` | Guest profiles |
| 23 | `2026061700023` | `customer_order` | Restaurant orders |
| 24 | `2026061700024` | `currency` | Currency reference |
| 25 | `2026061700025` | `acc_automation` | M-Banking head code mapping |
| 26 | `2026061700026` | `tbl_roomnofloorassign` | Room number to floor/type assignment |
| 27 | `2026061700027` | `user` | System users (referenced by rate plan FKs) |

---

## 2. Authentication (OhipClient)

**File:** `application/libraries/OhipClient.php`

### Flow

1. **Token retrieval** — POST to `/oauth/v1/tokens` with:
   - `Authorization: Basic base64(client_id:client_secret)`
   - `x-app-key: <app_key>`
   - `enterpriseId: <enterprise_id>`
   - Body: `grant_type=client_credentials&scope=urn:opc:hgbu:ws:__myscopes__`
2. **Token caching** — stored in `ohip_tokens` table, reused until near expiration (60s buffer).
3. **API calls** — every request includes:
   - `Authorization: Bearer <token>`
   - `x-app-key: <app_key>`
   - `x-hotelId: <hotel_id>`
   - `Content-Type: application/json`
   - `x-Request-Id: <uuid>`

### Base `request()` method

```php
protected function request($method, $path, $body = null)
```

Returns `['status' => HTTP_CODE, 'body' => decoded_json]`.

---

## 3. Unified Reservation Sync (Opera → PMS `fo_reservations` + `fo_registrations`)

**Purpose:** Single batch process that:
- Creates/updates `fo_reservations` for all statuses (Reserved, InHouse, CheckedOut, Cancelled)
- Creates/updates `fo_registrations` for InHouse and CheckedOut stays
- Marks registrations as checked out for CheckedOut status
- Generates per-night `fo_room_bills` with service charge and VAT

### Files
- `OhipReservationSync` (library) — fetches list via API, orchestrates per-item
- `Ohip_sync_model` — `createReservation()`, `updateReservation()`, `syncRegistrationFromList()`, `syncRoomBills()`, `markRegistrationCheckedOut()`

### Endpoint

**GET** `/rsv/v1/hotels/{hotelId}/reservations?lastModifyStartDate={date}&limit={n}&offset={n}`

Uses `lastModifyStartDate` (not `createdOnStartDate`) to catch all modifications: status changes, guest edits, date/rate changes, cancellations.

### Pagination

Full pagination via `offset`/`hasMore` — fetches up to 500 items per page.

### List Response Structure

```
reservations.reservationInfo[]
  ├── reservationIdList[]
  │     └── { type: "Reservation", id: "12345" }
  ├── reservationStatus          ← "Reserved" | "Cancelled" | "InHouse" | "CheckedOut"
  ├── lastModifyDateTime
  ├── reservationGuest
  │     ├── givenName, surname, nameTitle
  │     ├── phoneNumber, email
  │     └── address.{streetAddress, country.code}
  └── roomStay
        ├── arrivalDate, departureDate
        ├── roomType, roomId
        ├── adultCount, childCount
        └── rateAmount.amount
```

### Sync Algorithm (`syncReservations($startDate)`)

```
for each item in paginated list:
    1. Extract operaId from reservationIdList[type=Reservation].id
    2. Reservation sync (all statuses):
       - If exists in fo_reservations → updateReservation()
       - Else → createReservation()
    3. Registration sync (only InHouse / CheckedOut):
       - Call syncRegistrationFromList() — uses list data, no extra API call
       - Creates/updates fo_registrations, fo_registration_room_details, fo_guests
       - Calls syncRoomBills() to generate fo_room_bills (one row per night)
    4. Checkout mark (only CheckedOut):
       - markRegistrationCheckedOut(regId, departureDate)
```

### Room Bills (`syncRoomBills()`)

Generated per-night from arrival to departure (min 1 night). Formula using settings:

```
service_charge  = rate × (settings.service_charge_for_rooms / 100)
vat_charge      = (rate + service_charge) × (settings.vat_for_rooms / 100)
total           = rate + service_charge + vat_charge
```

Dedup by `UNIQUE(date, room, registration_id)` on `fo_room_bills`.

### Status Mappings

| Opera Status | `fo_reservations.status` | Registration synced? | `fo_registrations.checkout_status` |
|-------------|:------------------------:|:--------------------:|:----------------------------------:|
| Reserved    | 1 (Confirmed)            | No                   | N/A |
| InHouse     | 1 (Confirmed)            | Yes                  | 0 (Active) |
| CheckedOut  | 1 (Confirmed)            | Yes                  | 1 (Checked out) + `checkout_date` = `departureDate` |
| Cancelled   | 3 (Cancelled)            | No                   | N/A |

### CLI

```
php index.php OhipSync reservations
```

Uses `app_date - 7 days` as the `lastModifyStartDate`.

---

## 4. Restaurant Charge Posting (PMS → Opera)

**Purpose:** Post paid restaurant bills from PMS to Opera guest folios, including payment.

### Files
- `OhipPosting` (library) — `postCharge()`, `postChargesAndPayments()`
- `Ohip_sync_model::getPendingMultipayBills()` — query
- `OhipSync::restaurant_charges()` — controller action

### Data Flow

```
multipay_bill ──order_id──► customer_order ──outlet_id──► tbl_tablefloor
                                                               │
                                                      transaction_code
                                                               │
                                                    (Opera transaction code)
```

### Payment Type Mapping

| `payment_type_id` | Type | Opera code | Source |
|:-----------------:|------|:----------:|--------|
| 1 | Cash | CA | `payment_method.opera_code` |
| 2 | Card | VA | `payment_method.opera_code` |
| 3 | Bank | BT | `payment_method.opera_code` |
| 4 | M-Banking | BK/UP/NGB | `acc_automation.m_banking_head_code_name` by `multipay_bill.bank_name` |
| 5 | Company | CL | `payment_method.opera_code` |
| 8 | Room Transfer | — | No payment posted; charge only via `/charges` endpoint |

### Query: `getPendingMultipayBills()`

Selects bills where:
- `multipay_bill.opera_sync = 0` (not yet posted)
- `multipay_bill.payment_status = 1` (paid)

Joins: `multipay_bill → customer_order → fo_registrations → tbl_tablefloor`

### Charge + Payment Endpoint

**POST** `/csh/v1/hotels/{hotelId}/reservations/{reservationId}/chargesAndPayments`

Flat payload (no `criteria` wrapper):

```json
{
  "charges": [{
    "transactionCode": "<tbl_tablefloor.transaction_code>",
    "price": { "amount": 123.45, "currencyCode": "BDT" },
    "postingQuantity": 1,
    "checkNumber": "<order_id>",
    "applyRoutingInstructions": false,
    "usePackageAllowance": false,
    "folioWindowNo": 1
  }],
  "payments": [{
    "paymentMethod": { "paymentMethod": "CA" },
    "postingAmount": { "amount": 123.45, "currencyCode": "BDT" },
    "action": "Payment",
    "folioWindowNo": 1
  }],
  "cashierId": <getCashierId()>
}
```

- `cashierId` must be a number (not string)
- `amount` must be a float
- `folioWindowNo` must be an integer
- HTTP 200 with empty body = success

### Room Transfer Flow

If `payment_type_id = 8` (Room Transfer):
- Post charge only via `/charges` endpoint (no payment)
- Charge goes to guest's `opera_id`
- Payment handled at checkout in Opera

### Walk-in Orders

If `multipay_bill.registration_id IS NULL` (walk-in), falls back to PM reservation set in `sys_opera_config`.

### CLI

```
php index.php OhipSync restaurant_charges
```

---

## 5. Folio Service Sync (Opera → PMS Service Bills)

**Purpose:** Fetch folio postings from Opera (mini-bar, laundry, telephone, extra bed, etc.) and create/update PMS service bills with service charge and VAT.

### Files
- `OhipFolioSync` (library) — fetches folios, extracts postings, orchestrates per-posting
- `Ohip_sync_model` — `createServiceBillFromPosting()`, `findFoServiceByTransactionCode()`, `findHousekeepingServiceByTransactionCode()`

### Step 1: Fetch folio (multi-window)

**GET** `/csh/v1/hotels/{hotelId}/reservations/{reservationId}/folios?limit=300&fetchInstructions=Postings&fetchInstructions=Totalbalance&fetchInstructions=Transactioncodes&fetchInstructions=Windowbalances`

The API returns multiple folio windows in one call, but sometimes a window's folios are not included if capacity is consumed by earlier windows. The `getFolio()` method handles this by:

1. First call: discover all windows (no `folioWindowNo` filter)
2. If a window has `emptyFolio: true` and `emptyWindow: false`, it means folios exist but weren't returned — fetch that window individually with `folioWindowNo=N`
3. Merge all postings from all windows

### Response Structure

```
reservationFolioInformation.folioWindows[]
  └── folios[]
        └── postings[]
              ├── transactionCode      ← maps to PMS service
              ├── transactionNo        ← unique posting ID (dedup)
              ├── postedAmount.amount
              ├── transactionType      ← Revenue / Payment
              ├── creditAmount | debitAmount
              ├── remark
              ├── transactionDate / postingDate
              └── postingQuantity
```

### Sync Algorithm (`syncServices()`)

```
for each posting in merged postings:
    if no transactionCode → error

    fo_service = findFoServiceByTransactionCode(txn_code)
    hk_service = findHousekeepingServiceByTransactionCode(txn_code)

    if neither found → skip (no mapping configured)

    → createServiceBillFromPosting(...)   ← handles both insert and update
```

### Service Bill Upsert (`createServiceBillFromPosting()`)

Checks if a bill with `opera_posting_no` already exists in `fo_service_bills`:
- **Exists** → updates existing row (both `fo_service_bills` and `housekeeping_service_bills` for HK services)
- **New** → inserts with fresh service numbers

Financial calculation using settings:

```
sc_value     = amount × (settings.service_charge_for_rooms / 100)
vat_amount   = (amount + sc_value) × (settings.vat_for_rooms / 100)
grand_total  = amount + sc_value + vat_amount
```

Table fields:

| Field | Value |
|-------|-------|
| `service_charge` | Calculated SC value (sc_value) |
| `vat_amount` | Calculated VAT |
| `grand_total` | amount + sc_value + vat |
| `amount` | Raw Opera posting amount (line total) |
| `service_rate` | amount / qty |
| `service_qty` | postingQuantity |
| `opera_posting_no` | posting.transactionNo (dedup) |

#### HK Service flow
- Insert/update in `housekeeping_service_bills`
- Insert/update in `fo_service_bills` (with `hk_service.category_id`)

#### FO-only Service flow
- Insert/update in `fo_service_bills` (with `fo_service.id`)

### Transaction Code Mapping

Pre-populate these with Opera transaction codes:

| Table | Column | Example |
|-------|--------|---------|
| `fo_services` | `transaction_code` | `"1002"` (Extra Bed), `"LAUNDRY"` |
| `housekeeping_services` | `transaction_code` | `"1002"` (Extra Bed), `"MINIBAR"` |

Opera Room Charge (1000), SC (1018), VAT (1017), and Cash FO (6000) are typically not mapped — they represent rates/taxes that should be filtered out by the registration sync rather than imported as service bills.

### CLI

```
php index.php OhipSync services
```

Fetches all registrations with `opera_id`, then runs `syncServices()` on each.

---

## 6. All CLI Endpoints

| Command | Description |
|---------|-------------|
| `php index.php OhipSync reservations` | Sync all Opera reservations (last 7 days) → `fo_reservations` + `fo_registrations` + `fo_room_bills` |
| `php index.php OhipSync restaurant_charges` | Post unpaid restaurant bills → Opera folio |
| `php index.php OhipSync services` | Sync Opera folio postings → PMS service bills |
| `php index.php OhipSync registration_update CONFIRMATION_NO` | Manual: update one registration from Opera detail API |
| `php index.php OhipSync savePmReservation CONFIRMATION_NO` | Save PM reservation fallback in `sys_opera_config` |

---

## 7. Web UI: Opera Settings

**File:** `application/modules/dashboard/views/settings/opera.php`
**Controller:** `application/modules/dashboard/controllers/Setting.php` (`opera()` / `opera_store()`)

UI accepts a PMS reservation confirmation number. On save, it resolves the Opera reservation ID via `getReservationByConfirmation()` and stores both in `sys_opera_config`. This is used as the fallback target for walk-in restaurant charges (when `multipay_bill.registration_id` is null).

---

## 8. Required Manual Mappings

| Table | Column | Purpose |
|-------|--------|---------|
| `tbl_tablefloor` | `transaction_code` | Opera transaction code for each restaurant outlet |
| `fo_services` | `transaction_code` | Opera transaction code for each FO service item |
| `housekeeping_services` | `transaction_code` | Opera transaction code for each HK service item |
| `roomdetails` | `opera_code` | Opera room type name (e.g. `"KNES"`, `"KNPS"`) for each PMS room type |
| `payment_method` | `opera_code` | Opera payment method code (CA, VA, BT, UP, CL) for each PMS payment type |

---

## 9. Numbering Conventions

| Table | Prefix | Example | Generation |
|-------|--------|---------|------------|
| `fo_reservations` | `GR` | `GR00000007` | Max current + 1 |
| `fo_registrations` | `RR` | `RR00000015` | Max current + 1 |
| `fo_service_bills` | `SB` | `SB00000023` | Max current + 1 |
| `housekeeping_service_bills` | `EB` | `EB00000005` | Max current + 1 |

---

## 10. Error Handling & Idempotency

| Sync | Idempotency Mechanism |
|------|----------------------|
| Reservation | `fo_reservations.opera_id` — updates existing row |
| Registration | `fo_registrations.opera_id` UNIQUE — updates existing row |
| Room bills | `UNIQUE(date, room, registration_id)` on `fo_room_bills` |
| Restaurant charge | `multipay_bill.opera_sync` flag (0=unsent, 1=sent) |
| Folio service | `fo_service_bills.opera_posting_no` — upserts (insert or update) |

---

## 11. Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| `lastModifyStartDate` instead of `createdOnStartDate` | Catches status changes, modifications, cancellations — not just new reservations |
| Registration sync from list data (no extra API call) | ~50% fewer API calls for large syncs; list response has all needed flat fields |
| Unified `syncReservations()` handles all statuses | Single pass for Reserved/InHouse/CheckedOut instead of separate endpoints |
| `chargesAndPayments` flat payload (no `criteria`) | Opera API rejects nested `criteria` wrapper for this endpoint |
| Multi-window folio fetch | Opera can split folios across windows; one API call may not return all |
| Service bills upsert by `opera_posting_no` | Re-running sync updates existing bills instead of skipping or duplicating |
| SC/VAT calculated from settings | Matches the PMS's own room bill calculation formula exactly |

---

## 12. File Index

### Application Code

| File | Role |
|------|------|
| `application/libraries/OhipClient.php` | Base API client: auth, token caching, HTTP requests, `getCashierId()` |
| `application/libraries/OhipReservation.php` | Individual reservation detail + folio API calls (extends OhipClient) |
| `application/libraries/OhipPosting.php` | Charge + payment posting to Opera (extends OhipClient) |
| `application/libraries/OhipFolioSync.php` | Multi-window folio fetch + service sync orchestration (extends OhipClient) |
| `application/libraries/OhipReservationSync.php` | Paginated reservation list + unified sync (extends OhipClient) |
| `application/models/Ohip_sync_model.php` | All DB operations: inserts, updates, queries, mappings, calculations |
| `application/controllers/OhipSync.php` | CLI entry points for all sync operations |
| `application/modules/dashboard/controllers/Setting.php` | Web UI for Opera settings + PM reservation config |
| `application/modules/dashboard/views/settings/opera.php` | Opera settings view with PM reservation input |

### Migrations (`application/migrations/`)

| File | Creates |
|------|---------|
| `2026061700001` through `2026061700017` | Core PMS tables with Opera columns embedded |
| `2026061700018` | `sys_opera_config` — OHIP connection settings |
| `2026061700019` | `ohip_tokens` — OAuth2 token cache |
| `2026061700020` through `2026061700027` | Supporting PMS tables (roomdetails, fo_room_bills, fo_guests, customer_order, currency, acc_automation, tbl_roomnofloorassign, user) |

Run in ascending order on an empty database.
