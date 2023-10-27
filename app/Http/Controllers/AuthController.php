<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'exists:admins,name'],
            'password' => ['required']
        ]);

        $admin = Admin::query()->where('name', $data['name'])->first();
        if (Hash::check($data['password'], $admin->password)) {
            $admin->tokens()->delete();
            $token = $admin->createToken('admin');
            return ['token' => $token->plainTextToken, 'admin' => $admin];
        }

        abort(ResponseStatus::UNAUTHENTICATED->value, 'Incorrect Password');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'password' => ['required'],
            'new_password' => ['required', 'confirmed']
        ]);

        $admin = $request->user();

        if (Hash::check($data['password'], $admin->password)) {
            $admin->update(['password' => bcrypt($data['new_password'])]);
            return response()->json(['message' => 'ok']);
        }

        abort(ResponseStatus::UNAUTHENTICATED->value, 'Incorrect Password');
    }

    public function logout(Request $request)
    {
        $admin = $request->user();
        $admin->tokens()->delete();

        return response()->json(['message' => 'success']);
    }
}
