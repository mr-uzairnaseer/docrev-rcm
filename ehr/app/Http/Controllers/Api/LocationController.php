<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return LocationResource::collection(
            Location::forOrganization($this->organizationId())
                ->orderBy('name')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $location = Location::create(array_merge(
            $request->validated(),
            ['organization_id' => $this->organizationId()]
        ));

        return (new LocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Location $location): LocationResource
    {
        $this->ensureBelongsToOrganization($location);

        return new LocationResource($location);
    }

    private function ensureBelongsToOrganization(Location $location): void
    {
        if (! $this->belongsToOrganization($location)) {
            abort(404);
        }
    }
}
