<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $request->merge([
            'role' => $this->validRole($request),
            'status' => $this->validStatus($request),
            'per_page' => $this->perPage($request),
        ]);

        $users = User::when($request->search, function ($q, $search) {
            $search = '%'.mb_strtolower($search).'%';

            $q->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
            });
        })
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('id');

        return Inertia::render('Users/Index', [
            'users' => $this->paginate($users, $request, 25),
            'filters' => $request->only(['search', 'role', 'status', 'per_page']),
            'filterOptions' => [
                'roles' => ['owner', 'admin', 'manager', 'staff', 'viewer'],
                'statuses' => ['active', 'inactive', 'suspended'],
                'perPage' => [10, 25, 50, 100],
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create');
    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status ?? 'active',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['user' => $user], 201);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status ?? $user->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->expectsJson()) {
            return response()->json(['user' => $user->fresh()]);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User deleted successfully.']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function validRole(Request $request): ?string
    {
        $role = $request->string('role')->toString();

        return in_array($role, ['owner', 'admin', 'manager', 'staff', 'viewer'], true) ? $role : null;
    }

    private function validStatus(Request $request): ?string
    {
        $status = $request->string('status')->toString();

        return in_array($status, ['active', 'inactive', 'suspended'], true) ? $status : null;
    }

    private function perPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }
}
