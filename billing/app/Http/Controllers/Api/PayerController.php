<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PayerResource;
use App\Models\Payer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayerController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return PayerResource::collection(
            Payer::forOrganization($this->organizationId())
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }
}
