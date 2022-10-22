<?php

use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\HomeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RuleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');


Route::prefix('admin')->middleware(['auth', 'verified'])->group(function() {
    Route::get('/', [HomeController::class, 'index'])->name('admin');
    Route::resource('users', UserController::class);
    Route::get('users/{user}/edit/password', [UserController::class, 'editPassword'])->name('users.edit.password');
    Route::put('users/{user}/edit/password', [UserController::class, 'updatePassword'])->name('users.update.password');
    Route::get('profile', [UserController::class, 'profile'])->name('profile');
    Route::resource('rules', RuleController::class);
    Route::resource('groups', GroupController::class);
    Route::resource('permissions', PermissionController::class);
    Route::get('permissions/{permission}/rules', [PermissionController::class, 'rules'])->name('permissions.rules');
    Route::put('permissions/{permission}/rules', [PermissionController::class, 'syncRules'])->name('permissions.rules.sync');
});

require __DIR__.'/auth.php';
