<?php

use App\Http\Controllers\api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post("eleve/register", [UserController::class, 'register'] )->name('api.student.register');
Route::post("eleve/login", [UserController::class, 'login'] )->name('api.student.login');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
