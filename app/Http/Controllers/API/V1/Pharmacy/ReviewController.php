<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\ReplyToReviewRequest;
use App\Http\Resources\API\V1\Pharmacy\ReviewResource;
use App\Models\Pharmacy;
use App\Models\PharmacyReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reviews & Reputation Management (FR-PH-7).
 *
 * Allows pharmacists to monitor patient reviews and ratings,
 * reply to comments, and view their aggregate score.
 */
class ReviewController extends Controller
{
    /**
     * List pharmacy reviews (FR-PH-7.1, FR-PH-7.3).
     *
     * Returns all patient reviews for the pharmacy with star ratings,
     * comments, submission dates, and any pharmacist replies.
     * Results are paginated and ordered by most recent.
     * Accessible by owner or staff with `pharmacy_manage` permission.
     */
    public function index(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $reviews = $pharmacy->pharmacyReviews()
            ->with('patient.user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => $pharmacy->pharmacyReviews()->avg('rating'),
            ],
        ]);
    }

    /**
     * Reply to a review (FR-PH-7.2).
     *
     * Allows the pharmacist to respond to a patient's review publicly.
     * The reply is stored on the review record.
     * Requires `pharmacy_manage` permission.
     */
    public function reply(ReplyToReviewRequest $request, Pharmacy $pharmacy, PharmacyReview $review): JsonResponse
    {
        if ($review->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Review not found for this pharmacy.'], 404);
        }

        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();

        $review->update([
            'pharmacist_reply' => $validated['comment'],
        ]);

        $review->load('patient.user');

        return response()->json([
            'message' => 'Reply submitted successfully.',
            'data' => new ReviewResource($review),
        ]);
    }
}
