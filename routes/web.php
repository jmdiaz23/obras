<?php

use App\Http\Controllers\ExportController;
use App\Livewire\Apus\ApuEditor;
use App\Livewire\Apus\Apus;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Catalogos\CargosMo;
use App\Livewire\Catalogos\Equipos;
use App\Livewire\Catalogos\Materiales;
use App\Livewire\Catalogos\Proveedores;
use App\Livewire\Proyectos\ProyectoEditor;
use App\Livewire\Proyectos\Proyectos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/registro', Register::class)->name('register');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->prefix('catalogos')->name('catalogos.')->group(function () {
    Route::get('/proveedores', Proveedores::class)->name('proveedores');
    Route::get('/materiales', Materiales::class)->name('materiales');
    Route::get('/equipos', Equipos::class)->name('equipos');
    Route::get('/mano-de-obra', CargosMo::class)->name('mano-de-obra');
});

Route::middleware('auth')->prefix('apus')->name('apus.')->group(function () {
    Route::get('/', Apus::class)->name('index');
    Route::get('/{apu}/editar', ApuEditor::class)->name('editar');
});

Route::middleware('auth')->prefix('proyectos')->name('proyectos.')->group(function () {
    Route::get('/', Proyectos::class)->name('index');
    Route::get('/{proyecto}/editar', ProyectoEditor::class)->name('editar');
    Route::get('/{proyecto}/exportar', [ExportController::class, 'xlsx'])->name('exportar');
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
