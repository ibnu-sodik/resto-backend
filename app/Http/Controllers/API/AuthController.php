<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return ApiResponse::error('Data tidak ditemukan.', 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Password salah', 404);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return ApiResponse::success('Login berhasil', [
            'token' => $token,
            'user'  => $user->only(['id', 'name', 'email', 'role'])
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return ApiResponse::success('Logged out');
    }
}
