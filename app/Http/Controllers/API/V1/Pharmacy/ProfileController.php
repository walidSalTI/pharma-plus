<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\StorePharmacyRequest;
use App\Http\Requests\API\V1\Pharmacy\UpdateProfileRequest;
use App\Http\Requests\API\V1\Pharmacy\UpdateUserProfileRequest;
use App\Http\Resources\API\V1\Pharmacy\PharmacistProfileResource;
use App\Http\Resources\API\V1\Pharmacy\PharmacyResource;
use App\Models\Pharmacy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pharmacy Profile & Settings (FR-PH-6).
 *
 * Manages the consumer-facing pharmacy profile including name, address,
 * coordinates, contact details, and storefront imagery. Also handles
 * temporary vacation/closure declarations.
 */
class ProfileController extends Controller
{
    /**
     * Get the pharmacy profile (FR-PH-6.1).
     *
     * Returns the full pharmacy profile including operating hours,
     * average rating, and review count. Accessible by owner or staff
     * with `pharmacy_manage` permission.
     */
    public function show(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('viewDashboard', $pharmacy);

        $pharmacy->load('pharmacyOperatingHours')
            ->loadAvg('pharmacyReviews', 'rating')
            ->loadCount([
                'pharmacyReviews',
                'staffPharmacists',
                'medicationOrders as pending_orders_count' => fn ($q) => $q->where('status', 'pending'),
                'pharmacyInventories as low_stock_count' => fn ($q) => $q->whereColumn('stock', '<=', 'min_stock'),
            ]);

        $pharmacy->total_stock = $pharmacy->pharmacyInventories()
            ->sum('stock');

        return response()->json([
            'data' => new PharmacyResource($pharmacy),
        ]);
    }

    /**
     * Update the pharmacy profile (FR-PH-6.1).
     *
     * Updates store name, address, GPS coordinates, support contacts,
     * and/or storefront image. Partial updates are supported — only
     * provided fields are modified. Requires `pharmacy_manage` permission.
     */
    public function update(UpdateProfileRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['address'])) {
            $updateData['address'] = $validated['address'];
        }
        if (isset($validated['latitude'])) {
            $updateData['latitude'] = $validated['latitude'];
        }
        if (isset($validated['longitude'])) {
            $updateData['longitude'] = $validated['longitude'];
        }
        if (isset($validated['support_email'])) {
            $updateData['support_email'] = $validated['support_email'];
        }
        if (isset($validated['support_number'])) {
            $updateData['support_number'] = $validated['support_number'];
        }

        if ($request->hasFile('front_image')) {
            if ($pharmacy->front_image) {
                Storage::disk('public')->delete($pharmacy->front_image);
            }
            $updateData['front_image'] = $request->file('front_image')->store('pharmacy_images', 'public');
        }

        $pharmacy->update($updateData);

        $pharmacy->load('pharmacyOperatingHours');
        $pharmacy->loadAvg('pharmacyReviews', 'rating');
        $pharmacy->loadCount('pharmacyReviews');

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new PharmacyResource($pharmacy),
        ]);
    }

    /**
     * Get a single pharmacy's full details (when selected from sidebar).
     *
     * Returns the full pharmacy profile with operating hours, ratings,
     * and inventory/order counts. Authorization checks via viewDashboard
     * — the pharmacist must own or be staff at this pharmacy.
     */
    public function showPharmacy(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('viewDashboard', $pharmacy);

        $pharmacy->load('pharmacyOperatingHours')
            ->loadCount('pharmacyReviews')
            ->loadAvg('pharmacyReviews', 'rating');

        $pharmacy->pending_orders_count = $pharmacy->medicationOrders()
            ->where('status', 'pending')
            ->count();

        $pharmacy->low_stock_count = $pharmacy->pharmacyInventories()
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $pharmacy->total_stock = $pharmacy->pharmacyInventories()
            ->sum('stock');

        return response()->json([
            'data' => new PharmacyResource($pharmacy),
        ]);
    }

    /**
     * Search pharmacies by name (FR-PH-6.1).
     *
     * Returns all pharmacies whose name matches the given query string.
     * Accessible by any authenticated pharmacist. Not scoped to a single
     * pharmacy — searches across the entire system.
     */
    public function searchPharmacies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $pharmacies = Pharmacy::where('name', 'like', '%'.$validated['query'].'%')
            ->orderBy('name')
            ->get();

        return response()->json(PharmacyResource::collection($pharmacies)->response()->getData(true));
    }

    /**
     * Create a new pharmacy (FR-PH-6.1).
     *
     * Creates a new pharmacy linked to the authenticated pharmacist.
     * Requires the pharmacist to be verified (status = 'approved').
     */
    public function storePharmacy(StorePharmacyRequest $request): JsonResponse
    {

        $pharmacist = $request->user()->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Pharmacist profile not found.'], 404);
        }

        if (! $pharmacist->isVerified()) {
            return response()->json(['message' => 'Only verified pharmacists can create a pharmacy.'], 403);
        }

        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('front_image')) {
            $imagePath = $request->file('front_image')->store('pharmacy_images', 'public');
        }

        $pharmacy = Pharmacy::create([
            'pharmacist_id' => $pharmacist->id,
            'name' => $validated['name'],
            'address' => $validated['address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'support_email' => $validated['support_email'] ?? null,
            'support_number' => $validated['support_number'] ?? null,
            'front_image' => $imagePath,
        ]);

        return response()->json([
            'message' => 'Pharmacy created successfully.',
            'data' => new PharmacyResource($pharmacy),
        ], 201);
    }

    /**
     * Get the pharmacist's personal profile.
     *
     * Returns the authenticated pharmacist's user information
     * along with their pharmacist-specific details (verification
     * status, syndicate card).
     */
    public function showProfile(Request $request): JsonResponse
    {
        $user = $request->user()->load('pharmacist');

        return response()->json([
            'data' => new PharmacistProfileResource($user),
        ]);
    }

    /**
     * Update the pharmacist's personal user profile.
     *
     * Updates the authenticated user's own profile information
     * (name, email, phone, age, gender, location).
     */
    public function updateProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        $updateData = [];
        foreach (['f_name', 'l_name', 'email', 'phone_number', 'age', 'gender', 'location'] as $field) {
            if (isset($validated[$field])) {
                $updateData[$field] = $validated[$field];
            }
        }

        if ($updateData !== []) {
            $user->update($updateData);
        }

        return response()->json([
            'message' => 'User profile updated successfully.',
            'data' => [
                'id' => $user->id,
                'f_name' => $user->f_name,
                'l_name' => $user->l_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'age' => $user->age,
                'gender' => $user->gender,
                'location' => $user->location,
            ],
        ]);
    }
}
