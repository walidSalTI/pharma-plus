<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Pharmacist;
use App\Models\Pharmacy;
use App\Notifications\JoinRequestNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Staff Invitations & Join Requests (FR-PH-6.3).
 *
 * Handles notifications for pharmacist staff invitations
 * and pharmacy join requests between owners and pharmacists.
 */
class NotificationController extends Controller
{
    /**
     * List notifications (FR-PH-6.3).
     *
     * Returns all unread notifications for the authenticated user.
     * Pharmacists see both staff invitations and join request responses.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (DatabaseNotification $notification): array {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? null,
                    'data' => $data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });

        return response()->json([
            'data' => $notifications,
        ]);
    }

    /**
     * Accept a staff invitation (FR-PH-6.3).
     *
     * Pharmacist accepts invitation to join pharmacy staff.
     * Creates the pivot record with the permissions from the invitation.
     */
    public function acceptStaffInvitation(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $pharmacist = $user->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Only pharmacists can accept staff invitations.'], 403);
        }

        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $data = $notification->data;

        if (($data['type'] ?? null) !== 'staff_invitation') {
            return response()->json(['message' => 'Invalid notification type.'], 400);
        }

        if ($notification->read_at) {
            return response()->json(['message' => 'This invitation has already been processed.'], 400);
        }

        $pharmacy = Pharmacy::find($data['pharmacy_id']);

        if (! $pharmacy) {
            return response()->json(['message' => 'Pharmacy not found.'], 404);
        }

        if ($pharmacy->pharmacist_id === $pharmacist->id) {
            return response()->json(['message' => 'You are the owner of this pharmacy.'], 400);
        }

        $alreadyStaff = $pharmacist->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->exists();

        if ($alreadyStaff) {
            $notification->markAsRead();

            return response()->json(['message' => 'You are already a staff member of this pharmacy.']);
        }

        $permissions = $data['permissions'] ?? [];

        $pivotData = array_intersect_key($permissions, array_flip([
            'pharmacy_manage', 'inventory_manage', 'operating_hours_manage',
            'orders_process', 'orders_view_own',
        ]));

        $pharmacist->staffPharmacies()->attach($pharmacy->id, $pivotData);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Staff invitation accepted successfully.',
        ]);
    }

    /**
     * Reject a staff invitation (FR-PH-6.3).
     *
     * Pharmacist rejects invitation to join pharmacy staff.
     * Marks notification as read.
     */
    public function rejectStaffInvitation(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $pharmacist = $user->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Only pharmacists can reject staff invitations.'], 403);
        }

        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $data = $notification->data;

        if (($data['type'] ?? null) !== 'staff_invitation') {
            return response()->json(['message' => 'Invalid notification type.'], 400);
        }

        if ($notification->read_at) {
            return response()->json(['message' => 'This invitation has already been processed.'], 400);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Staff invitation rejected.',
        ]);
    }

    /**
     * Send a join request to pharmacy owner (FR-PH-6.3).
     *
     * A pharmacist can request to join a pharmacy's staff.
     * Sends a notification to the pharmacy owner.
     */
    public function sendJoinRequest(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $pharmacist = $request->user()->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Only pharmacists can send join requests.'], 403);
        }

        if ($pharmacy->pharmacist_id === $pharmacist->id) {
            return response()->json(['message' => 'You are the owner of this pharmacy.'], 400);
        }

        $alreadyStaff = $pharmacist->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->exists();

        if ($alreadyStaff) {
            return response()->json(['message' => 'You are already a staff member of this pharmacy.'], 400);
        }

        $owner = $pharmacy->pharmacist;

        if (! $owner || ! $owner->user) {
            return response()->json(['message' => 'Pharmacy owner not found.'], 404);
        }

        $owner->user->notify(new JoinRequestNotification($pharmacy, $pharmacist));

        return response()->json([
            'message' => 'Join request sent successfully.',
        ]);
    }

    /**
     * Accept a join request (FR-PH-6.3).
     *
     * Pharmacy owner accepts a pharmacist's request to join.
     * Creates the pivot record with the specified permissions.
     */
    public function acceptJoinRequest(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $pharmacist = $user->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Only pharmacists can accept join requests.'], 403);
        }

        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $data = $notification->data;

        if (($data['type'] ?? null) !== 'join_request') {
            return response()->json(['message' => 'Invalid notification type.'], 400);
        }

        if ($notification->read_at) {
            return response()->json(['message' => 'This request has already been processed.'], 400);
        }

        $pharmacy = Pharmacy::find($data['pharmacy_id']);

        if (! $pharmacy) {
            return response()->json(['message' => 'Pharmacy not found.'], 404);
        }

        if ($pharmacy->pharmacist_id !== $pharmacist->id) {
            return response()->json(['message' => 'You are not the owner of this pharmacy.'], 403);
        }

        $requester = Pharmacist::find($data['requester_id']);

        if (! $requester) {
            return response()->json(['message' => 'Requester pharmacist not found.'], 404);
        }

        $alreadyStaff = $requester->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->exists();

        if ($alreadyStaff) {
            $notification->markAsRead();

            return response()->json(['message' => 'This pharmacist is already a staff member.']);
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.pharmacy_manage' => ['nullable', 'boolean'],
            'permissions.inventory_manage' => ['nullable', 'boolean'],
            'permissions.operating_hours_manage' => ['nullable', 'boolean'],
            'permissions.orders_process' => ['nullable', 'boolean'],
            'permissions.orders_view_own' => ['nullable', 'boolean'],
        ]);

        $pivotData = isset($validated['permissions'])
            ? array_intersect_key($validated['permissions'], array_flip([
                'pharmacy_manage', 'inventory_manage', 'operating_hours_manage',
                'orders_process', 'orders_view_own',
            ]))
            : [];

        $requester->staffPharmacies()->attach($pharmacy->id, $pivotData);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Join request accepted successfully.',
        ]);
    }

    /**
     * Reject a join request (FR-PH-6.3).
     *
     * Pharmacy owner rejects a pharmacist's request to join.
     * Marks notification as read.
     */
    public function rejectJoinRequest(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $pharmacist = $user->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Only pharmacists can reject join requests.'], 403);
        }

        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $data = $notification->data;

        if (($data['type'] ?? null) !== 'join_request') {
            return response()->json(['message' => 'Invalid notification type.'], 400);
        }

        if ($notification->read_at) {
            return response()->json(['message' => 'This request has already been processed.'], 400);
        }

        $pharmacy = Pharmacy::find($data['pharmacy_id']);

        if (! $pharmacy) {
            return response()->json(['message' => 'Pharmacy not found.'], 404);
        }

        if ($pharmacy->pharmacist_id !== $pharmacist->id) {
            return response()->json(['message' => 'You are not the owner of this pharmacy.'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Join request rejected.',
        ]);
    }
}
