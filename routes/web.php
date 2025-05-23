<?php

use App\Http\Controllers\AsociarController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CapachosController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/',[AsociarController::class,'login'])->name('login');
Route::get('/loginauto/{id_usuario}/{id_fase}/{id_hc?}/{accion?}',[AsociarController::class,'loginauto'])->name('loginauto');
Route::post('/',[AsociarController::class,'peticionlogin'])->name('peticionlogin');

Route::get('/asociar',[AsociarController::class,'formularioAsociar'])->name('formularioAsociar');

Route::get('/leercapacho',[CapachosController::class,'leerCapacho'])->name('leerCapacho');
//ajax
Route::post('/asociarproductocritico',[AsociarController::class,'asociarProductoCritico'])->name('asociarproductocritico');
Route::post('/buscarfasesusuario',[AsociarController::class,'buscarFasesUsuario'])->name('buscarfasesusuario');

Route::post('/ejecutarActividad',[CapachosController::class,'ejecutarActividad'])->name('ejecutarActividad');
Route::post('/obtenerCapachoQr',[CapachosController::class,'obtenerCapachoQr'])->name('obtenerCapachoQr');
