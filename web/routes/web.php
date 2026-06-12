<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\MosqueController;
use App\Http\Controllers\Owner\ReportController;
use App\Http\Controllers\Owner\UserController;
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
Route::prefix('owner')->name('owner.')->middleware(['auth:web', 'owner'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/mosques', [MosqueController::class, 'index'])->name('mosques.index');
    Route::get('/mosques/pending', [MosqueController::class, 'pending'])->name('mosques.pending');
    Route::get('/mosques/{id}', [MosqueController::class, 'show'])->name('mosques.show');
    Route::post('/mosques/{id}/approve', [MosqueController::class, 'approve'])->name('mosques.approve');
    Route::post('/mosques/{id}/reject', [MosqueController::class, 'reject'])->name('mosques.reject');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Owner\SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\Owner\SettingController::class, 'update'])->name('settings.update');
    
    // User management (CRUD akun owner)
    Route::resource('users', UserController::class)->except(['show']);

    // Reports
    Route::get('/reports/fee', [ReportController::class, 'feeIndex'])->name('reports.fee.index');
    Route::get('/reports/fee/export', [ReportController::class, 'feeExport'])->name('reports.fee.export');
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
