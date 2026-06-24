# DocRev Portal

Patient-facing portal — appointments, statements, account access.

## Run

```bash
php artisan serve --port=8003
```

## Health

`GET http://localhost:8003/api/health`

## Demo accounts

**Staff admin**
- Email: `portal@demo-medical.test`
- Password: `password`

**Patient**
- Email: `jane.doe@patient.test`
- Password: `password`

## API endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Staff login |
| POST | `/api/patient/login` | Patient login |
| GET | `/api/patient/me` | Patient profile |
| GET | `/api/patient/appointments` | Upcoming appointments |
| GET | `/api/patient/statements` | Billing statements |

## Domain models

- `PatientAccount`, `PortalAppointment`, `PatientStatement`
