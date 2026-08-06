<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Specialist;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\ApproveProposalRequest;
use App\Http\Requests\API\V1\Specialist\ReviewProposalRequest;
use App\Http\Resources\API\V1\Specialist\ProposalResource;
use App\Models\Medication;
use App\Models\MedicationIngredient;
use App\Models\MedicationProposal;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProposalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $proposals = MedicationProposal::with('pharmacist.user')
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => ProposalResource::collection($proposals),
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'per_page' => $proposals->perPage(),
                'total' => $proposals->total(),
            ],
        ]);
    }

    public function show(MedicationProposal $proposal): JsonResponse
    {
        return response()->json([
            'data' => new ProposalResource($proposal->load(['pharmacist.user', 'specialist.user'])),
        ]);
    }

    public function approve(ApproveProposalRequest $request, MedicationProposal $proposal): JsonResponse
    {
        $specialist = $request->user()->specialist;

        if ($proposal->status !== 'pending') {
            return response()->json(['message' => 'Proposal has already been reviewed.'], 422);
        }

        $validated = $request->validated();
        $activeIngredients = $validated['active_ingredients'];
        unset($validated['active_ingredients']);

        DB::transaction(function () use ($proposal, $specialist, $validated, $activeIngredients): void {
            $proposal->update([
                'status' => 'accepted',
                'specialist_id' => $specialist->id,
            ]);

            $product = Product::create([
                'name' => $validated['trade_name'],
                'barcode' => $validated['barcode'] ?? null,
                'image' => $validated['image'] ?? null,
                'type' => 'medication',
            ]);

            $medication = Medication::create([
                'product_id' => $product->id,
                'form' => $validated['form'] ?? null,
                'arabic_form' => $validated['arabic_form'] ?? null,
                'manufacture_id' => $validated['manufacture_id'] ?? null,
                'usage_id' => $validated['usage_id'] ?? null,
                'status' => 'accepted',
            ]);

            foreach ($activeIngredients as $ingredient) {
                MedicationIngredient::create([
                    'medication_id' => $medication->id,
                    'active_ingredient_id' => $ingredient['active_ingredient_id'],
                    'active_ratio' => $ingredient['active_ratio'] ?? null,
                ]);
            }
        });

        return response()->json([
            'message' => 'Proposal approved and medication added to catalog.',
            'data' => new ProposalResource($proposal->fresh()->load(['pharmacist.user', 'specialist.user'])),
        ]);
    }

    public function reject(ReviewProposalRequest $request, MedicationProposal $proposal): JsonResponse
    {
        $specialist = $request->user()->specialist;

        if ($proposal->status !== 'pending') {
            return response()->json(['message' => 'Proposal has already been reviewed.'], 422);
        }

        $proposal->update([
            'status' => 'rejected',
            'specialist_id' => $specialist->id,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'message' => 'Proposal rejected.',
            'data' => new ProposalResource($proposal->fresh()->load(['pharmacist.user', 'specialist.user'])),
        ]);
    }
}
