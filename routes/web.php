<?php

use App\Http\Controllers\Web\ClasseController;
use App\Http\Controllers\Web\MatieresController;
use App\Http\Controllers\Web\ProfesseursController;
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

Route::get('/', function () {
    return view('index');
})->name('index');
Route::resource('classe', ClasseController::class);
Route::resource('matiere', MatieresController::class);
Route::resource('professeur', ProfesseursController::class);