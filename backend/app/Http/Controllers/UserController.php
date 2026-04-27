<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.mb_strtolower($search).'%';

                $query->where(function ($inner) use ($like): void {
                    $inner->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                        ->orWhereRaw("LOWER(COALESCE(email, '')) LIKE ?", [$like]);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::query()->orderBy('description')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => mb_strtolower($validated['username']),
            'email' => $validated['email'] ?: null,
            'password' => $validated['password'],
            'must_change_password' => true,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $user->roles()->sync($validated['roles']);

        return redirect()->route('users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('description')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'username' => mb_strtolower($validated['username']),
            'email' => $validated['email'] ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $user->roles()->sync($validated['roles']);

        return redirect()->route('users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        $user->update([
            'password' => Hash::make($validated['new_password']),
            'must_change_password' => true,
        ]);

        return redirect()->route('users.edit', $user)->with('status', 'Contrasena restablecida correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->hasRole('administrador')) {
            return back()->withErrors(['users' => 'No se puede eliminar un usuario con perfil administrador.']);
        }

        if ($request->user()?->id === $user->id) {
            return back()->withErrors(['users' => 'No puedes eliminar tu propio usuario.']);
        }

        try {
            $user->roles()->detach();
            $user->delete();
        } catch (QueryException) {
            return back()->withErrors(['users' => 'No se pudo eliminar el usuario porque tiene informacion relacionada.']);
        }

        return redirect()->route('users.index')->with('status', 'Usuario eliminado correctamente.');
    }
}
