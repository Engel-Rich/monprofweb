<?php

use App\Http\Controllers\Web\CategoriController;
use App\Http\Controllers\Web\ClasseController;
use App\Http\Controllers\Web\CoursController;
use App\Http\Controllers\Web\EleveController;
use App\Http\Controllers\Web\MatieresController;
use App\Http\Controllers\Web\ProfesseursController;
use App\Http\Controllers\Web\Usercontroller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



/// Secure routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('index');
    })->name('index');
    Route::resource('classe', ClasseController::class);
    Route::resource('matiere', MatieresController::class);
    Route::get('/eleves', [EleveController::class, 'index'])->name('eleve.index');
    Route::resource('professeur', ProfesseursController::class);
    Route::resource('categorie', CategoriController::class);
    Route::resource('cours', CoursController::class);
    Route::get('/logout', [Usercontroller::class, 'logout'])->name('auth.logout');
});

/// Lgin and Register routes 

Route::middleware('guest')->group(function () {
    Route::get('/login', [Usercontroller::class, 'login'])->name('auth.login');
    Route::get('/register', [Usercontroller::class, 'register'])->name('auth.register');
    Route::post('/register', [Usercontroller::class, 'store'])->name('auth.store');
    Route::post('/login', [Usercontroller::class, 'signIn'])->name('auth.signin');
});
