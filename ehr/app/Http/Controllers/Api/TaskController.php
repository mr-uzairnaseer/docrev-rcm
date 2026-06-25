<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::query()
            ->where('organization_id', $this->organizationId())
            ->when($status = $request->query('status'), fn ($q) => $q->where('status', $status))
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,completed'],
            'priority' => ['nullable', 'string', 'in:low,normal,high'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = Task::create(array_merge($validated, [
            'organization_id' => $this->organizationId(),
        ]));

        return response()->json($task, 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        if (! $this->belongsToOrganization($task)) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,completed'],
            'user_id' => ['nullable', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'in:low,normal,high'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        if (! $this->belongsToOrganization($task)) {
            abort(403);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully.']);
    }
}
