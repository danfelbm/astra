<?php
use Modules\Users\Http\Controllers\Admin\UserController;
use Modules\Users\Http\Controllers\Admin\UserUpdateRequestController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','verified','admin'])->prefix('admin')->name('admin.')->group(function(){
Route::get('usuarios',[UserController::class,'index'])->middleware('can:users.view')->name('usuarios.index');
Route::get('usuarios/create',[UserController::class,'create'])->middleware('can:users.create')->name('usuarios.create');
Route::post('usuarios',[UserController::class,'store'])->middleware('can:users.create')->name('usuarios.store');
Route::get('usuarios/{usuario}/edit',[UserController::class,'edit'])->middleware('can:users.edit')->name('usuarios.edit');
Route::put('usuarios/{usuario}',[UserController::class,'update'])->middleware('can:users.edit')->name('usuarios.update');
Route::delete('usuarios/{usuario}',[UserController::class,'destroy'])->middleware('can:users.delete')->name('usuarios.destroy');
Route::post('usuarios/{usuario}/toggle-active',[UserController::class,'toggleActive'])->middleware('can:users.edit')->name('usuarios.toggle-active');
Route::post('usuarios/{usuario}/avatar',[UserController::class,'uploadAvatar'])->middleware('can:users.edit')->name('usuarios.avatar.upload');
Route::delete('usuarios/{usuario}/avatar',[UserController::class,'deleteAvatar'])->middleware('can:users.edit')->name('usuarios.avatar.delete');
Route::prefix('solicitudes-actualizacion')->name('update-requests.')->controller(UserUpdateRequestController::class)->group(function(){
Route::get('/','index')->middleware('can:users.update_requests')->name('index');
Route::get('/{updateRequest}','show')->middleware('can:users.update_requests')->name('show');
Route::post('/{updateRequest}/approve','approve')->middleware('can:users.approve_updates')->name('approve');
Route::post('/{updateRequest}/reject','reject')->middleware('can:users.approve_updates')->name('reject');
Route::get('/{updateRequest}/download','downloadDocument')->middleware('can:users.update_requests')->name('download');
Route::get('/export/csv','export')->middleware('can:users.export')->name('export');
});
});
