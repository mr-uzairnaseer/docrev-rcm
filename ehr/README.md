# DocRev EHR

Clinical EHR application — patients, providers, locations, encounters.

## Run

```bash
php artisan serve --port=8001
```

## Health

`GET http://localhost:8001/api/health`

## Demo login

- Email: `admin@demo-medical.test`
- Password: `password`

## API endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Staff login |
| GET | `/api/auth/me` | Current user |
| GET/POST | `/api/patients` | List / create patients |
| GET/POST | `/api/providers` | List / create providers |
| GET/POST | `/api/locations` | List / create locations |
| GET/POST/PATCH | `/api/encounters` | Manage encounters |
| POST | `/api/encounters/{id}/sign` | Sign & complete encounter |

All protected routes require `Authorization: Bearer {token}`.

- `Organization`, `Patient`, `Provider`, `Location`, `Encounter`
- All PHI models use `Auditable` + `BelongsToOrganization`
