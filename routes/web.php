<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\FirmaController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/captcha/refresh', function () {
    $captcha = app(\App\Services\CaptchaService::class);
    return response()->json(['imagen' => $captcha->generar()]);
})->name('captcha.refresh');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard - solo admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
    });

    // Alumnos - pueden acceder admin y ventanilla (crear/editar/eliminar solo admin via controlador)
    Route::middleware('role:admin,ventanilla')->group(function () {
        Route::resource('alumnos', AlumnoController::class);
        Route::get('alumnos/{alumno}/firmas', [AlumnoController::class, 'firmas'])->name('alumnos.firmas');

        // Documentos
        Route::get('documentos/buscar-alumnos', [DocumentoController::class, 'buscarAlumnos'])->name('documentos.buscarAlumnos');
        Route::get('documentos/siguiente-folio', [DocumentoController::class, 'siguienteFolio'])->name('documentos.siguienteFolio');
        Route::resource('documentos', DocumentoController::class)->except(['edit', 'update']);
        Route::get('documentos/{documento}/firmar', [DocumentoController::class, 'firmar'])->name('documentos.firmar');
        Route::post('documentos/{documento}/firma', [DocumentoController::class, 'storeFirma'])->name('documentos.storeFirma');

        // Firmas (preview / comparación)
        Route::get('firmas/{firma}', [FirmaController::class, 'show'])->name('firmas.show');
    });

    // Administración - solo admin
    Route::middleware('role:admin')->group(function () {
        Route::resource('usuarios', UsuarioController::class);
        Route::resource('tipos', TipoDocumentoController::class)->except(['create', 'edit', 'show']);

        Route::get('importaciones', [ImportController::class, 'index'])->name('importaciones.index');
        Route::post('importaciones', [ImportController::class, 'store'])->name('importaciones.store');
        Route::post('importaciones/vaciar', [ImportController::class, 'vaciarTodo'])->name('importaciones.vaciar');
        Route::post('importaciones/{importacion}/cancelar', [ImportController::class, 'cancelar'])->name('importaciones.cancelar');
        Route::get('importaciones/progreso', [ImportController::class, 'progreso'])->name('importaciones.progreso');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
