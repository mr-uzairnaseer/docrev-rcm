<?php

namespace App\Services\CmsReference;

use App\Models\CmsClaimAdjustmentCode;
use App\Models\CmsHcpcsCode;
use App\Models\CmsIcd10Code;
use App\Models\CmsMac;
use App\Models\CmsMedicareAdvantageContract;
use App\Models\CmsModifier;
use App\Models\CmsPlaceOfServiceCode;
use App\Models\CmsQhpIssuer;
use App\Models\CmsReferencePayer;
use App\Models\CmsRegion;
use App\Models\CmsRemittanceRemarkCode;
use App\Models\CmsRevenueCode;
use App\Models\CmsState;
use App\Models\CmsTaxonomyCode;
use App\Models\CmsTypeOfBillCode;
use App\Support\CmsReference\CmsBillingCodeCatalog;
use App\Support\CmsReference\CmsCatalog;
use App\Support\CmsReference\CmsCsvReader;
use App\Support\CmsReference\CmsExtendedPayers;
use App\Support\CmsReference\CmsHcpcsParser;
use App\Support\CmsReference\CmsIcd10Parser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CmsReferenceImporter
{
    private CmsDataDownloader $downloader;

    public function __construct(CmsDataDownloader $downloader)
    {
        $this->downloader = $downloader;
    }

    public function import(bool $fresh = true, bool $download = true, ?array $only = null): array
    {
        $datasets = $this->resolveDatasets($only);

        if ($download) {
            if ($only === null) {
                $this->downloader->ensureBundledDatasets();
            } else {
                foreach ($datasets as $dataset) {
                    $this->downloader->ensureDataset($dataset);
                }
            }
        }

        if ($fresh) {
            $this->truncate($datasets);
        }

        $counts = [];
        foreach ($datasets as $dataset) {
            $counts[$dataset] = $this->importDataset($dataset);
        }

        return $counts;
    }

    private function resolveDatasets(?array $only): array
    {
        $all = CmsBillingCodeCatalog::datasetKeys();

        if ($only === null || $only === []) {
            return $all;
        }

        $selected = array_values(array_intersect($all, $only));
        if (in_array('payers', $selected, true)) {
            foreach (['regions', 'states', 'macs'] as $dependency) {
                if (! in_array($dependency, $selected, true)) {
                    $selected[] = $dependency;
                }
            }
        }

        if (in_array('medicare_advantage', $selected, true) && ! in_array('payers', $selected, true)) {
            $selected[] = 'payers';
        }

        if (in_array('qhp_issuers', $selected, true) && ! in_array('states', $selected, true)) {
            $selected[] = 'states';
        }

        return array_values(array_unique(array_intersect($all, $selected)));
    }

    private function importDataset(string $dataset): int
    {
        return match ($dataset) {
            'regions' => $this->importRegions(),
            'states' => $this->importStates(),
            'macs' => $this->importMacs(),
            'payers' => $this->importPayers(),
            'medicare_advantage' => $this->importMedicareAdvantageContracts(),
            'qhp_issuers' => $this->importQhpIssuers(),
            'pos' => $this->importPlaceOfServiceCodes(),
            'taxonomy' => $this->importTaxonomyCodes(),
            'hcpcs' => $this->importHcpcsCodes(),
            'icd10' => $this->importIcd10Codes(),
            'modifiers' => $this->importModifiers(),
            'carc' => $this->importClaimAdjustmentCodes(),
            'rarc' => $this->importRemittanceRemarkCodes(),
            'type_of_bill' => $this->importTypeOfBillCodes(),
            'revenue_codes' => $this->importRevenueCodes(),
            default => 0,
        };
    }

    private function truncate(array $datasets): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        if (in_array('payers', $datasets, true)) {
            CmsReferencePayer::query()->delete();
        }
        if (in_array('medicare_advantage', $datasets, true)) {
            CmsMedicareAdvantageContract::query()->delete();
        }
        if (in_array('qhp_issuers', $datasets, true)) {
            CmsQhpIssuer::query()->delete();
        }
        if (in_array('macs', $datasets, true)) {
            DB::table('cms_mac_states')->delete();
            CmsMac::query()->delete();
        }
        if (in_array('states', $datasets, true)) {
            CmsState::query()->delete();
        }
        if (in_array('regions', $datasets, true)) {
            CmsRegion::query()->delete();
        }
        if (in_array('pos', $datasets, true)) {
            CmsPlaceOfServiceCode::query()->delete();
        }
        if (in_array('taxonomy', $datasets, true)) {
            CmsTaxonomyCode::query()->delete();
        }
        if (in_array('hcpcs', $datasets, true)) {
            CmsHcpcsCode::query()->delete();
        }
        if (in_array('icd10', $datasets, true)) {
            CmsIcd10Code::query()->delete();
        }
        if (in_array('modifiers', $datasets, true)) {
            CmsModifier::query()->delete();
        }
        if (in_array('carc', $datasets, true)) {
            CmsClaimAdjustmentCode::query()->delete();
        }
        if (in_array('rarc', $datasets, true)) {
            CmsRemittanceRemarkCode::query()->delete();
        }
        if (in_array('type_of_bill', $datasets, true)) {
            CmsTypeOfBillCode::query()->delete();
        }
        if (in_array('revenue_codes', $datasets, true)) {
            CmsRevenueCode::query()->delete();
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function importRegions(): int
    {
        $count = 0;
        foreach (CmsCatalog::regions() as $region) {
            CmsRegion::create($region);
            $count++;
        }

        return $count;
    }

    private function importStates(): int
    {
        $regions = CmsRegion::query()->pluck('id', 'number');
        $count = 0;

        foreach (CmsCatalog::states() as $state) {
            CmsState::create([
                'code' => $state['code'],
                'name' => $state['name'],
                'cms_region_id' => $regions[$state['region_number']],
                'jurisdiction_type' => $state['jurisdiction_type'],
                'fips_code' => $state['fips_code'],
            ]);
            $count++;
        }

        return $count;
    }

    private function importMacs(): int
    {
        $stateIds = CmsState::query()->pluck('id', 'code');
        $count = 0;

        foreach (CmsCatalog::macs() as $mac) {
            $states = $mac['states'];
            unset($mac['states']);

            $record = CmsMac::create($mac);
            foreach ($states as $stateCode) {
                if (! isset($stateIds[$stateCode])) {
                    continue;
                }
                $record->states()->attach($stateIds[$stateCode], ['is_primary' => true]);
            }
            $count++;
        }

        return $count;
    }

    private function importPayers(): int
    {
        $stateIds = CmsState::query()->pluck('id', 'code');
        $macByState = $this->primaryAbMacByState();
        $count = 0;

        foreach (CmsCatalog::federalPrograms() as $payer) {
            CmsReferencePayer::create($this->payerRow($payer));
            $count++;
        }

        foreach (CmsCatalog::medicaidPrograms() as $stateCode => $program) {
            CmsReferencePayer::create([
                'code' => 'MEDICAID-'.$stateCode,
                'name' => $program['name'],
                'program' => 'medicaid',
                'ownership' => 'public',
                'cms_state_id' => $stateIds[$stateCode] ?? null,
                'cms_mac_id' => $macByState[$stateCode] ?? null,
                'electronic_payer_id' => $program['electronic_payer_id'],
                'plan_type' => 'medicaid_ffs',
                'website' => $program['website'] ?? null,
                'notes' => 'State Medicaid agency — public/government payer per CMS/Medicaid.gov.',
            ]);
            $count++;
        }

        foreach (CmsCatalog::chipPrograms() as $stateCode => $program) {
            CmsReferencePayer::create([
                'code' => 'CHIP-'.$stateCode,
                'name' => $program['name'],
                'program' => 'chip',
                'ownership' => 'public',
                'cms_state_id' => $stateIds[$stateCode] ?? null,
                'cms_mac_id' => $macByState[$stateCode] ?? null,
                'electronic_payer_id' => $program['electronic_payer_id'],
                'plan_type' => 'chip',
                'notes' => 'Children\'s Health Insurance Program — state/federal partnership.',
            ]);
            $count++;
        }

        foreach (CmsCatalog::marketplaceByState() as $stateCode => $marketplace) {
            CmsReferencePayer::create([
                'code' => 'MARKETPLACE-'.$stateCode,
                'name' => $marketplace['name'],
                'program' => 'marketplace',
                'ownership' => $marketplace['ownership'],
                'cms_state_id' => $stateIds[$stateCode] ?? null,
                'plan_type' => $marketplace['plan_type'],
                'website' => $marketplace['website'],
                'notes' => 'Affordable Care Act qualified health plan marketplace per CMS.',
            ]);
            $count++;
        }

        foreach (CmsMac::query()->where('mac_type', 'ab_mac')->get() as $mac) {
            CmsReferencePayer::create([
                'code' => 'MEDICARE-AB-'.$mac->jurisdiction_code,
                'name' => 'Medicare Part A/B — '.$mac->name.' ('.$mac->jurisdiction_code.')',
                'program' => 'medicare',
                'ownership' => 'public',
                'cms_mac_id' => $mac->id,
                'electronic_payer_id' => $mac->contract_number,
                'cms_plan_id' => $mac->contract_number,
                'plan_type' => 'medicare_ab_mac',
                'website' => $mac->website,
                'phone' => $mac->phone,
                'notes' => 'Medicare Administrative Contractor (A/B MAC) per CMS jurisdiction assignment.',
            ]);
            $count++;
        }

        foreach (CmsMac::query()->where('mac_type', 'dme_mac')->get() as $mac) {
            CmsReferencePayer::create([
                'code' => 'MEDICARE-DME-'.$mac->jurisdiction_code,
                'name' => 'Medicare DME — '.$mac->name.' ('.$mac->jurisdiction_code.')',
                'program' => 'medicare',
                'ownership' => 'public',
                'cms_mac_id' => $mac->id,
                'electronic_payer_id' => $mac->contract_number,
                'cms_plan_id' => $mac->contract_number,
                'plan_type' => 'medicare_dme_mac',
                'website' => $mac->website,
                'phone' => $mac->phone,
                'notes' => 'DME MAC for DMEPOS claims per CMS geographic assignment.',
            ]);
            $count++;
        }

        foreach (CmsCatalog::commercialPayers() as $payer) {
            CmsReferencePayer::create([
                'code' => $payer['code'],
                'name' => $payer['name'],
                'program' => 'commercial',
                'ownership' => $payer['ownership'],
                'cms_state_id' => isset($payer['state']) ? ($stateIds[$payer['state']] ?? null) : null,
                'electronic_payer_id' => $payer['electronic_payer_id'] ?? null,
                'plan_type' => $payer['plan_type'] ?? 'commercial',
                'notes' => 'Private/commercial payer — verify electronic payer ID with clearinghouse enrollment.',
            ]);
            $count++;
        }

        foreach (CmsExtendedPayers::stateBcbsPayers() as $payer) {
            CmsReferencePayer::create([
                'code' => $payer['code'],
                'name' => $payer['name'],
                'program' => 'commercial',
                'ownership' => $payer['ownership'],
                'cms_state_id' => $stateIds[$payer['state']] ?? null,
                'electronic_payer_id' => $payer['electronic_payer_id'],
                'plan_type' => $payer['plan_type'],
                'notes' => 'State Blue Cross Blue Shield plan — verify payer ID with clearinghouse.',
            ]);
            $count++;
        }

        foreach ($stateIds as $stateCode => $stateId) {
            CmsReferencePayer::create([
                'code' => 'WORKCOMP-'.$stateCode,
                'name' => $stateCode.' Workers\' Compensation',
                'program' => 'workers_comp',
                'ownership' => 'public',
                'cms_state_id' => $stateId,
                'plan_type' => 'workers_comp',
                'notes' => 'State workers\' compensation — billing rules vary by state.',
            ]);
            $count++;
        }

        return $count;
    }

    private function importMedicareAdvantageContracts(): int
    {
        $path = database_path('data/cms/medicare-advantage-contracts.csv');
        $rows = CmsCsvReader::rows($path);
        $count = 0;
        $now = now();

        foreach ($rows as $row) {
            $contractNumber = strtoupper(trim($row['Contract Number'] ?? ''));
            if ($contractNumber === '') {
                continue;
            }

            $enrollment = $this->intOrNull($row['Enrollment'] ?? null);
            $offersPartD = strtoupper(trim($row['Offers Part D'] ?? '')) === 'YES';

            CmsMedicareAdvantageContract::create([
                'contract_number' => $contractNumber,
                'organization_type' => $row['Organization Type'] ?? null,
                'plan_type' => $row['Plan Type'] ?? null,
                'organization_name' => $row['Organization Name'] ?? $contractNumber,
                'marketing_name' => $row['Organization Marketing Name'] ?? null,
                'parent_organization' => $row['Parent Organization'] ?? null,
                'contract_effective_date' => $this->parseDate($row['Contract Effective Date'] ?? null),
                'offers_part_d' => $offersPartD,
                'ma_enrollment' => $this->intOrNull($row['MAOnly'] ?? null),
                'part_d_enrollment' => $this->intOrNull($row['PartD'] ?? null),
                'total_enrollment' => $enrollment,
                'ownership' => $this->ownershipForMaContract($row['Organization Type'] ?? ''),
            ]);

            if (preg_match('/^H\d{4}$/', $contractNumber)) {
                CmsReferencePayer::create([
                    'code' => 'MA-'.$contractNumber,
                    'name' => trim(($row['Organization Marketing Name'] ?? $row['Organization Name'] ?? 'Medicare Advantage').' ('.$contractNumber.')'),
                    'program' => 'medicare_advantage',
                    'ownership' => 'private',
                    'electronic_payer_id' => $contractNumber,
                    'cms_plan_id' => $contractNumber,
                    'plan_type' => $row['Plan Type'] ?? 'medicare_advantage',
                    'notes' => 'CMS Medicare Advantage contract '.($row['Parent Organization'] ?? '').' — enrollment '.$enrollment,
                ]);
            }

            $count++;
        }

        return $count;
    }

    private function importQhpIssuers(): int
    {
        $stateIds = CmsState::query()->pluck('id', 'code');
        $count = 0;
        $batch = [];

        foreach (CmsExtendedPayers::qhpIssuers() as $issuer) {
            $batch[] = [
                'issuer_id' => $issuer['id'],
                'issuer_name' => $issuer['name'].' ('.$issuer['state'].')',
                'cms_state_id' => $stateIds[$issuer['state']] ?? null,
                'market_type' => $issuer['market_type'],
                'ownership' => $issuer['ownership'],
                'website' => 'https://www.healthcare.gov/',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;
        }

        foreach (array_chunk($batch, 500) as $chunk) {
            CmsQhpIssuer::insert($chunk);
        }

        return $count;
    }

    private function importPlaceOfServiceCodes(): int
    {
        $path = database_path('data/cms/place-of-service-codes.json');
        if (! File::exists($path)) {
            return 0;
        }

        $payload = json_decode(File::get($path), true);
        $concepts = $payload['concept'] ?? [];
        $count = 0;

        foreach ($concepts as $concept) {
            CmsPlaceOfServiceCode::create([
                'code' => $concept['code'],
                'name' => $concept['display'],
                'definition' => $concept['definition'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    private function importTaxonomyCodes(): int
    {
        $path = database_path('data/cms/nucc_taxonomy.csv');
        $rows = CmsCsvReader::rows($path);
        if ($rows === []) {
            $count = 0;
            foreach (CmsCatalog::taxonomyCodes() as $taxonomy) {
                CmsTaxonomyCode::create($taxonomy);
                $count++;
            }

            return $count;
        }

        $batch = [];
        $now = now();
        foreach ($rows as $row) {
            $batch[] = [
                'code' => $row['Code'],
                'grouping' => $row['Grouping'] ?: null,
                'classification' => $row['Classification'] ?: null,
                'specialization' => $row['Specialization'] ?: null,
                'definition' => $row['Definition'] ?: ($row['Display Name'] ?? null),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($batch, 500) as $chunk) {
            CmsTaxonomyCode::insert($chunk);
        }

        return count($batch);
    }

    private function importHcpcsCodes(): int
    {
        $path = database_path('data/cms/hcpcs-2025-jan.txt');
        $codes = CmsHcpcsParser::parseFile($path);
        if ($codes === []) {
            return 0;
        }

        $now = now();
        $batch = array_map(fn ($code) => array_merge($code, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $codes);

        foreach (array_chunk($batch, 500) as $chunk) {
            CmsHcpcsCode::insert($chunk);
        }

        return count($batch);
    }

    private function importIcd10Codes(): int
    {
        $path = database_path('data/cms/icd10cm_codes_2025.txt');
        $codes = CmsIcd10Parser::parseCodesFile($path);
        if ($codes === []) {
            return 0;
        }

        $now = now();
        $batch = array_map(fn ($code) => array_merge($code, [
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $codes);

        foreach (array_chunk($batch, 500) as $chunk) {
            CmsIcd10Code::insert($chunk);
        }

        return count($batch);
    }

    private function importModifiers(): int
    {
        return $this->importCodeCatalog(CmsBillingCodeCatalog::modifiers(), CmsModifier::class, [
            'code', 'description', 'level',
        ]);
    }

    private function importClaimAdjustmentCodes(): int
    {
        return $this->importCodeCatalog(CmsBillingCodeCatalog::claimAdjustmentCodes(), CmsClaimAdjustmentCode::class, [
            'code', 'group_code', 'description',
        ]);
    }

    private function importRemittanceRemarkCodes(): int
    {
        return $this->importCodeCatalog(CmsBillingCodeCatalog::remittanceRemarkCodes(), CmsRemittanceRemarkCode::class, [
            'code', 'description',
        ]);
    }

    private function importTypeOfBillCodes(): int
    {
        return $this->importCodeCatalog(CmsBillingCodeCatalog::typeOfBillCodes(), CmsTypeOfBillCode::class, [
            'code', 'description', 'facility_type', 'care_type',
        ]);
    }

    private function importRevenueCodes(): int
    {
        return $this->importCodeCatalog(CmsBillingCodeCatalog::revenueCodes(), CmsRevenueCode::class, [
            'code', 'description', 'category',
        ]);
    }

    private function importCodeCatalog(array $rows, string $modelClass, array $fields): int
    {
        if ($rows === []) {
            return 0;
        }

        $now = now();
        $batch = [];

        foreach ($rows as $row) {
            $entry = ['is_active' => true, 'created_at' => $now, 'updated_at' => $now];
            foreach ($fields as $field) {
                $entry[$field] = $row[$field] ?? null;
            }
            $batch[] = $entry;
        }

        foreach (array_chunk($batch, 500) as $chunk) {
            $modelClass::insert($chunk);
        }

        return count($batch);
    }

    private function payerRow(array $payer): array
    {
        return [
            'code' => $payer['code'],
            'name' => $payer['name'],
            'program' => $payer['program'],
            'ownership' => $payer['ownership'],
            'electronic_payer_id' => $payer['electronic_payer_id'] ?? null,
            'plan_type' => $payer['plan_type'] ?? null,
            'website' => $payer['website'] ?? null,
            'notes' => $payer['notes'] ?? null,
        ];
    }

    private function primaryAbMacByState(): array
    {
        $map = [];
        $macs = CmsMac::query()->with('states')->where('mac_type', 'ab_mac')->get();
        foreach ($macs as $mac) {
            foreach ($mac->states as $state) {
                $map[$state->code] = $mac->id;
            }
        }

        return $map;
    }

    private function ownershipForMaContract(string $organizationType): string
    {
        $type = strtolower($organizationType);

        return match (true) {
            str_contains($type, 'demo'),
            str_contains($type, '1833'),
            str_contains($type, 'union') => 'public',
            default => 'private',
        };
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $parts = explode('/', trim($value));
        if (count($parts) !== 3) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', (int) $parts[2], (int) $parts[0], (int) $parts[1]);
    }

    private function intOrNull(?string $value): ?int
    {
        if ($value === null || $value === '' || $value === '*') {
            return null;
        }

        return (int) preg_replace('/[^0-9]/', '', $value);
    }
}
