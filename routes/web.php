<?php

use App\Http\Controllers\AppMessageController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\Web\CategoriController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminStatisticsController;
use App\Http\Controllers\Web\ClasseController;
use App\Http\Controllers\Web\CodesController;
use App\Http\Controllers\Web\CoursController;
use App\Http\Controllers\Web\EleveController;
use App\Http\Controllers\Web\MatieresController;
use App\Http\Controllers\Web\PaiementsController;
use App\Http\Controllers\Web\ProfesseursController;
use App\Http\Controllers\Web\QuestionsController;
use App\Http\Controllers\Web\ReponsesController;
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
    Route::get('/', AdminDashboardController::class)->name('index');
    Route::resource('classe', ClasseController::class)->except(['show']);

    Route::post('/classe/add_matiere', [ClasseController::class, 'addMatiereToClasse'])->name('classe.add_matiere');
    Route::delete('/classe/delete_matiere', [ClasseController::class, 'deleteMatiereToClasse'])->name('classe.delete_matiere');

    Route::resource('matiere', MatieresController::class)->except(['show']);
    Route::get('/eleves', [EleveController::class, 'index'])->name('eleve.index');
    Route::resource('professeur', ProfesseursController::class);
    Route::resource('categorie', CategoriController::class);
    Route::resource('cours', CoursController::class);
    Route::resource('question', QuestionsController::class)->only(['index', 'show']);
    Route::resource('reponse', ReponsesController::class)->only(['store', 'update']);
    Route::resource('paiements', ReponsesController::class)->only(['index']);
    Route::prefix('/code')->group(function () {
        Route::get('/{status}', [CodesController::class, 'index'])->name('codes.index');
    });
    Route::get('/paiement', [PaiementsController::class, 'index'])->name('paiement.index');
    Route::get('/paiement/{paiement}', [PaiementsController::class, 'active'])->name('paiement.active');
    Route::post('/paiement/activate', [PaiementsController::class, 'valide'])->name('paiement.valide');
    Route::get('/logout', [Usercontroller::class, 'logout'])->name('auth.logout');
    Route::controller(AppMessageController::class)->group(function () {
        Route::get('/messages', 'index')->name('messages.index');
        Route::post('/messages', 'store')->name('messages.store');
        Route::get('/messages/new', 'create')->name('messages.create');
        Route::get('/messages/{appMessage}/edit', 'edit')->name('messages.edit');
        Route::put('/messages/{appMessage}', 'update')->name('messages.update');
    })->middleware('auth');
    Route::get('/suggestion', [SuggestionController::class, 'index'])->name('index.suggestion');
    Route::get('/partenaires', [Usercontroller::class, 'partnerIndex'])->name('partner.index');
    Route::get('/partenaires/add', [Usercontroller::class, 'add_partner'])->name('partner.add');
    Route::post('/partenaires/add', [Usercontroller::class, 'storePartner'])->name('partner.store');
    Route::get('/statistiques', AdminStatisticsController::class)->name('statistiques');

    // Route::get('/test-firebase', function () {
    //     dd(app('firebase.storage'));
    // });
});

/// Lgin and Register routes 

Route::middleware('guest')->group(function () {
    Route::get('/login', [Usercontroller::class, 'login'])->name('auth.login');
    Route::get('/register', [Usercontroller::class, 'register'])->name('auth.register');
    Route::post('/register', [Usercontroller::class, 'store'])->name('auth.store');
    Route::post('/login', [Usercontroller::class, 'signIn'])->name('auth.signin');
});
