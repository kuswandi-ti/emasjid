<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// ─── Root redirect ────────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('login'));

// ─── Auth routes (guest only) ─────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
});

Route::post('/login', [LoginController::class, 'store'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')
    ->middleware('auth');

// ─── Owner panel ──────────────────────────────────────────────────────────────
Route::prefix('owner')->name('owner.')->middleware(['auth', 'owner'])->group(function () {
    Route::get('/dashboard', fn () => view('owner.dashboard'))->name('dashboard');
    Route::get('/mosques', fn () => view('owner.dashboard'))->name('mosques.index');
    Route::get('/mosques/pending', fn () => view('owner.dashboard'))->name('mosques.pending');
    Route::get('/settings', fn () => view('owner.dashboard'))->name('settings.index');
    Route::get('/users', fn () => view('owner.dashboard'))->name('users.index');
    Route::get('/reports', fn () => view('owner.dashboard'))->name('reports.index');
});

// ─── Admin panel ──────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'mosque', 'mosque.active'])->group(function () {
    Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');
    Route::get('/mosque/edit', fn () => view('admin.dashboard'))->name('mosque.edit');
    Route::get('/schedules', fn () => view('admin.dashboard'))->name('schedules.index');
    Route::get('/activities', fn () => view('admin.dashboard'))->name('activities.index');
    Route::get('/finance/income', fn () => view('admin.dashboard'))->name('finance.income.index');
    Route::get('/finance/expense', fn () => view('admin.dashboard'))->name('finance.expense.index');
    Route::get('/finance/report', fn () => view('admin.dashboard'))->name('finance.report.index');
    Route::get('/announcements', fn () => view('admin.dashboard'))->name('announcements.index');
    Route::get('/congregation', fn () => view('admin.dashboard'))->name('congregation.index');
    Route::get('/staff', fn () => view('admin.dashboard'))->name('staff.index');
    Route::get('/donations', fn () => view('admin.dashboard'))->name('donations.index');
});
