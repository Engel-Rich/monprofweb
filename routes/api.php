<?php

use App\Http\Controllers\api\CategorieController;
use App\Http\Controllers\api\ClasseController;
use App\Http\Controllers\api\CodeController;
use App\Http\Controllers\api\CoursController;
use App\Http\Controllers\api\MatiereController;
use App\Http\Controllers\api\PaiementsController;
use App\Http\Controllers\api\QuestionController;
use App\Http\Controllers\api\UserController;
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

Route::post("auth/refresh-token", [UserController::class, 'refresh'])->name('api.user.refresh_token');
Route::post("eleve/register", [UserController::class, 'register'])->name('api.student.register');
Route::post("parent/register", [UserController::class, 'registerParent'])->name('api.parent.register');
Route::post("user/login", [UserController::class, 'login'])->name('api.user.login');
Route::post("user/update_profile", [UserController::class, 'updateProfile'])->name('api.user.update_profile');



// Route::middleware('auth')->group(function (){

// });
Route::resource('classe', ClasseController::class);
Route::prefix('/code')->group(function ()  {
    Route::put('/active', [CodeController::class, "activeCode"]);    
});
Route::resource('matiere', MatiereController::class)->only(['index']);
Route::resource('categorie', CategorieController::class)->only(['index']);
Route::get('categorie/status',[CategorieController::class, 'status']);
Route::get('categorie/parent/status',[CategorieController::class, 'statusCodesParent']);
Route::resource('cours', CoursController::class)->only(['index']);
Route::resource('question', QuestionController::class)->only(['index','store']);
Route::resource('paiement', PaiementsController::class)->except(['index', 'show', 'edit', 'destroy', 'create', 'update']);
// Route::middleware('auth.api')->group(function ()  {
    
// });
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
