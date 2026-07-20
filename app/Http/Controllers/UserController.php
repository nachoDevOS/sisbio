<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * CRUD clásico (MVC) de los usuarios del sistema.
 *
 * La contraseña se hashea sola por el cast 'hashed' del modelo User; los
 * roles se asignan con spatie/permission.
 */
class UserController extends Controller
{
    /**
     * Listado de usuarios con sus roles.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $usuarios = User::with('roles')->latest()->paginate(15);

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Formulario de alta.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('usuarios.create', ['roles' => Role::orderBy('name')->get()]);
    }

    /**
     * Guarda un usuario nuevo y sincroniza sus roles.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $datos = $request->safe()->except(['roles', 'avatar']);

        if ($request->hasFile('avatar')) {
            $datos['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $usuario = User::create($datos);
        $usuario->syncRoles($this->rolesPorId($request->input('roles', [])));

        return redirect()
            ->route('usuarios.index')
            ->with('estado', 'Usuario creado correctamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(User $usuario): View
    {
        $this->authorize('update', $usuario);

        return view('usuarios.edit', [
            'usuario' => $usuario->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    /**
     * Actualiza un usuario. La contraseña vacía se ignora (se conserva).
     */
    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);

        $datos = $request->safe()->except(['roles', 'avatar', 'password']);

        // Solo cambia la contraseña si el formulario trajo una.
        if ($request->filled('password')) {
            $datos['password'] = $request->input('password');
        }

        if ($request->hasFile('avatar')) {
            $datos['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $usuario->update($datos);
        $usuario->syncRoles($this->rolesPorId($request->input('roles', [])));

        return redirect()
            ->route('usuarios.index')
            ->with('estado', 'Usuario actualizado correctamente.');
    }

    /**
     * Elimina un usuario y su avatar guardado.
     */
    public function destroy(User $usuario): RedirectResponse
    {
        $this->authorize('delete', $usuario);

        if ($usuario->avatar_path) {
            Storage::disk('public')->delete($usuario->avatar_path);
        }

        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('estado', 'Usuario eliminado.');
    }

    /**
     * Traduce los IDs de rol recibidos del formulario a nombres de rol,
     * que es lo que espera spatie/permission.
     *
     * @param  array<int|string>  $ids
     * @return array<string>
     */
    private function rolesPorId(array $ids): array
    {
        return Role::whereIn('id', $ids)->pluck('name')->all();
    }
}
