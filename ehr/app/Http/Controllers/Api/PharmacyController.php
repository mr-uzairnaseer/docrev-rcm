<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PharmacyResource;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PharmacyController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return PharmacyResource::collection(
            Pharmacy::forOrganization($this->organizationId())
                ->where('is_active', true)
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }
}
