<?php

use App\Http\Controllers\api\CategorieController;
use App\Http\Controllers\api\ClasseController;
use App\Http\Controllers\api\CodeController;
use App\Http\Controllers\api\CoursController;
use App\Http\Controllers\api\MatiereController;
use App\Http\Controllers\api\PaiementsController;
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

Route::post("eleve/register", [UserController::class, 'register'])->name('api.student.register');
Route::post("eleve/login", [UserController::class, 'login'])->name('api.student.login');

// Route::middleware('auth')->group(function (){

// });
Route::resource('classe', ClasseController::class);
Route::prefix('/code')->group(function ()  {
    Route::put('active', [CodeController::class, "activeCode"]);    
});
Route::resource('matiere', MatiereController::class);
Route::resource('categorie', CategorieController::class);
Route::resource('cours', CoursController::class);
Route::resource('paiement', PaiementsController::class)->except('index', 'show', 'edit', 'destroy', 'create', 'update');
// Route::middleware('auth.api')->group(function ()  {
    
// });
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
