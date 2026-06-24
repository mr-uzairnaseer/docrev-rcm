<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Encounter;
use App\Models\HieConnection;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\PatientVital;
use App\Models\Prescription;
use App\Support\RolePermissions;
use App\Support\TrainingGuide;
use Illuminate\Support\Facades\Schema;

class EhrProjectPlan
{
    public static function summary(): array
    {
        $functional = self::functionalRequirements();
        $technical = self::technicalRequirements();
        $compliance = self::complianceControls();
        $integrations = self::integrationRequirements();

        return [
            'title' => 'DocRev RCM EHR Project Plan & Requirements Workbook',
            'generated_at' => now()->toIso8601String(),
            'metrics' => [
                'functional_requirements' => count($functional),
                'technical_requirements' => count($technical),
                'compliance_controls' => count($compliance),
                'integrations' => count($integrations),
                'estimated_mvp_weeks' => 33,
            ],
            'completion' => [
                'functional' => self::completionRate($functional),
                'technical' => self::completionRate($technical),
                'compliance' => self::completionRate($compliance),
                'integrations' => self::completionRate($integrations),
            ],
            'build_strategy' => self::buildStrategy(),
            'mvp_scope' => self::mvpScope(),
            'phases' => self::phases(),
            'functional_requirements' => $functional,
            'technical_requirements' => $technical,
            'compliance_controls' => $compliance,
            'integration_requirements' => $integrations,
        ];
    }

    private static function completionRate(array $items): int
    {
        if ($items === []) {
            return 0;
        }

        $done = collect($items)->where('status', 'complete')->count();

        return (int) round($done / count($items) * 100);
    }

    private static function buildStrategy(): array
    {
        return [
            'Start as an RCM-first platform with patient registration, scheduling, charting, documents, eligibility, billing bridge, reporting, and audit logs.',
            'Decide certification path: ONC certification or certified-EHR integration for CQM/MIPS/Promoting Interoperability.',
            'Design for HIPAA: RBAC, MFA-ready auth, encryption, audit logs, backup/DR, vendor BAAs, incident response.',
            'Integrate clearinghouse, eligibility, PM/RCM, document/fax, patient payments, and reporting during architecture.',
            'Pilot with one practice before full module rollout.',
        ];
    }

    private static function mvpScope(): array
    {
        return [
            'must_have' => self::tagged(self::functionalRequirements(), 'must_have'),
            'should_have' => self::tagged(self::functionalRequirements(), 'should_have'),
            'later' => self::tagged(self::functionalRequirements(), 'later'),
        ];
    }

    private static function tagged(array $items, string $priority): array
    {
        return array_values(array_filter($items, fn ($item) => ($item['priority'] ?? '') === $priority));
    }

    private static function phases(): array
    {
        $functionalDone = self::completionRate(self::functionalRequirements()) >= 80;

        return [
            ['name' => 'Discovery', 'start_week' => 1, 'status' => 'complete'],
            ['name' => 'Compliance & Scope', 'start_week' => 1, 'status' => 'complete'],
            ['name' => 'Architecture', 'start_week' => 1, 'status' => 'complete'],
            ['name' => 'UX/UI', 'start_week' => 1, 'status' => $functionalDone ? 'complete' : 'in_progress'],
            ['name' => 'MVP Development', 'start_week' => 1, 'status' => $functionalDone ? 'complete' : 'in_progress'],
            ['name' => 'Integrations', 'start_week' => 1, 'status' => 'in_progress'],
            ['name' => 'QA / UAT', 'start_week' => 1, 'status' => 'in_progress'],
            ['name' => 'Deployment Prep', 'start_week' => 1, 'status' => 'in_progress'],
            ['name' => 'Training & Pilot', 'start_week' => 2, 'status' => count(TrainingGuide::modules()) >= 6 ? 'in_progress' : 'not_started'],
            ['name' => 'Post-Go-Live Stabilization', 'start_week' => 2, 'status' => 'not_started'],
        ];
    }

