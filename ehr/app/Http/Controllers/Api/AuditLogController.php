<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->where('organization_id', $this->organizationId())
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json($logs);
    }
}
