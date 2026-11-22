<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $data = $r->validate([
            'name'        => 'required|string|min:3|max:100',
            'email'       => 'required|email|max:100|unique:users,email',
            'nim_nidn'    => 'required|string|min:5|max:30|unique:users,nim_nidn',
            'role'        => 'required|in:mahasiswa,dosen',
            'password'    => 'required|string|min:6|confirmed',
            'invite_code' => 'required_if:role,dosen|nullable|string',
        ], [
            'email.unique'       => 'Email sudah terdaftar',
            'nim_nidn.unique'    => 'NIM/NIDN sudah terdaftar',
            'password.min'       => 'Password minimal 6 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'invite_code.required_if' => 'Kode undangan dosen wajib diisi',
        ]);

        if ($data['role'] === 'dosen' &&
            !hash_equals((string) config('app.dosen_invite_code'), (string) $r->invite_code)) {
            return response()->json(
                ['success' => false, 'message' => 'Kode undangan dosen tidak valid'], 422);
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'nim_nidn' => $data['nim_nidn'],
            'role'     => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data'    => ['user' => $user, 'token' => $user->createToken('android')->plainTextToken],
        ], 201);
    }

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
