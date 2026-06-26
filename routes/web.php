<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('classes', ClassRoomController::class)->parameters(['classes' => 'classRoom']);

    Route::prefix('classes/{classRoom}/students')->name('classes.students.')->group(function () {
        Route::get('/create', [StudentController::class, 'create'])->name('create');
        Route::post('/', [StudentController::class, 'store'])->name('store');
        Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
        Route::delete('/{student}/delete', [StudentController::class, 'deleteStudent'])->name('delete');
        Route::get('/import', [StudentController::class, 'importForm'])->name('import');
        Route::post('/import', [StudentController::class, 'import'])->name('import.store');
    });
    Route::resource('attendance', AttendanceController::class)
        ->parameters(['attendance' => 'attendanceSession'])
        ->except(['edit']);
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
});
