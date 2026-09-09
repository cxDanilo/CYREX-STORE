<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $user = new User(['role' => 'editor']);

        return view('admin.users.form', compact('user'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(10)->uncompromised()],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'whatsapp_number' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
        ]);

        $data['password'] = bcrypt($data['password']);
        // El código se genera solo, una sola vez, acá — nunca a mano, así
        // no hay forma de que dos vendedores terminen con el mismo.
        $data['ref_code'] = self::generateRefCode($data['name']);

        // 'role' no es mass assignable (ver User::$fillable) — se asigna
        // a mano acá, el único lugar (junto con update() abajo) donde
        // este controller decide el rol de una cuenta.
        $user = new User($data);
        $user->role = $data['role'];
        $user->save();

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario creado.');
    }

    // Corto y legible (el nombre, en vez de algo random) — si ya existe
    // ese slug le suma un número hasta encontrar uno libre. Se llama una
    // sola vez, al crear, para que el link ya compartido de alguien nunca
    // cambie de código más adelante.
    public static function generateRefCode(string $name): string
    {
        $base = Str::slug($name) ?: 'user';
        $code = $base;
        $i = 2;

        while (User::where('ref_code', $code)->exists()) {
            $code = $base.$i;
            $i++;
        }

        return $code;
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(10)->uncompromised()],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'whatsapp_number' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
        ]);

        // El código de referido no se toca acá — queda fijo desde que se
        // creó el usuario, para no romper un link que ya esté circulando.
        if (empty($user->ref_code)) {
            $data['ref_code'] = self::generateRefCode($data['name']);
        }

        // Un admin no puede sacarse el rol de admin a sí mismo por
        // accidente — evita quedar todos afuera si es el único admin.
        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'No puedes quitarte tu propio rol de administrador.'])->withInput();
        }

        if (! empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        // 'role' no es mass assignable (ver User::$fillable) — se asigna
        // a mano acá.
        $user->fill($data);
        $user->role = $data['role'];
        $user->save();

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['delete' => 'No puedes eliminar tu propia cuenta.']);
        }

        $user->delete();

        return back()->with('status', 'Usuario eliminado.');
    }
}
