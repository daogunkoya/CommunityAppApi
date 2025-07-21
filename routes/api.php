<?php

use App\Http\Controllers\AuthLoginController;
use App\Http\Controllers\AuthRegisterController;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('/login', AuthLoginController::class);

Route::post('/logout', 'Auth\LoginController@logout');

Route::get('/users', 'Api\UserController@index');

Route::post('/users', AuthRegisterController::class);

Route::get('/home', HomeController::class);
