<?php

use App\Http\Controllers\ProductosPdfController;
use App\Livewire\AlertasPage;
use App\Livewire\CajasPage;
use App\Livewire\CategoriasPage;
use App\Livewire\Dashboard;
use App\Livewire\MarcasPage;
use App\Livewire\OrdenesCompraPage;
use App\Livewire\ProductosPage;
use App\Livewire\ProveedoresPage;
use App\Livewire\RecepcionesRegistrarPage;
use App\Livewire\TasaCambioPage;
use App\Livewire\VentasRegistrarPage;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');

Route::get('/categorias', CategoriasPage::class)->name('categorias');
Route::get('/marcas', MarcasPage::class)->name('marcas');
Route::get('/productos', ProductosPage::class)->name('productos');
Route::get('/productos/pdf', ProductosPdfController::class)->name('productos.pdf');
Route::get('/proveedores', ProveedoresPage::class)->name('proveedores');

Route::get('/ordenes-compra', OrdenesCompraPage::class)->name('ordenes_compra');
Route::get('/recepciones', RecepcionesRegistrarPage::class)->name('recepciones');

Route::get('/caja', CajasPage::class)->name('caja');
Route::get('/ventas', VentasRegistrarPage::class)->name('ventas');

Route::get('/alertas', AlertasPage::class)->name('alertas');

// Configuración
Route::get('/configuracion/tasa-cambio', TasaCambioPage::class)->name('configuracion.tasa_cambio');
