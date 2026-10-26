<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'dispositivo' => ['nullable', 'string', 'max:60'],
        ]);

        $usuario = User::where('email', $datos['email'])->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        $token = $usuario->createToken($datos['dispositivo'] ?? 'api')->plainTextToken;

        return response()->json(['token' => $token, 'rol' => $usuario->rol], 201);
    }
}
