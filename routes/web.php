<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseSourceController;
use App\Http\Controllers\SecurityPolicyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Databases
Route::get('/databases', [DatabaseSourceController::class, 'index'])->name('databases.index');
Route::post('/databases', [DatabaseSourceController::class, 'store'])->name('databases.store');
Route::post('/databases/{database}/test', [DatabaseSourceController::class, 'testConnection'])->name('databases.test');
Route::delete('/databases/{database}', [DatabaseSourceController::class, 'destroy'])->name('databases.destroy');

// Security & Policies
Route::get('/databases/{database}/policies', [SecurityPolicyController::class, 'edit'])->name('policies.edit');
Route::put('/databases/{database}/policies', [SecurityPolicyController::class, 'update'])->name('policies.update');

// API Keys & MCP Snippets
Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

// Audit Logs
Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
Route::get('/audit-logs/{log}', [AuditLogController::class, 'show'])->name('audit-logs.show');
