<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\DashboardController;
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
Route::get('/admin', DashboardController::class)->middleware(['auth', 'role:super_admin,production_branch_manager,internal_outlet,whole_marketer'])->name('dashboard');
Route::get('/orders', [OrderController::class, 'index'])->middleware(['auth', 'role:super_admin,production_branch_manager,internal_outlet,whole_marketer'])->name('orders.index');
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/orders/{order}/accept', [OrderController::class, 'accept'])->middleware('role:super_admin,production_branch_manager')->name('orders.accept');
    Route::post('/orders/{order}/reject', [OrderController::class, 'reject'])->middleware('role:super_admin,production_branch_manager')->name('orders.reject');
    Route::get('/branches', [BranchController::class, 'index'])->middleware('role:super_admin,production_branch_manager')->name('branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->middleware('role:super_admin')->name('branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->middleware('role:super_admin')->name('branches.store');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->middleware('role:super_admin')->name('branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->middleware('role:super_admin')->name('branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->middleware('role:super_admin')->name('branches.destroy');
    Route::get('/products', [ProductController::class, 'index'])->middleware('role:super_admin,production_branch_manager')->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('role:super_admin')->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->middleware('role:super_admin')->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->middleware('role:super_admin')->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('role:super_admin')->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('role:super_admin')->name('products.destroy');
    Route::get('/categories', [ProductCategoryController::class, 'index'])->middleware('role:super_admin')->name('categories.index');
    Route::get('/categories/create', [ProductCategoryController::class, 'create'])->middleware('role:super_admin')->name('categories.create');
    Route::post('/categories', [ProductCategoryController::class, 'store'])->middleware('role:super_admin')->name('categories.store');
    Route::get('/categories/{category}/edit', [ProductCategoryController::class, 'edit'])->middleware('role:super_admin')->name('categories.edit');
    Route::put('/categories/{category}', [ProductCategoryController::class, 'update'])->middleware('role:super_admin')->name('categories.update');
    Route::delete('/categories/{category}', [ProductCategoryController::class, 'destroy'])->middleware('role:super_admin')->name('categories.destroy');
    Route::get('/bookings', [BookingController::class, 'index'])->middleware('role:super_admin,production_branch_manager,whole_marketer')->name('bookings.index');
    Route::get('/reports', [ReportController::class, 'index'])->middleware('role:super_admin,production_branch_manager')->name('reports.index');
    Route::get('/users', [UserController::class, 'index'])->middleware('role:super_admin')->name('users.index');
    Route::get('/settings', [SettingsController::class, 'edit'])->middleware('role:super_admin')->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('role:super_admin')->name('settings.update');
});
