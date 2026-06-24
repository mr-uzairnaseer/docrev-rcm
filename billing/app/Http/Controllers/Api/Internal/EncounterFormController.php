<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Services\Billing\ClaimFormBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EncounterFormController extends Controller
{
    public function __invoke(Request $request, string $encounterUuid, ClaimFormBuilder $builder): JsonResponse
    {
        $validated = $request->validate([
            'form' => ['nullable', 'string', 'in:hcfa,ub04,HCFA,UB04,cms-1500,cms-1450'],
        ]);

        $formType = $validated['form'] ?? $request->query('form', 'hcfa');

        try {
            $form = $builder->buildForEncounterUuid($encounterUuid, $formType);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $form]);
    }
}
