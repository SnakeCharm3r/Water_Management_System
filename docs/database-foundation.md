# DAWASA Database Foundation

## Authority boundary

Laravel and MySQL are the institutional system of record for identities, accounts, meter history, tariffs, billing, receivables, payments, approvals, audit history, and synchronization state. Supabase is a customer-facing projection and authentication integration only. Financial calculations and permission decisions do not originate from Supabase.

## Discovery report

### Laravel application

The Laravel project initially contained only the framework tables, the default `users` model, Filament setup, and Spatie Permission tables. It has no existing customer, account, meter, tariff, billing, payment, or synchronization models, migrations, forms, or records to transform.

The configured MySQL database is `Dawasa` on `127.0.0.1:3306`. It was empty when this foundation began.

### Existing mobile and Supabase structures

The separate DAWASA mobile project stores customer-facing data in Supabase Auth and `kv_store_2abd97f5`, with planned `public.customers`, `public.meter_readings`, `public.bills`, `public.payments`, and `public.complaints` tables. Its existing customer profile API exposes `firstName`, `middleName`, `lastName`, `location`, `ipNumber`, and a Supabase Auth UUID.

The mobile application currently treats `ipNumber` and `meterNumber` as customer profile values. The institutional schema corrects this by storing the IP number only on `water_accounts`, the physical meter number only on `meters`, and historical placement only on `meter_installations`.

## Existing-field mapping

| Existing input or projection | Normalized institutional destination | Import handling |
| --- | --- | --- |
| Customer name | `customers.first_name`, `middle_name`, `last_name`, or `business_name` | Split individual names; retain organization names in `business_name` |
| Customer type | `customers.customer_type` | Defaults must be reviewed during import when omitted |
| Phone | `customers.phone` | Preserved |
| Alternative phone | `customers.alternative_phone` | Preserved when available |
| Email | `customers.email` | Preserved; not globally unique because shared organizational email is valid |
| National ID | `customers.national_id` | Indexed; used only as a controlled import match |
| Registration number | `customers.registration_number` | Indexed; used only as a controlled import match |
| IP/account number | `water_accounts.ip_number` | Canonical account-level identifier; unique |
| Account name | `water_accounts.account_name` | Uses customer display name when source omits it |
| Service/property address | `water_accounts.service_address` | Existing mobile `location` maps here |
| Region, district, ward, street, plot | `water_accounts` locality fields and `zone_id` | Parse only when source values are reliable; otherwise retain the source address |
| GPS coordinates | `water_accounts.latitude`, `longitude` | Decimal coordinates |
| Customer category/tariff | `water_accounts.tariff_category_id` | Resolve by category code; report conflicts |
| Meter number | `meters.meter_number` | Unique physical meter identity |
| Serial number | `meters.serial_number` | Nullable unique identity |
| Meter type, size, model, manufacturer | `meters` metadata fields | Preserved |
| Installation date | `meter_installations.installation_date` | Historical placement record |
| Initial reading | `meter_installations.initial_reading` | Decimal reading |
| Installation location | `meter_installations.installation_location` | Preserved |
| Seal number | `meter_installations.seal_number` | Preserved |
| External Supabase UUID | `supabase_id` on the matching aggregate | Immutable link after a successful match |
| Supabase Auth user UUID | `customers.supabase_auth_user_id` | Customer authentication link |

## Conflict resolution

The prior proposed mobile schema places `ip_number`, `meter_number`, and a single location on `customers`. These are not duplicated in the institutional schema. An account can have a single IP number and multiple historical meters; a customer can own multiple water accounts.

Supabase bills and payments are projections and must not be imported as authoritative totals without reconciliation. Their external identifiers are retained for matching and conflict reporting.

## Migration order

1. Zones and staff-user extensions.
2. Customers, tariff categories, and water accounts.
3. Meters and meter installations.
4. Billing cycles and meter readings.
5. Effective-dated tariffs and blocks.
6. Bills, bill lines, and adjustments.
7. Payments, allocations, and append-only ledger entries.
8. Billing settings, audit logs, and integration inbox/outbox.
9. Seeded roles, permissions, tariffs, and settings.

## Data migration strategy

All future imports are additive and idempotent. Matching order is Supabase UUID, IP number, meter number, serial number, linked account plus national ID/registration number, then verified phone. Ambiguous matches are reported and skipped rather than overwritten. Issued bills, payments, allocations, ledger entries, audit logs, and historical installations are never physically deleted.

## ERD

```text
zones ──< users
zones ──< water_accounts >── customers
                    │             │
                    │             └──< water_accounts
                    ├── tariff_categories ──< tariff_rates ──< tariff_blocks
                    ├──< meter_installations >── meters
                    ├──< bills >── billing_cycles
                    │       ├──< bill_items >── meter_readings
                    │       └──< bill_adjustments
                    ├──< payments ──< payment_allocations >── bills
                    └──< account_ledger_entries

integration_outbox and audit_logs record institutional changes without becoming source tables.
```
