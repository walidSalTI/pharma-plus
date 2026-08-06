<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Pharmacy\StaffResource;
use App\Models\Pharmacist;
use App\Models\Pharmacy;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Delegate Staff Access Control (FR-PH-6.3).
 *
 * Allows the primary pharmacist to configure localized sub-accounts
 * for assistant pharmacists with restricted execution permissions.
 * Staff accounts have independent login credentials and limited
 * capabilities (e.g., inventory updates only).
 *
 * New Flow:
 * 1. Owner searches for a pharmacist by ID or email (search)
 * 2a. If not found → owner creates a new account + auto-assigns (store)
 * 2b. If found → owner sends an invitation notification (invite)
 * 3. Pharmacist can also send a join request to the owner
 *    (NotificationController::sendJoinRequest)
 */
class StaffController extends Controller
{
    /**
     * List staff members (FR-PH-6.3).
     *
     * Returns all assistant pharmacists linked to the given pharmacy.
     * Requires `pharmacy_manage` permission.
     */
    public function index(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $pharmacist = $request->user()->pharmacist;

        $staff = Pharmacist::whereHas('staffPharmacies', function ($q) use ($pharmacy) {
            $q->where('pharmacies.id', $pharmacy->id);
        })
            ->with(['user', 'staffPharmacies' => fn ($q) => $q->where('pharmacies.id', $pharmacy->id)])
            ->where('id', '!=', $pharmacist->id)
            ->get();

        return response()->json([
            'data' => StaffResource::collection($staff),
        ]);
    }

    /**
     * Search for a pharmacist by ID or email (FR-PH-6.3).
     *
     * Searches for an existing pharmacist record. If found, returns
     * their info so the owner can invite them. If not found, returns
     * `found: false` so the owner can create a new account instead.
     * Requires `pharmacy_manage` permission.
     */
    public function search(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        $query = $validated['query'];

        $pharmacist = Pharmacist::where('id', $query)
            ->orWhereHas('user', function ($q) use ($query) {
                $q->where('email', $query)
                    ->orWhere('f_name', 'LIKE', "%{$query}%")
                    ->orWhere('l_name', 'LIKE', "%{$query}%")
                    ->orWhereRaw("CONCAT(f_name, ' ', l_name) LIKE ?", ["%{$query}%"]);
            })
            ->with('user')
            ->first();

        if (! $pharmacist) {
            return response()->json([
                'found' => false,
                'message' => 'No pharmacist found with the given ID, email, or name.',
            ]);
        }

        $currentPharmacist = $request->user()->pharmacist;

        $alreadyStaff = $pharmacist->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->exists();

        $isOwner = $pharmacy->pharmacist_id === $pharmacist->id;
        $isSelf = $pharmacist->id === $currentPharmacist->id;

        return response()->json([
            'found' => true,
            'pharmacist' => [
                'id' => $pharmacist->id,
                'user_id' => $pharmacist->user_id,
                'name' => ($pharmacist->user?->f_name ?? '').' '.($pharmacist->user?->l_name ?? ''),
                'email' => $pharmacist->user?->email,
                'phone_number' => $pharmacist->user?->phone_number,
            ],
            'already_staff' => $alreadyStaff,
            'is_owner' => $isOwner,
            'is_self' => $isSelf,
        ]);
    }

    /**
     * Invite an existing pharmacist to join staff (FR-PH-6.3).
     *
     * Sends a StaffInvitation notification to the target pharmacist.
     * The pharmacist can then accept or reject via their notifications.
     * Requires `pharmacy_manage` permission.
     */
    public function invite(Request $request, Pharmacy $pharmacy, Pharmacist $targetPharmacist): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $inviter = $request->user()->pharmacist;

        if ($targetPharmacist->id === $inviter->id) {
            return response()->json(['message' => 'Cannot invite yourself.'], 400);
        }

