<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RuleController;
use App\Http\Controllers\Admin\BlockController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\KeyController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\BorrowController;
use App\Http\Controllers\Admin\ReportController;

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


URL::forceRootUrl(config('app.url'));

if (config('app.env') !== 'local') {
    URL::forceScheme('https');
}

Route::redirect('/', 'admin')->name('home');

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

    Route::resource('blocks', BlockController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('rooms', RoomController::class);
    Route::resource('keys', KeyController::class);
    Route::resource('borrows', BorrowController::class);
    Route::post('borrows/{borrow}/receive/keys/{chaves}', [BorrowController::class, 'receive'])->name('borrows.receive');
    Route::delete('borrows/{borrow}/receive/{received}/keys', [BorrowController::class, 'receiveDestroy'])->name('borrows.receive.destroy');

    Route::get('reports/index', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/auth.php';
