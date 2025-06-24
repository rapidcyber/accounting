<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\VoucherController;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
    Route::get('export/expenses', [ExpenseController::class, 'exportToExcel'])
        ->name('export.expenses');
    Route::get('expenses/print', [ExpenseController::class, 'print'])
        ->name('expenses.print');
    Route::get('vourcher/print/{id}', [VoucherController::class, 'print'])
        ->name('voucher.print');
});

require __DIR__.'/auth.php';
