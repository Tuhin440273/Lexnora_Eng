<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Exceptions\CannotChangeUser;
use App\Modules\Admin\Services\UserAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserAdministrationService $users) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(UserAdministrationService::ROLES)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->with('roles:id,name')
            ->when($filters['search'] ?? null, function ($q, string $search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $q->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($filters['role'] ?? null, fn ($q, string $role) => $q->role($role))
            ->when($filters['status'] ?? null, fn ($q, string $status) => $status === 'inactive'
                ? $q->whereNotNull('deactivated_at')
                : $q->whereNull('deactivated_at'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'verified' => $user->email_verified_at !== null,
                'role' => $user->roles->first()?->name,
                'is_active' => ! $user->isDeactivated(),
                'is_me' => $user->is($request->user()),
                'joined_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'roles' => UserAdministrationService::ROLES,
        ]);
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        try {
            $this->users->setActive($request->user(), $user, $user->isDeactivated());
        } catch (CannotChangeUser $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $user->fresh()->isDeactivated() ? __('Account deactivated.') : __('Account reactivated.')]);

        return back();
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(UserAdministrationService::ROLES)]]);

        try {
            $this->users->setRole($request->user(), $user, $data['role']);
        } catch (CannotChangeUser $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role changed.')]);

        return back();
    }
}
