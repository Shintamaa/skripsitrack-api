<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $r->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $r->email)->first();

        if (!$user || !Hash::check($r->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Email atau password salah'], 401);
        }
        return response()->json(['success' => true, 'message' => 'Login berhasil',
            'data' => ['user' => $user, 'token' => $user->createToken('android')->plainTextToken]]);
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logout berhasil']);
    }

    public function me(Request $r) { return response()->json(['success' => true, 'data' => $r->user()]); }

    public function supervisors()  // daftar dosen untuk dropdown saat membuat skripsi
    {
        return response()->json(['success' => true,
            'data' => User::where('role', 'dosen')->get(['id', 'name', 'nim_nidn'])]);
    }
}