        if ($pharmacy->pharmacist_id === $targetPharmacist->id) {
            return response()->json(['message' => 'This pharmacist is the owner of the pharmacy.'], 400);
        }

        $alreadyStaff = $targetPharmacist->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->exists();

        if ($alreadyStaff) {
            return response()->json(['message' => 'This pharmacist is already a staff member.'], 400);
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.pharmacy_manage' => ['nullable', 'boolean'],
            'permissions.inventory_manage' => ['nullable', 'boolean'],
            'permissions.operating_hours_manage' => ['nullable', 'boolean'],
            'permissions.orders_process' => ['nullable', 'boolean'],
            'permissions.orders_view_own' => ['nullable', 'boolean'],
        ]);

        $permissions = $validated['permissions'] ?? [];

        $targetUser = $targetPharmacist->user;

        if (! $targetUser) {
            return response()->json(['message' => 'Target pharmacist user not found.'], 404);
        }

        $targetUser->notify(new StaffInvitationNotification(
            pharmacy: $pharmacy,
            inviter: $inviter,
            permissions: $permissions,
        ));

        return response()->json([
            'message' => 'Staff invitation sent successfully.',
        ]);
    }

    /**
     * Create a staff account and auto-assign (FR-PH-6.3).
     *
     * Creates a new User + Pharmacist record and links them to the
     * given pharmacy as staff. The new staff member receives
     * independent login credentials. This is used when the searched
     * pharmacist does not yet have an account in the system.
     * Auto-assigned without requiring acceptance.
     * Requires `pharmacy_manage` permission.
     */
    public function store(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validate([
            'f_name' => ['required', 'string', 'max:255'],
            'l_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone_number' => ['required', 'string', 'max:20'],
            'permissions' => ['nullable', 'array'],
            'permissions.pharmacy_manage' => ['nullable', 'boolean'],
            'permissions.inventory_manage' => ['nullable', 'boolean'],
            'permissions.operating_hours_manage' => ['nullable', 'boolean'],
            'permissions.orders_process' => ['nullable', 'boolean'],
            'permissions.orders_view_own' => ['nullable', 'boolean'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = User::create([
            'f_name' => $validated['f_name'],
            'l_name' => $validated['l_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone_number' => $validated['phone_number'],
            'age' => 0,
            'gender' => 'male',
            'location' => '',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('pharmacist');

        $staff = Pharmacist::create([
            'user_id' => $user->id,
            'syndicate_card' => null,
        ]);

        $pivotData = [];
        if (isset($validated['permissions'])) {
            $pivotData = array_intersect_key($validated['permissions'], array_flip([
                'pharmacy_manage',
                'inventory_manage',
                'operating_hours_manage',
                'orders_process',
                'orders_view_own',
            ]));
        }

        if (isset($validated['salary'])) {
            $pivotData['salary'] = $validated['salary'];
        }

        $staff->staffPharmacies()->attach($pharmacy->id, $pivotData);

        // 2. تحميل العلاقات الأساسية
        $staff->load(['user', 'staffPharmacies']);

        // 3. استخراج كائن الـ Pivot وضبطه مباشرة على كائن الـ staff لتجنب الـ MissingAttributeException
        $currentPharmacyRelation = $staff->staffPharmacies->firstWhere('id', $pharmacy->id);
        if ($currentPharmacyRelation && $currentPharmacyRelation->pivot) {
            $staff->setRelation('pivot', $currentPharmacyRelation->pivot);
        }

        // فك التعليق عن الـ Resource ليعمل بداخلها الفحص الآمن بنجاح
        return response()->json([
            'message' => 'Staff account created and assigned successfully.',
            'data' => new StaffResource($staff),
        ], 201);
    }

    /**
     * Update a staff member (FR-PH-6.3).
     *
     * Updates the staff member's profile information and permissions
     * for the given pharmacy. The authenticated pharmacist cannot
     * edit themselves. Requires `pharmacy_manage` permission.
     */
    public function update(Request $request, Pharmacy $pharmacy, Pharmacist $staff): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $pharmacist = $request->user()->pharmacist;

        if ($staff->id === $pharmacist->id) {
            return response()->json(['message' => 'Cannot edit your own account through staff management.'], 403);
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.pharmacy_manage' => ['nullable', 'boolean'],
            'permissions.inventory_manage' => ['nullable', 'boolean'],
            'permissions.operating_hours_manage' => ['nullable', 'boolean'],
            'permissions.orders_process' => ['nullable', 'boolean'],
            'permissions.orders_view_own' => ['nullable', 'boolean'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $pivotData = [];
        if (isset($validated['permissions'])) {
            $pivotData = array_intersect_key($validated['permissions'], array_flip([
                'pharmacy_manage',
                'inventory_manage',
                'operating_hours_manage',
                'orders_process',
                'orders_view_own',
            ]));
        }

        if (isset($validated['salary'])) {
            $pivotData['salary'] = $validated['salary'];
        }

        if ($pivotData !== []) {
            $staff->staffPharmacies()->updateExistingPivot($pharmacy->id, $pivotData);
        }

        $staff->load(['user', 'staffPharmacies' => fn ($q) => $q->where('pharmacies.id', $pharmacy->id)]);

        return response()->json([
            'message' => 'Staff account updated successfully.',
            'data' => new StaffResource($staff),
        ]);
    }

    /**
     * Get the authenticated pharmacist's permissions for a pharmacy.
     *
     * Three cases:
     * 1. Owner of the pharmacy → all permissions return true
     * 2. Staff member → returns their specific pivot permissions
     * 3. Neither owner nor staff → 403 Access Denied
     */
    public function getPermissions(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('viewDashboard', $pharmacy);

        $pharmacist = $request->user()->pharmacist;

        if ($pharmacy->pharmacist_id === $pharmacist->id) {
            return response()->json([
                'data' => [
                    'role' => 'owner',
                    'permissions' => [
                        'pharmacy_manage' => true,
                        'inventory_manage' => true,
                        'operating_hours_manage' => true,
                        'orders_process' => true,
                        'orders_view_own' => true,
                    ],
                    'salary' => 0,
                ],
            ]);
        }

        $staffPivot = $pharmacist->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->first()
            ?->pivot;

        return response()->json([
            'data' => [
                'role' => 'staff',
                'permissions' => [
                    'pharmacy_manage' => $staffPivot?->pharmacy_manage ?? false,
                    'inventory_manage' => $staffPivot?->inventory_manage ?? false,
                    'operating_hours_manage' => $staffPivot?->operating_hours_manage ?? false,
                    'orders_process' => $staffPivot?->orders_process ?? false,
                    'orders_view_own' => $staffPivot?->orders_view_own ?? false,
                ],
                'salary' => (float) ($staffPivot?->salary ?? 0),
            ],
        ]);
    }

    /**
     * Remove a staff member (FR-PH-6.3).
     *
     * Deletes the staff pharmacist record and their associated user
     * account, immediately revoking system access.
     * Requires `pharmacy_manage` permission.
     */
    public function destroy(Request $request, Pharmacy $pharmacy, Pharmacist $staff): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $pharmacist = $request->user()->pharmacist;

        if ($staff->id === $pharmacist->id) {
            return response()->json(['message' => 'Cannot remove yourself from staff.'], 403);
        }

        if ($pharmacy->pharmacist_id === $staff->id) {
            return response()->json(['message' => 'Cannot remove the pharmacy owner.'], 400);
        }

        $isStaff = $staff->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->exists();

        if (! $isStaff) {
            return response()->json(['message' => 'This pharmacist is not a staff member of this pharmacy.'], 400);
        }

        $staff->staffPharmacies()->detach($pharmacy->id);

        return response()->json(['message' => 'Staff member removed from pharmacy successfully.']);
    }
}
