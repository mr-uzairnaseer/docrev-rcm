<?php

namespace App\Http\Controllers\Api;

use App\Models\CmsClaimAdjustmentCode;
use App\Models\CmsHcpcsCode;
use App\Models\CmsIcd10Code;
use App\Models\CmsMac;
use App\Models\CmsMedicareAdvantageContract;
use App\Models\CmsModifier;
use App\Models\CmsPlaceOfServiceCode;
use App\Models\CmsQhpIssuer;
use App\Models\CmsReferencePayer;
use App\Models\CmsRemittanceRemarkCode;
use App\Models\CmsRevenueCode;
use App\Models\CmsState;
use App\Models\CmsTaxonomyCode;
use App\Models\CmsTypeOfBillCode;
use App\Services\CmsReference\CmsReferenceImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CmsReferenceController extends ApiController
{
    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => [
                'states' => CmsState::count(),
                'macs' => CmsMac::count(),
                'payers' => CmsReferencePayer::count(),
                'medicare_advantage_contracts' => CmsMedicareAdvantageContract::count(),
                'qhp_issuers' => CmsQhpIssuer::count(),
                'place_of_service_codes' => CmsPlaceOfServiceCode::count(),
                'taxonomy_codes' => CmsTaxonomyCode::count(),
                'hcpcs_codes' => CmsHcpcsCode::count(),
                'icd10_codes' => CmsIcd10Code::count(),
                'modifiers' => CmsModifier::count(),
                'claim_adjustment_codes' => CmsClaimAdjustmentCode::count(),
                'remittance_remark_codes' => CmsRemittanceRemarkCode::count(),
                'type_of_bill_codes' => CmsTypeOfBillCode::count(),
                'revenue_codes' => CmsRevenueCode::count(),
                'datasets' => [
                    'regions', 'states', 'macs', 'payers', 'medicare_advantage', 'qhp_issuers',
                    'pos', 'taxonomy', 'hcpcs', 'icd10', 'modifiers', 'carc', 'rarc',
                    'type_of_bill', 'revenue_codes',
                ],
                'source' => 'CMS public reference data (cms.gov, HL7 POS CodeSystem)',
                'programs' => CmsReferencePayer::query()
                    ->selectRaw('program, ownership, count(*) as total')
                    ->groupBy('program', 'ownership')
                    ->orderBy('program')
                    ->get(),
            ],
        ]);
    }

    public function states(Request $request): JsonResponse
    {
        $query = CmsState::query()->with('region')->withCount('macs')->orderBy('name');

        if ($request->filled('region')) {
            $query->whereHas('region', fn ($q) => $q->where('number', $request->integer('region')));
        }

        if ($request->filled('code')) {
            $query->where('code', strtoupper($request->string('code')));
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 60)),
        ]);
    }

    public function showState(string $code): JsonResponse
    {
        $state = CmsState::query()
            ->with(['region', 'macs', 'payers' => fn ($q) => $q->orderBy('program')->orderBy('name')])
            ->where('code', strtoupper($code))
            ->firstOrFail();

        return response()->json(['data' => $state]);
    }

    public function macs(Request $request): JsonResponse
    {
        $query = CmsMac::query()->with('states')->orderBy('mac_type')->orderBy('jurisdiction_code');

        if ($request->filled('mac_type')) {
            $query->where('mac_type', $request->string('mac_type'));
        }

        if ($request->filled('state')) {
            $state = strtoupper($request->string('state'));
            $query->whereHas('states', fn ($q) => $q->where('code', $state));
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 30)),
        ]);
    }

    public function payers(Request $request): JsonResponse
    {
        $query = CmsReferencePayer::query()
            ->with(['state', 'mac'])
            ->orderBy('program')
            ->orderBy('name');

        if ($request->filled('program')) {
            $query->where('program', $request->string('program'));
        }

        if ($request->filled('ownership')) {
            $query->where('ownership', $request->string('ownership'));
        }

        if ($request->filled('state')) {
            $query->whereHas('state', fn ($q) => $q->where('code', strtoupper($request->string('state'))));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('electronic_payer_id', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function placeOfService(Request $request): JsonResponse
    {
        return response()->json([
            'data' => CmsPlaceOfServiceCode::query()
                ->orderBy('code')
                ->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function taxonomy(Request $request): JsonResponse
    {
        $query = CmsTaxonomyCode::query()->orderBy('code');

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('classification', 'like', $term)
                    ->orWhere('definition', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function medicareAdvantage(Request $request): JsonResponse
    {
        $query = CmsMedicareAdvantageContract::query()->orderBy('contract_number');

        if ($request->filled('offers_part_d')) {
            $query->where('offers_part_d', $request->boolean('offers_part_d'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('contract_number', 'like', $term)
                    ->orWhere('organization_name', 'like', $term)
                    ->orWhere('marketing_name', 'like', $term)
                    ->orWhere('parent_organization', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function hcpcs(Request $request): JsonResponse
    {
        $query = CmsHcpcsCode::query()->orderBy('code');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('long_description', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function qhpIssuers(Request $request): JsonResponse
    {
        $query = CmsQhpIssuer::query()->with('state')->orderBy('issuer_name');

        if ($request->filled('state')) {
            $query->whereHas('state', fn ($q) => $q->where('code', strtoupper($request->string('state'))));
        }

        if ($request->filled('ownership')) {
            $query->where('ownership', $request->string('ownership'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('issuer_name', 'like', $term)
                    ->orWhere('issuer_id', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function icd10(Request $request): JsonResponse
    {
        $query = CmsIcd10Code::query()->orderBy('code');

        if ($request->filled('billable')) {
            $query->where('is_billable', $request->boolean('billable'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function modifiers(Request $request): JsonResponse
    {
        $query = CmsModifier::query()->orderBy('code');

        if ($request->filled('level')) {
            $query->where('level', $request->string('level'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function claimAdjustments(Request $request): JsonResponse
    {
        $query = CmsClaimAdjustmentCode::query()->orderBy('code');

        if ($request->filled('group_code')) {
            $query->where('group_code', strtoupper($request->string('group_code')));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function remittanceRemarks(Request $request): JsonResponse
    {
        $query = CmsRemittanceRemarkCode::query()->orderBy('code');

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function typeOfBill(Request $request): JsonResponse
    {
        $query = CmsTypeOfBillCode::query()->orderBy('code');

        if ($request->filled('facility_type')) {
            $query->where('facility_type', 'like', '%'.$request->string('facility_type').'%');
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function revenueCodes(Request $request): JsonResponse
    {
        $query = CmsRevenueCode::query()->orderBy('code');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        return response()->json([
            'data' => $query->paginate($request->integer('per_page', 100)),
        ]);
    }

    public function import(Request $request, CmsReferenceImporter $importer): JsonResponse
    {
        $fresh = $request->boolean('fresh', true);
        $download = $request->boolean('download', true);
        $only = $request->input('only');
        $only = is_array($only) ? $only : (is_string($only) && $only !== '' ? array_map('trim', explode(',', strtolower($only))) : null);

        $counts = $importer->import($fresh, $download, $only);

        return response()->json([
            'message' => 'CMS reference data imported.',
            'data' => $counts,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $dataset = $request->string('dataset')->toString();
        $limit = min($request->integer('limit', 1000), 5000);
        [$headers, $rows] = $this->exportRows($dataset, $request, $limit);

        $filename = 'cms-'.$dataset.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function exportRows(string $dataset, Request $request, int $limit): array
    {
        return match ($dataset) {
            'payers' => $this->exportPayers($request, $limit),
            'medicare-advantage' => $this->exportMa($request, $limit),
            'qhp' => $this->exportQhp($request, $limit),
            'states' => $this->exportStates($request, $limit),
            'macs' => $this->exportMacs($request, $limit),
            'icd10' => $this->exportIcd10($request, $limit),
            'hcpcs' => $this->exportHcpcs($request, $limit),
            'modifiers' => $this->exportSimple(CmsModifier::query()->orderBy('code'), ['code', 'description', 'level'], $limit),
            'carc' => $this->exportSimple(CmsClaimAdjustmentCode::query()->orderBy('code'), ['code', 'group_code', 'description'], $limit),
            'rarc' => $this->exportSimple(CmsRemittanceRemarkCode::query()->orderBy('code'), ['code', 'description'], $limit),
            'tob' => $this->exportSimple(CmsTypeOfBillCode::query()->orderBy('code'), ['code', 'description', 'facility_type', 'care_type'], $limit),
            'revenue' => $this->exportSimple(CmsRevenueCode::query()->orderBy('code'), ['code', 'description', 'category'], $limit),
            'pos' => $this->exportSimple(CmsPlaceOfServiceCode::query()->orderBy('code'), ['code', 'name', 'definition'], $limit),
            'taxonomy' => $this->exportSimple(CmsTaxonomyCode::query()->orderBy('code'), ['code', 'classification', 'specialization', 'definition'], $limit),
            default => abort(422, 'Unknown CMS dataset for export.'),
        };
    }

    private function exportSimple($query, array $columns, int $limit): array
    {
        $records = $query->limit($limit)->get();

        return [
            $columns,
            $records->map(fn ($row) => array_map(fn ($col) => $row->{$col}, $columns))->all(),
        ];
    }

    private function exportPayers(Request $request, int $limit): array
    {
        $query = CmsReferencePayer::query()->with(['state', 'mac'])->orderBy('program')->orderBy('name');
        if ($request->filled('program')) {
            $query->where('program', $request->string('program'));
        }
        if ($request->filled('ownership')) {
            $query->where('ownership', $request->string('ownership'));
        }
        if ($request->filled('state')) {
            $query->whereHas('state', fn ($q) => $q->where('code', strtoupper($request->string('state'))));
        }
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('code', 'like', $term));
        }

        $records = $query->limit($limit)->get();

        return [
            ['code', 'name', 'program', 'ownership', 'state', 'electronic_payer_id', 'plan_type'],
            $records->map(fn ($p) => [
                $p->code, $p->name, $p->program, $p->ownership,
                $p->state?->code, $p->electronic_payer_id, $p->plan_type,
            ])->all(),
        ];
    }

    private function exportMa(Request $request, int $limit): array
    {
        $query = CmsMedicareAdvantageContract::query()->orderBy('contract_number');
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($q) => $q->where('contract_number', 'like', $term)->orWhere('organization_name', 'like', $term));
        }
        if ($request->boolean('offers_part_d')) {
            $query->where('offers_part_d', true);
        }

        $records = $query->limit($limit)->get();

        return [
            ['contract_number', 'marketing_name', 'organization_name', 'plan_type', 'total_enrollment', 'offers_part_d'],
            $records->map(fn ($c) => [
                $c->contract_number, $c->marketing_name, $c->organization_name,
                $c->plan_type, $c->total_enrollment, $c->offers_part_d ? 'yes' : 'no',
            ])->all(),
        ];
    }

    private function exportQhp(Request $request, int $limit): array
    {
        $query = CmsQhpIssuer::query()->with('state')->orderBy('issuer_name');
        if ($request->filled('state')) {
            $query->whereHas('state', fn ($q) => $q->where('code', strtoupper($request->string('state'))));
        }
        $records = $query->limit($limit)->get();

        return [
            ['issuer_id', 'issuer_name', 'state', 'market_type', 'ownership'],
            $records->map(fn ($i) => [
                $i->issuer_id, $i->issuer_name, $i->state?->code, $i->market_type, $i->ownership,
            ])->all(),
        ];
    }

    private function exportStates(Request $request, int $limit): array
    {
        $records = CmsState::query()->with('region')->orderBy('name')->limit($limit)->get();

        return [
            ['code', 'name', 'region', 'jurisdiction_type', 'fips_code'],
            $records->map(fn ($s) => [
                $s->code, $s->name, $s->region?->name, $s->jurisdiction_type, $s->fips_code,
            ])->all(),
        ];
    }

    private function exportMacs(Request $request, int $limit): array
    {
        $query = CmsMac::query()->with('states')->orderBy('jurisdiction_code');
        if ($request->filled('mac_type')) {
            $query->where('mac_type', $request->string('mac_type'));
        }
        $records = $query->limit($limit)->get();

        return [
            ['contract_number', 'name', 'mac_type', 'jurisdiction_code', 'states', 'phone'],
            $records->map(fn ($m) => [
                $m->contract_number, $m->name, $m->mac_type, $m->jurisdiction_code,
                $m->states->pluck('code')->join(', '), $m->phone,
            ])->all(),
        ];
    }

    private function exportIcd10(Request $request, int $limit): array
    {
        $query = CmsIcd10Code::query()->orderBy('code');
        if ($request->filled('billable')) {
            $query->where('is_billable', $request->boolean('billable'));
        }
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($q) => $q->where('code', 'like', $term)->orWhere('description', 'like', $term));
        }
        $records = $query->limit($limit)->get();

        return [
            ['code', 'description', 'is_billable'],
            $records->map(fn ($c) => [$c->code, $c->description, $c->is_billable ? 'yes' : 'no'])->all(),
        ];
    }

    private function exportHcpcs(Request $request, int $limit): array
    {
        $query = CmsHcpcsCode::query()->orderBy('code');
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($q) => $q->where('code', 'like', $term)->orWhere('short_description', 'like', $term));
        }
        $records = $query->limit($limit)->get();

        return [
            ['code', 'short_description', 'category', 'long_description'],
            $records->map(fn ($h) => [$h->code, $h->short_description, $h->category, $h->long_description])->all(),
        ];
    }
}
