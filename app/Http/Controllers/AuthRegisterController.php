<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use Illuminate\Http\Request;

class AuthRegisterController extends Controller
{
    public function __invoke(Request $request)
    {

        $validatedRequest = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);
        $user = \App\Models\User::create($validatedRequest);

        // return new AuthResource($user);

        return $user->createToken('token');

    }
}
