<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::view('/', 'welcome')->name('home');
Route::get('/admin', DashboardController::class)->middleware(['auth', 'can:view-dashboard'])->name('dashboard');
Route::get('/orders', [OrderController::class, 'index'])->middleware(['auth', 'can:view-orders'])->name('orders.index');
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware(['auth', 'can:view-orders'])->name('orders.show');
Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->middleware(['auth', 'can:view-orders'])->name('orders.edit');
Route::put('/orders/{order}', [OrderController::class, 'update'])->middleware(['auth', 'can:view-orders'])->name('orders.update');
Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->middleware(['auth', 'role:super_admin'])->name('orders.destroy');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->middleware('can:manage-order-approvals')->name('orders.accept');
    Route::post('/orders/{order}/reject', [OrderController::class, 'reject'])->middleware('can:manage-order-approvals')->name('orders.reject');
    Route::get('/branches', [BranchController::class, 'index'])->middleware('can:manage-branches')->name('branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->middleware('can:manage-branch-master-data')->name('branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->middleware('can:manage-branch-master-data')->name('branches.store');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->middleware('can:manage-branch-master-data')->name('branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->middleware('can:manage-branch-master-data')->name('branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->middleware('can:manage-branch-master-data')->name('branches.destroy');
    Route::get('/products', [ProductController::class, 'index'])->middleware('can:manage-products')->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('can:manage-products')->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->middleware('can:manage-products')->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->middleware('can:manage-products')->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('can:manage-products')->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('can:manage-products')->name('products.destroy');
    Route::get('/daily-reports', [DailyReportController::class, 'index'])->middleware('can:manage-daily-reports')->name('daily-reports.index');
    Route::put('/daily-reports', [DailyReportController::class, 'update'])->middleware('can:manage-daily-reports')->name('daily-reports.update');
    Route::get('/inventory', [InventoryController::class, 'index'])->middleware('can:manage-inventory')->name('inventory.index');
    Route::post('/inventory/movements', [InventoryController::class, 'storeMovement'])->middleware('can:manage-inventory')->name('inventory.movements.store');
    Route::post('/inventory/materials', [InventoryController::class, 'storeMaterial'])->middleware('can:manage-all-inventory')->name('inventory.materials.store');
    Route::put('/inventory/materials/{rawMaterial}', [InventoryController::class, 'updateMaterial'])->middleware('can:manage-all-inventory')->name('inventory.materials.update');
    Route::get('/categories', [ProductCategoryController::class, 'index'])->middleware('can:manage-categories')->name('categories.index');
    Route::get('/categories/create', [ProductCategoryController::class, 'create'])->middleware('can:manage-categories')->name('categories.create');
    Route::post('/categories', [ProductCategoryController::class, 'store'])->middleware('can:manage-categories')->name('categories.store');
    Route::get('/categories/{category}/edit', [ProductCategoryController::class, 'edit'])->middleware('can:manage-categories')->name('categories.edit');
    Route::put('/categories/{category}', [ProductCategoryController::class, 'update'])->middleware('can:manage-categories')->name('categories.update');
    Route::delete('/categories/{category}', [ProductCategoryController::class, 'destroy'])->middleware('can:manage-categories')->name('categories.destroy');
    Route::get('/bookings', [BookingController::class, 'index'])->middleware('can:view-bookings')->name('bookings.index');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('can:view-reports')->name('reports.index');
    Route::resource('users', UserController::class)->except('show')->middleware('can:manage-users');
    Route::resource('roles', RoleController::class)->except('show')->middleware('can:manage-roles');
    Route::resource('permissions', PermissionController::class)->except('show')->middleware('can:manage-permissions');
    Route::get('/settings', [SettingsController::class, 'edit'])->middleware('can:manage-integration-settings')->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('can:manage-integration-settings')->name('settings.update');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('can:view-audit-trail')->name('audit-logs.index');
});
