<?php

use Illuminate\Support\Facades\Route;

// Redirect root ke login
Route::get('/', fn () => redirect()->route('login'));

// Auth
Route::get('/login', fn () => view('auth.login'))->name('login');
Route::post('/logout', fn () => redirect()->route('login'))->name('logout');

// Owner panel — placeholder routes untuk preview layout
Route::prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', fn () => view('owner.dashboard'))->name('dashboard');
    Route::get('/mosques',         fn () => view('owner.dashboard'))->name('mosques.index');
    Route::get('/mosques/pending', fn () => view('owner.dashboard'))->name('mosques.pending');
    Route::get('/settings',        fn () => view('owner.dashboard'))->name('settings.index');
    Route::get('/users',           fn () => view('owner.dashboard'))->name('users.index');
    Route::get('/reports',         fn () => view('owner.dashboard'))->name('reports.index');
});

// Admin panel — placeholder routes untuk preview layout
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');
    Route::get('/mosque/edit',             fn () => view('admin.dashboard'))->name('mosque.edit');
    Route::get('/schedules',               fn () => view('admin.dashboard'))->name('schedules.index');
    Route::get('/activities',              fn () => view('admin.dashboard'))->name('activities.index');
    Route::get('/finance/income',          fn () => view('admin.dashboard'))->name('finance.income.index');
    Route::get('/finance/expense',         fn () => view('admin.dashboard'))->name('finance.expense.index');
    Route::get('/finance/report',          fn () => view('admin.dashboard'))->name('finance.report.index');
    Route::get('/announcements',           fn () => view('admin.dashboard'))->name('announcements.index');
    Route::get('/congregation',            fn () => view('admin.dashboard'))->name('congregation.index');
    Route::get('/staff',                   fn () => view('admin.dashboard'))->name('staff.index');
    Route::get('/donations',               fn () => view('admin.dashboard'))->name('donations.index');
});
