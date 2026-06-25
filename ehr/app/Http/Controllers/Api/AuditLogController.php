<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->where('organization_id', $this->organizationId());

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($event = $request->input('event')) {
            $query->where('event', 'like', "%{$event}%");
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json($logs);
    }
}
