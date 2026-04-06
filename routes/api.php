<?php

use App\Http\Controllers\AppMessageController;
use App\Http\Controllers\api\CategorieController;
use App\Http\Controllers\api\ClasseController;
use App\Http\Controllers\api\CodeController;
use App\Http\Controllers\api\CoursController;
use App\Http\Controllers\api\MatiereController;
use App\Http\Controllers\api\OTPController;
use App\Http\Controllers\api\PaiementsController;
use App\Http\Controllers\api\QuestionController;
use App\Http\Controllers\api\TransactionController as ApiTransactionController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\PayementServicesController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\TransactionController;
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

Route::middleware('emei-verify')->group(function () {
    Route::post("auth/refresh-token", [UserController::class, 'refresh'])->name('api.user.refresh_token');
    Route::post("eleve/register", [UserController::class, 'register'])->name('api.student.register');
    Route::post("user/login", [UserController::class, 'login'])->name('api.student.register');
    Route::post("parent/register", [UserController::class, 'registerParent'])->name('api.parent.register');
    Route::post("user/logout", [UserController::class, 'logout'])->name('api.user.logout');
    Route::post("user/update_profile", [UserController::class, 'updateProfile'])->name('api.user.update_profile');
    Route::resource('classe', ClasseController::class);

    // Gestion des mots de passe

    Route::post("user/reset_password", [UserController::class, 'resetPassword'])->name('api.user.reset_password');
    Route::post("user/request_otp", [OTPController::class, 'store'])->name('api.otp.request');
    Route::post("user/verify_otp", [OTPController::class, 'verifyOtp'])->name('api.otp.verify');

    Route::prefix('/code')->group(function () {
        Route::put('/active', [CodeController::class, "activeCode"]);
    });
    Route::resource('matiere', MatiereController::class)->only(['index']);
    Route::middleware('auth:sanctum')->put('/auth/fcm_token', [UserController::class, 'updateTocken']);
    Route::middleware('auth:sanctum')->put('/auth/update_profile', [UserController::class, 'updateTocken']);
    Route::middleware('auth:sanctum')->get('/transactions', [ApiTransactionController::class, 'index']);
    Route::post('/sugestion', [SuggestionController::class, 'store']);
    Route::resource('categorie', CategorieController::class)->only(['index']);
    Route::get('categorie/status', [CategorieController::class, 'status']);
    Route::get('categorie/parent/status', [CategorieController::class, 'statusCodesParent']);
    Route::resource('cours', CoursController::class)->only(['index']);
    Route::resource('question', QuestionController::class)->only(['index', 'store']);
    Route::resource('paiement', PaiementsController::class)->except(['index', 'show', 'edit', 'destroy', 'create', 'update']);
    Route::controller(AppMessageController::class)->prefix('/notifications')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/', 'index')->name('messages.api.index');
        Route::get('/unread', 'getNotificationsOnRead')->name('messages.get-unread');
        Route::put('/read', 'readNotification')->name('messages.read');
    });

    Route::resource('payment_services', PayementServicesController::class)->only(['index']);
});
Route::post('transaction/webhook', [TransactionController::class, 'validatePaymentCallback'])->name('api.transaction.callback');

Route::apiResource('payment-services', PayementServicesController::class)->middleware('auth:sanctum');