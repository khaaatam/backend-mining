<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // validasi inputan
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // cek kredensial email & password
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('email atau password salah', 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // generate token sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // balikin response pake format standard
        return $this->successResponse([
            'user' => $user,
            'token' => $token,
            'role' => $user->getRoleNames()->first()
        ], 'login berhasil');
    }

    public function logout(Request $request)
    {
        // hapus token sanctum yang lagi dipake user
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'logout berhasil');
    }
}
