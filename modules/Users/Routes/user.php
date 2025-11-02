<?php
use Modules\Users\Http\Controllers\User\DashboardController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified','user'])->prefix('miembro')->name('user.')->group(function(){
Route::get('dashboard',[DashboardController::class,'index'])->name('dashboard');
});
