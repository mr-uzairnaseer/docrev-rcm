<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProviderController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProviderResource::collection(
            Provider::forOrganization($this->organizationId())
                ->orderBy('last_name')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(StoreProviderRequest $request): JsonResponse
    {
        $provider = Provider::create(array_merge(
            $request->validated(),
            ['organization_id' => $this->organizationId()]
        ));

        return (new ProviderResource($provider))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Provider $provider): ProviderResource
    {
        $this->ensureBelongsToOrganization($provider);

        return new ProviderResource($provider);
    }

    private function ensureBelongsToOrganization(Provider $provider): void
    {
        if (! $this->belongsToOrganization($provider)) {
            abort(404);
        }
    }
}
