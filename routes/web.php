<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\PlantillaController;
use App\Livewire\Admin\Empresas as AdminEmpresas;
use App\Livewire\Admin\Panel as AdminPanel;
use App\Livewire\Admin\Usuarios as AdminUsuarios;
use App\Livewire\Apus\ApuEditor;
use App\Livewire\Apus\Apus;
use App\Livewire\Auth\Login;
use App\Livewire\Catalogos\CargosMo;
use App\Livewire\Catalogos\Equipos;
use App\Livewire\Catalogos\Materiales;
use App\Livewire\Catalogos\Proveedores;
use App\Livewire\Proyectos\Avance;
use App\Livewire\Proyectos\ProyectoEditor;
use App\Livewire\Proyectos\Proyectos;
use App\Models\Apu;
use App\Models\CargoMo;
use App\Models\Equipo;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Proyecto;
use App\Services\AvanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route(auth()->user()->esSuperAdmin() ? 'admin.dashboard' : 'dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminPanel::class)->name('dashboard');
    Route::get('/empresas', AdminEmpresas::class)->name('empresas');
    Route::get('/usuarios', AdminUsuarios::class)->name('usuarios');
});

Route::get('/dashboard', function () {
    $proyectoAvance = Proyecto::query()
        ->with([
            'items.apu.materiales.material',
            'items.apu.manoObra.cargo',
            'items.apu.equipos.equipo',
            'items.apu.empresa',
            'etapas',
        ])
        ->latest()
        ->first();

    return view('dashboard', [
        'kpis' => [
            'Proyectos' => Proyecto::query()->count(),
            'APUs' => Apu::query()->count(),
            'Materiales' => Material::query()->count(),
            'Equipos' => Equipo::query()->count(),
            'Mano de obra' => CargoMo::query()->count(),
            'Proveedores' => Proveedor::query()->count(),
        ],
        'proyectosRecientes' => Proyecto::query()->latest()->take(5)->get(),
        'estados' => Proyecto::ESTADOS,
        'proyectoAvance' => $proyectoAvance,
        'avance' => $proyectoAvance ? AvanceService::calcular($proyectoAvance) : null,
    ]);
})->middleware(['auth', 'empresa'])->name('dashboard');

Route::middleware(['auth', 'empresa'])->prefix('catalogos')->name('catalogos.')->group(function () {
    Route::get('/proveedores', Proveedores::class)->name('proveedores');
    Route::get('/materiales', Materiales::class)->name('materiales');
    Route::get('/equipos', Equipos::class)->name('equipos');
    Route::get('/mano-de-obra', CargosMo::class)->name('mano-de-obra');
    Route::get('/plantilla/{modulo}', [PlantillaController::class, 'descargar'])->name('plantilla');
});

Route::middleware(['auth', 'empresa'])->prefix('apus')->name('apus.')->group(function () {
    Route::get('/', Apus::class)->name('index');
    Route::get('/{apu}/editar', ApuEditor::class)->name('editar');
});

Route::middleware(['auth', 'empresa'])->prefix('proyectos')->name('proyectos.')->group(function () {
    Route::get('/', Proyectos::class)->name('index');
    Route::get('/{proyecto}/editar', ProyectoEditor::class)->name('editar');
    Route::get('/{proyecto}/avance', Avance::class)->name('avance');
    Route::get('/{proyecto}/exportar', [ExportController::class, 'xlsx'])->name('exportar');
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
