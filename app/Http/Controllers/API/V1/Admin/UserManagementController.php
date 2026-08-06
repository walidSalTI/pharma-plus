<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\UpdateUserRequest;
use App\Http\Resources\API\V1\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with(['doctor', 'pharmacist', 'patient', 'specialist', 'scientificRep', 'pharmaceuticalCompany'])
            ->when($request->filled('role'), fn ($q) => $q->role($request->input('role')))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('email', 'like', '%'.$request->input('search').'%')
                    ->orWhere('f_name', 'like', '%'.$request->input('search').'%')
                    ->orWhere('l_name', 'like', '%'.$request->input('search').'%');
            }))
            ->when($request->filled('trashed'), fn ($q) => $q->onlyTrashed())
            ->when($request->filled('created_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('created_from')))
            ->when($request->filled('created_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('created_to')))
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->load(['doctor', 'pharmacist', 'patient', 'specialist', 'scientificRep', 'pharmaceuticalCompany'])),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'f_name' => ['required', 'string', 'max:255'],
            'l_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:50'],
            'age' => ['required', 'integer', 'min:1', 'max:150'],
            'gender' => ['required', 'string', 'in:male,female'],
            'location' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $password = Str::password(12);

        $user = User::create([
            'f_name' => $validated['f_name'],
            'l_name' => $validated['l_name'],
            'email' => $validated['email'],
            'password' => Hash::make($password),
            'phone_number' => $validated['phone_number'],
            'age' => $validated['age'],
            'gender' => $validated['gender'],
            'location' => $validated['location'] ?? null,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => [
                'user' => new UserResource($user),
                'generated_password' => $password,
            ],
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function restore(string $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['message' => 'User restored successfully.']);
    }

    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->syncRoles($validated['roles']);

        return response()->json([
            'message' => 'Roles assigned successfully.',
            'data' => [
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    public function suspend(User $user): JsonResponse
    {
        $user->tokens()->delete();

        return response()->json(['message' => 'User suspended successfully.']);
    }
}