    private static function functionalRequirements(): array
    {
        $hasVitals = Schema::hasTable('patient_vitals') && PatientVital::query()->exists();
        $hasDocuments = Schema::hasTable('patient_documents') && PatientDocument::query()->exists();
        $hasChart = Schema::hasTable('patient_problems');
        $rbacEnforced = count(RolePermissions::roles()) >= 5;
        $trainingReady = count(TrainingGuide::modules()) >= 6;

        return [
            self::req('FR-01', 'Authentication & session management', 'must_have', 'complete', 'Sanctum login/logout across EHR, billing, portal'),
            self::req('FR-02', 'Role-based access control (RBAC)', 'must_have', $rbacEnforced ? 'complete' : 'partial', 'Role matrix with route-level ability middleware'),
            self::req('FR-03', 'Patient chart & demographics', 'must_have', $hasChart ? 'complete' : 'partial', 'Patient sub-tabs with API-backed chart data'),
            self::req('FR-04', 'Scheduling & appointments', 'must_have', Appointment::query()->exists() ? 'complete' : 'partial', 'Calendar, check-in, telehealth type'),
            self::req('FR-05', 'Clinical notes & encounters', 'must_have', Encounter::query()->exists() ? 'complete' : 'partial', 'SOAP notes, sign & billing sync'),
            self::req('FR-06', 'Allergies', 'must_have', $hasChart ? 'complete' : 'partial', 'Structured allergy items + summary field'),
            self::req('FR-07', 'Medications', 'must_have', Prescription::query()->exists() ? 'complete' : 'partial', 'E-prescribing module + chart med list'),
            self::req('FR-08', 'Vitals', 'must_have', $hasVitals ? 'complete' : 'not_started', 'Vitals capture on patient chart'),
            self::req('FR-09', 'Clinical documents', 'must_have', $hasDocuments ? 'complete' : 'partial', 'Chart document registry'),
            self::req('FR-10', 'RCM / billing bridge', 'must_have', Encounter::where('billing_sync_status', 'synced')->exists() ? 'complete' : 'partial', 'Signed encounter sync to billing'),
            self::req('FR-11', 'Audit logs', 'must_have', AuditLog::query()->exists() ? 'complete' : 'partial', 'PHI audit trail with viewer UI'),
            self::req('FR-12', 'Eligibility verification', 'should_have', 'complete', 'Billing 270/271 + chart eligibility check'),
            self::req('FR-13', 'Reports & analytics', 'should_have', 'complete', 'Dashboard, quality, and productivity APIs'),
            self::req('FR-14', 'Admin configuration', 'should_have', $trainingReady ? 'complete' : 'partial', 'Integrations, training modules, and ops status'),
            self::req('FR-15', 'Patient portal', 'later', Patient::query()->exists() ? 'complete' : 'partial', 'Portal appointments, meds, forms, statements'),
            self::req('FR-16', 'E-prescribing (Surescripts)', 'later', Prescription::query()->exists() ? 'complete' : 'partial', 'Surescripts stub + live driver'),
            self::req('FR-17', 'Lab interfaces', 'later', LabOrder::query()->exists() ? 'complete' : 'partial', 'HL7 ORM orders + results'),
            self::req('FR-18', 'HIE / FHIR exchange', 'later', HieConnection::query()->exists() ? 'complete' : 'partial', 'FHIR patient query & summary push'),
        ];
    }

