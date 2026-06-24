# DocRev — Healthcare Platform

Three independent Laravel applications for clinical, billing, and patient-facing workflows.

## Applications

| App | Folder | Port | Purpose |
|-----|--------|------|---------|
| **EHR** | `ehr/` | 8001 | Clinical charting, encounters, providers |
| **RCM** | `billing/` | 8002 | Revenue cycle, claims, payments |
| **Portal** | `portal/` | 8003 | Patient self-service portal |

## Requirements

- PHP 8.0+ (Laravel 9) — upgrade to PHP 8.2+ for Laravel 11
- Composer
- MySQL 8 (production) or SQLite (local dev)
- Redis (queues & cache, production)

## Quick Start

> **Local dev:** Apps use **SQLite** by default. For production, switch each `.env` to MySQL — see `docker-compose.yml`.

### 1. Start infrastructure (optional)

```bash
docker compose up -d
```

### 2. Configure each app

```bash
cd ehr && cp .env.example .env && php artisan key:generate
cd ../billing && cp .env.example .env && php artisan key:generate
cd ../portal && cp .env.example .env && php artisan key:generate
```

### 3. Run migrations & seed

```bash
cd ehr && php artisan migrate --seed
cd ../billing && php artisan migrate --seed
cd ../portal && php artisan migrate --seed
```

### 4. Start dev servers

```bash
cd ehr && php artisan serve --port=8001
cd billing && php artisan serve --port=8002
cd portal && php artisan serve --port=8003
```

### Health checks

- EHR: `GET http://localhost:8001/api/health`
- RCM: `GET http://localhost:8002/api/health`
- Portal: `GET http://localhost:8003/api/health`

### Integration checklist (RCM)

```bash
cd billing && php artisan docrev:requirements
```

Or open **RCM → Setup** tab at `http://localhost:8002`.

### CMS reference data (states, MACs, payers)

Official CMS/NUCC public reference data is bundled and imported into the RCM app:

```bash
cd billing && php artisan docrev:cms-import
```

This loads **all** datasets: 56 states/territories, 19 MACs, Medicaid/CHIP/marketplace/commercial payers, **~1,000+ Medicare Advantage H-contracts**, **~880 NUCC taxonomy codes**, **~7,000+ HCPCS codes**, QHP issuers by state, and 52 place-of-service codes.

Use `--no-download` to import from bundled files only. Browse **RCM → CMS Reference** or `/api/cms/*`.

---

## What you need to provide for live functionality

Everything below works in **stub/sandbox mode** today. For production claims and real payments, provide:

### 1. Practice / organization (required for all live billing)

| Item | Used for |
|------|----------|
| Legal practice name | Claims, statements |
| Organization NPI (Type 2) | 837 billing provider |
| Federal Tax ID (EIN) | Payer enrollment |
| Billing address | Claims |
| Rendering provider NPIs (Type 1) | Service lines |
| Place of service codes | Claim lines |

### 2. Clearinghouse (837 submit + 835 ERA)

Choose a vendor: **Availity**, **Change Healthcare (Optum)**, **Waystar**, **Office Ally**, etc.

| Item | Env variable(s) |
|------|-----------------|
| Vendor choice | `CLEARINGHOUSE_DRIVER=availity` or `change_healthcare` or `sftp` |
| API client ID / secret | `AVAILITY_CLIENT_ID`, `AVAILITY_CLIENT_SECRET` (or Change Healthcare equivalents) |
| Submitter ID | `AVAILITY_SUBMITTER_ID` |
| Payer enrollment | Per insurance company — done with clearinghouse, not in code |
| SFTP (if applicable) | `CLEARINGHOUSE_SFTP_HOST`, username, password/key |

**You provide:** clearinghouse sandbox or production API credentials + payer enrollment confirmation.

### 3. Eligibility (270/271)

Usually same vendor as clearinghouse.

| Item | Env variable |
|------|--------------|
| Driver | `ELIGIBILITY_DRIVER=availity` or `change_healthcare` |
| API credentials | Same as clearinghouse typically |

**You provide:** API access + payer electronic IDs (configured in billing **Payers**).

### 4. Cross-app integration keys

| Connection | EHR `.env` | Billing `.env` | Portal `.env` |
|------------|------------|----------------|---------------|
| EHR → Billing sync | `BILLING_API_URL`, `BILLING_API_KEY` | `INTERNAL_API_KEY` (same value) | — |
| Billing → Portal statements | — | `PORTAL_API_URL` | `INTERNAL_API_KEY` (same value) |

Use a strong random key in production (not `docrev-internal-dev-key`).

### 5. Production infrastructure

| Item | Notes |
|------|-------|
| PHP 8.2+ | Laravel 11 upgrade path |
| MySQL 8 | `docrev_ehr`, `docrev_billing`, `docrev_portal` |
| Redis | `QUEUE_CONNECTION=redis` + `php artisan queue:work` on EHR (sync jobs) |
| HTTPS / TLS | Required for PHI |
| Backups | HIPAA-compliant retention |
| BAA | With hosting, clearinghouse, any PHI vendors |

### 6. Optional (future phases)

- Patient payment gateway (Stripe Healthcare / Rectangle Health) for portal pay-now
- e-Prescribing (Surescripts)
- Lab interfaces (HL7/FHIR)
- HIE / FHIR integration

---

## Demo credentials

| App | Email | Password |
|-----|-------|----------|
| EHR | `admin@demo-medical.test` | `password` |
| RCM | `billing@demo-medical.test` | `password` |
| Portal staff | `portal@demo-medical.test` | `password` |
| Portal patient | `jane.doe@patient.test` | `password` |

## End-to-end demo flow

1. **EHR** — register patient → encounter → diagnoses + charges → **Sign & Sync**
2. **RCM** — **Eligibility** check → build claim → scrub → submit → simulate ERA
3. **Portal** — patient login → view updated statement balance

## Architecture

- **Multi-tenant**: All PHI scoped by `organization_id`
- **Audit logging**: HIPAA-oriented access trail on auditable models
- **Separate databases**: `docrev_ehr`, `docrev_billing`, `docrev_portal`
- **Integration**: REST APIs + shared internal API keys between apps

## Upgrade path

Upgrade PHP to 8.2+ to move to Laravel 11 when ready.
