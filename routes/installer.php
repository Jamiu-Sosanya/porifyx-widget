<?php

use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->middleware('installer')->group(function () {
    Route::get('/', [InstallerController::class, 'welcome'])->name('installer.welcome');
    Route::get('requirements', [InstallerController::class, 'requirements'])->name('installer.requirements');
    Route::get('permissions', [InstallerController::class, 'permissions'])->name('installer.permissions');
    Route::get('environment', [InstallerController::class, 'environment'])->name('installer.environment');
    Route::post('environment', [InstallerController::class, 'saveEnvironment'])->name('installer.environment.save');
    Route::get('database', [InstallerController::class, 'database'])->name('installer.database');
    Route::post('database', [InstallerController::class, 'migrate'])->name('installer.migrate');
    Route::get('admin', [InstallerController::class, 'admin'])->name('installer.admin');
    Route::post('admin', [InstallerController::class, 'createAdmin'])->name('installer.admin.create');
    Route::get('finish', [InstallerController::class, 'finish'])->name('installer.finish');
});
