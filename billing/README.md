# DocRev RCM

Revenue cycle management — charges, claims, payers, eligibility, and remittance.

## Run

```bash
php artisan serve --port=8002
```

## Health

`GET http://localhost:8002/api/health`

## Demo login

- Email: `billing@demo-medical.test`
- Password: `password`

## API endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Staff login |
| GET/POST | `/api/patients` | Billing patients |
| GET/POST | `/api/charges` | Charge capture |
| POST | `/api/charges/{id}/ready` | Mark charge ready to bill |
| GET/POST | `/api/claims` | List / build claim from charges |
| POST | `/api/claims/{id}/ready` | Mark claim ready (pre-clearinghouse) |

## Domain models

```
Patient → Charge → Claim → ClaimLine
         ↘ Payer ↗
```

## Next phases

- Eligibility (EDI 270/271)
- Claims submission (EDI 837)
- ERA posting (EDI 835)
- Denial management
