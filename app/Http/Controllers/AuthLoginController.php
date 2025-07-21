<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthLoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],

        ]);

        if (Auth::once($credentials)) {
            return new AuthResource(auth()->user());
        }

        return $this->apiError('Unauthorized', 401);
    }
}
