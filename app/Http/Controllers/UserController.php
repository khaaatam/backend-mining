<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        // ambil semua user barengan sama rolenya
        $users = User::with('roles')->get();
        return $this->successResponse($users, 'daftar user berhasil diambil');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // assign role pake spatie
        $user->assignRole($request->role);

        return $this->successResponse($user->load('roles'), 'user berhasil dibuat', 201);
    }

    public function show(User $user)
    {
        return $this->successResponse($user->load('roles'), 'detail user ditemukan');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'role'  => 'sometimes|string|exists:roles,name',
        ]);

        $user->update($request->only('name', 'email'));

        if ($request->has('role')) {
            // hapus role lama, ganti yang baru (syncRoles bawaan spatie)
            $user->syncRoles($request->role);
        }

        return $this->successResponse($user->load('roles'), 'user berhasil diupdate');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->successResponse(null, 'user berhasil dihapus');
    }
}
