<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FrontlineRetentionController;
use App\Exports\DashboardExport;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {

    return view('welcome');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

   // Route::get('/retention', [FrontlineRetentionController::class, 'index'])->name('retention.index');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/retention', [FrontlineRetentionController::class, 'index'])->name('retention.index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [FrontlineRetentionController::class, 'TeamPerformance'])->name('dashboard');
    Route::get('/agent-dashboard', [FrontlineRetentionController::class, 'summaryDashboard'])->name('agent-dashboard');
    Route::get('export-dashboard', function () {
        return Excel::download(new DashboardExport, 'dashboard.xlsx');
    });



});


require __DIR__.'/auth.php';
