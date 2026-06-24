<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiController extends Controller
{
    protected function organizationId(): int
    {
        $user = auth()->user();

        if (! $user || ! $user->organization_id) {
            throw new HttpResponseException(response()->json([
                'message' => 'User is not assigned to an organization.',
            ], 403));
        }

        return (int) $user->organization_id;
    }

    protected function belongsToOrganization(object $model): bool
    {
        return (int) $model->organization_id === $this->organizationId();
    }
}