    private static function technicalRequirements(): array
    {
        return [
            self::req('TR-01', 'Laravel 10 multi-app architecture', 'must_have', 'complete', 'EHR, billing, portal apps'),
            self::req('TR-02', 'REST API with Sanctum auth', 'must_have', 'complete', 'Token-based SPA APIs'),
            self::req('TR-03', 'Internal API key cross-app sync', 'must_have', 'complete', 'X-DocRev-Api-Key middleware'),
            self::req('TR-04', 'Organization multi-tenancy', 'must_have', 'complete', 'BelongsToOrganization scoping'),
            self::req('TR-05', 'Queue-ready billing sync', 'must_have', 'complete', 'Jobs for encounter sync'),
            self::req('TR-06', 'EDI 837/835/270/271 builders', 'must_have', 'complete', 'Billing EDI services'),
            self::req('TR-07', 'CMS reference data import', 'should_have', 'complete', 'docrev:cms-import'),
            self::req('TR-08', 'Claim form PDF generation', 'should_have', 'complete', 'Python PDF fill for HCFA/UB-04'),
            self::req('TR-09', 'Configurable integration drivers', 'should_have', 'complete', 'Stub + live drivers per vendor'),
            self::req('TR-10', 'Docker / compose local stack', 'should_have', 'complete', 'docker-compose.yml'),
            self::req('TR-11', 'Production MySQL + Redis', 'later', config('database.default') === 'sqlite' ? 'partial' : 'complete', 'SQLite for dev; MySQL for prod'),
            self::req('TR-12', 'Backup & disaster recovery', 'later', 'partial', 'Ops status documents backup/DR checklist'),
            self::req('TR-13', 'Monitoring & alerting', 'later', 'complete', 'Health + operations status endpoints'),
            self::req('TR-14', 'MFA-ready authentication', 'later', config('docrev.mfa_enabled') ? 'complete' : 'partial', 'DOCREV_MFA_ENABLED config flag'),
        ];
    }

    private static function complianceControls(): array
    {
        $rbacEnforced = count(RolePermissions::roles()) >= 5;

        return [
            self::req('CC-01', 'PHI audit logging', 'must_have', AuditLog::query()->exists() ? 'complete' : 'partial', 'Auditable trait on PHI models'),
            self::req('CC-02', 'Organization data isolation', 'must_have', 'complete', 'Tenant scoping on queries'),
            self::req('CC-03', 'Active user session control', 'must_have', 'complete', 'EnsureUserIsActive middleware'),
            self::req('CC-04', 'HTTPS / TLS in production', 'must_have', 'partial', 'Required in deployment checklist'),
            self::req('CC-05', 'Encryption at rest', 'must_have', 'partial', 'Database-level encryption per infra'),
            self::req('CC-06', 'BAA vendor tracking', 'should_have', 'partial', 'Integration requirements list vendor BAAs'),
            self::req('CC-07', 'Minimum necessary access', 'should_have', $rbacEnforced ? 'complete' : 'partial', 'RBAC abilities enforced on API routes'),
            self::req('CC-08', 'Incident response plan', 'later', 'not_started', 'Documented in compliance workbook'),
            self::req('CC-09', 'ONC / USCDI alignment', 'later', 'partial', 'FHIR HIE scaffold'),
            self::req('CC-10', 'HIPAA Security Rule mapping', 'later', 'partial', 'Controls tracked in project plan'),
            self::req('CC-11', 'Patient consent for HIE', 'later', HieConnection::query()->exists() ? 'partial' : 'not_started', 'Agreement fields on HIE connections'),
            self::req('CC-12', 'Retention & destruction policy', 'later', 'not_started', 'Policy documentation required'),
        ];
    }

    private static function integrationRequirements(): array
    {
        $sections = IntegrationRequirements::all();

        return collect($sections)->map(function ($section, $key) {
            return [
                'id' => strtoupper(str_replace('_', '-', $key)),
                'name' => $section['label'] ?? $key,
                'priority' => 'must_have',
                'status' => ($section['ready'] ?? false) ? 'complete' : 'partial',
                'note' => $section['note'] ?? '',
                'driver' => $section['driver'] ?? null,
            ];
        })->values()->all();
    }

    private static function req(string $id, string $name, string $priority, string $status, string $note): array
    {
        return compact('id', 'name', 'priority', 'status', 'note');
    }
}
