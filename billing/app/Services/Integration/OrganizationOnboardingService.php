<?php

namespace App\Services\Integration;

use App\Models\CmsPlaceOfServiceCode;
use App\Models\CmsReferencePayer;
use App\Models\Organization;
use App\Models\OrganizationProvider;
use App\Models\Payer;
use App\Support\AppFeatures;
use App\Support\IntegrationRequirements;
use App\Support\OnboardingGuide;

class OrganizationOnboardingService
{
    public function __construct(private NpiRegistryClient $npiRegistry)
    {
    }

    public function guide(): array
    {
        return [
            'intro' => OnboardingGuide::intro(),
            'sections' => OnboardingGuide::sections(),
        ];
    }

    public function requirementsForOrganization(int $organizationId): array
    {
        $sections = IntegrationRequirements::allForOrganization($organizationId);
        $allReady = collect($sections)->every(fn ($s) => $s['ready']);

        return [
            'all_ready_for_production' => $allReady,
            'sections' => $sections,
            'drivers' => AppFeatures::drivers(),
            'guide' => $this->guide(),
            'organization' => $this->organizationProfile($organizationId),
            'reference_data' => $this->referenceDataSummary(),
        ];
    }

    public function organizationProfile(int $organizationId): array
    {
        $org = Organization::query()->with(['providers', 'payers.cmsReferencePayer'])->findOrFail($organizationId);
        $payers = $org->payers;

        return [
            'id' => $org->id,
            'name' => $org->name,
            'npi' => $org->npi,
            'tax_id' => $org->tax_id,
            'phone' => $org->phone,
            'email' => $org->email,
            'address' => $org->address,
            'providers' => $org->providers,
            'payers' => $payers->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'electronic_payer_id' => $p->electronic_payer_id,
                'cms_reference_payer_id' => $p->cms_reference_payer_id,
                'has_electronic_id' => (bool) $p->electronic_payer_id,
            ]),
            'checks' => $this->organizationChecks($org, $payers),
        ];
    }

    public function lookupNpi(string $npi): ?array
    {
        return $this->npiRegistry->lookup($npi);
    }

    public function applyFromNppes(int $organizationId, string $npi, ?string $applyAs = null): array
    {
        $record = $this->npiRegistry->lookup($npi);
        if (! $record) {
            throw new \RuntimeException('NPI not found in NPPES registry.');
        }

        $applyAs = $applyAs ?? $record['apply_as'];

        if ($applyAs === 'organization') {
            return $this->applyOrganizationFromNppes($organizationId, $record);
        }

        return $this->applyRenderingProviderFromNppes($organizationId, $record);
    }

    public function discoverRenderingProviders(int $organizationId, int $limit = 25): array
    {
        $org = Organization::query()->findOrFail($organizationId);
        $state = is_array($org->address) ? ($org->address['state'] ?? null) : null;
        $candidates = $this->npiRegistry->searchIndividualsByPractice($org->name, $state, $limit);

        $existing = OrganizationProvider::forOrganization($organizationId)
            ->pluck('npi')
            ->flip()
            ->all();

        $newCandidates = [];
        $alreadyLinked = 0;
        foreach ($candidates as $candidate) {
            if (isset($existing[$candidate['npi']])) {
                $alreadyLinked++;
            } else {
                $newCandidates[] = $candidate;
            }
        }

        return [
            'organization_name' => $org->name,
            'state' => $state,
            'candidates' => $newCandidates,
            'already_linked' => $alreadyLinked,
        ];
    }

    /**
     * @param  array<int, string>  $npis
     */
    public function applyRenderingProviders(int $organizationId, array $npis): array
    {
        $added = 0;
        $errors = [];

        foreach ($npis as $npi) {
            try {
                $this->applyFromNppes($organizationId, $npi, 'rendering_provider');
                $added++;
            } catch (\Throwable $e) {
                $errors[] = ['npi' => $npi, 'message' => $e->getMessage()];
            }
        }

        return [
            'message' => "Added {$added} rendering provider(s) from NPPES.",
            'added' => $added,
            'errors' => $errors,
            'organization' => $this->organizationProfile($organizationId),
        ];
    }

    public function updateOrganizationTaxId(int $organizationId, string $taxId): array
    {
        $org = Organization::query()->findOrFail($organizationId);
        $org->update(['tax_id' => trim($taxId)]);

        return [
            'message' => 'Federal Tax ID (EIN) saved.',
            'organization' => $this->organizationProfile($organizationId),
        ];
    }

    private function applyOrganizationFromNppes(int $organizationId, array $record): array
    {
        $org = Organization::query()->findOrFail($organizationId);

        $updates = [
            'npi' => $record['npi'],
        ];

        if (! empty($record['organization_name'])) {
            $updates['name'] = $record['organization_name'];
        }

        if (! empty($record['phone'])) {
            $updates['phone'] = $record['phone'];
        }

        if (! empty($record['address'])) {
            $updates['address'] = array_filter([
                'line1' => $record['address']['line1'],
                'line2' => $record['address']['line2'] ?? null,
                'city' => $record['address']['city'],
                'state' => $record['address']['state'],
                'zip' => $record['address']['zip'],
            ], fn ($v) => $v !== null && $v !== '');
        }

        $org->update($updates);

        return [
            'message' => 'Organization profile updated from NPPES (name, NPI, address). Enter EIN manually — it is not published in NPPES.',
            'applied' => 'organization',
            'nppes' => $record,
            'organization' => $this->organizationProfile($organizationId),
        ];
    }

    private function applyRenderingProviderFromNppes(int $organizationId, array $record): array
    {
        if (empty($record['first_name']) || empty($record['last_name'])) {
            throw new \RuntimeException('NPPES record is missing provider name.');
        }

        OrganizationProvider::query()->updateOrCreate(
            [
                'organization_id' => $organizationId,
                'npi' => $record['npi'],
            ],
            [
                'first_name' => $record['first_name'],
                'last_name' => $record['last_name'],
                'credentials' => $record['credential'],
                'taxonomy_code' => $record['taxonomy_code'],
                'is_active' => true,
            ]
        );

        return [
            'message' => "Rendering provider {$record['name']} ({$record['npi']}) added from NPPES.",
            'applied' => 'rendering_provider',
            'nppes' => $record,
            'organization' => $this->organizationProfile($organizationId),
        ];
    }

    public function syncPayerElectronicIds(int $organizationId): array
    {
        $updated = 0;
        $linked = 0;

        $payers = Payer::forOrganization($organizationId)->get();
        foreach ($payers as $payer) {
            if ($payer->cms_reference_payer_id) {
                $ref = CmsReferencePayer::find($payer->cms_reference_payer_id);
                if ($ref && $ref->electronic_payer_id && $payer->electronic_payer_id !== $ref->electronic_payer_id) {
                    $payer->update(['electronic_payer_id' => $ref->electronic_payer_id]);
                    $updated++;
                }
                $linked++;

                continue;
            }

            $ref = CmsReferencePayer::query()
                ->where('name', $payer->name)
                ->whereNotNull('electronic_payer_id')
                ->first();

            if (! $ref) {
                $ref = CmsReferencePayer::query()
                    ->where('name', 'like', '%'.explode(' ', $payer->name)[0].'%')
                    ->whereNotNull('electronic_payer_id')
                    ->first();
            }

            if ($ref) {
                $payer->update([
                    'cms_reference_payer_id' => $ref->id,
                    'electronic_payer_id' => $ref->electronic_payer_id,
                    'cms_state_id' => $ref->cms_state_id,
                ]);
                $updated++;
                $linked++;
            }
        }

        return [
            'message' => "Synced {$updated} payer electronic ID(s) from CMS reference.",
            'updated' => $updated,
            'linked' => $linked,
            'total_payers' => $payers->count(),
        ];
    }

    public function payerDirectory(int $limit = 100): array
    {
        $rows = CmsReferencePayer::query()
            ->with('state')
            ->whereNotNull('electronic_payer_id')
            ->where('electronic_payer_id', '!=', '')
            ->orderBy('program')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            'total_with_electronic_id' => CmsReferencePayer::query()
                ->whereNotNull('electronic_payer_id')
                ->where('electronic_payer_id', '!=', '')
                ->count(),
            'rows' => $rows->map(fn ($p) => [
                'code' => $p->code,
                'name' => $p->name,
                'program' => $p->program,
                'electronic_payer_id' => $p->electronic_payer_id,
                'state' => $p->state?->code,
            ]),
        ];
    }

    public function placeOfServiceCodes(int $limit = 60): array
    {
        $rows = CmsPlaceOfServiceCode::query()->orderBy('code')->limit($limit)->get();

        return [
            'total' => CmsPlaceOfServiceCode::count(),
            'common' => $rows->map(fn ($c) => [
                'code' => $c->code,
                'name' => $c->name,
                'definition' => $c->definition,
            ]),
            'source' => 'HL7/CMS Place of Service CodeSystem (bundled in docrev:cms-import)',
        ];
    }

    private function organizationChecks(Organization $org, $payers): array
    {
        $providers = OrganizationProvider::forOrganization($org->id)->count();

        return [
            'has_legal_name' => (bool) $org->name,
            'has_org_npi' => (bool) $org->npi,
            'has_tax_id' => (bool) $org->tax_id,
            'has_billing_address' => is_array($org->address) && ! empty($org->address),
            'has_rendering_providers' => $providers > 0,
            'payers_with_electronic_id' => $payers->where('electronic_payer_id', '!=', null)->count(),
            'total_payers' => $payers->count(),
        ];
    }

    public function referenceDataSummary(): array
    {
        return [
            'place_of_service_codes' => CmsPlaceOfServiceCode::count(),
            'reference_payers_with_electronic_id' => CmsReferencePayer::query()
                ->whereNotNull('electronic_payer_id')
                ->where('electronic_payer_id', '!=', '')
                ->count(),
            'cms_import_command' => 'php artisan docrev:cms-import --fresh',
            'onboarding_sync_command' => 'php artisan docrev:onboarding-sync',
        ];
    }
}
