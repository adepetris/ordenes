<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'force.password.change'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:administrador,usuario_solicitante,usuario_autorizado')
        ->name('dashboard');

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::get('/orders', [PurchaseOrderController::class, 'index'])
        ->middleware('role:administrador,usuario_solicitante,usuario_autorizado')
        ->name('orders.index');
    Route::get('/orders/export', [PurchaseOrderController::class, 'export'])
        ->middleware('role:administrador,usuario_solicitante,usuario_autorizado')
        ->name('orders.export');
    Route::get('/orders/{order}/export', [PurchaseOrderController::class, 'exportSingle'])
        ->middleware('role:administrador,usuario_solicitante,usuario_autorizado')
        ->whereNumber('order')
        ->name('orders.export.single');
    Route::get('/orders/{order}', [PurchaseOrderController::class, 'show'])
        ->middleware('role:administrador,usuario_solicitante,usuario_autorizado')
        ->whereNumber('order')
        ->name('orders.show');
    Route::get('/orders/{order}/attachments/{attachment}', [PurchaseOrderController::class, 'downloadAttachment'])
        ->middleware('role:administrador,usuario_solicitante,usuario_autorizado')
        ->whereNumber('order')
        ->whereNumber('attachment')
        ->name('orders.attachments.download');

    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->middleware('role:administrador,usuario_solicitante,usuario_autorizado')
        ->name('suppliers.index');
    Route::get('/suppliers/export', [SupplierController::class, 'export'])
        ->middleware('role:administrador,usuario_solicitante,usuario_autorizado')
        ->name('suppliers.export');

    Route::middleware('role:administrador,usuario_solicitante,usuario_autorizado')->group(function (): void {
        Route::get('/orders/create', [PurchaseOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [PurchaseOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}/edit', [PurchaseOrderController::class, 'edit'])->whereNumber('order')->name('orders.edit');
        Route::put('/orders/{order}', [PurchaseOrderController::class, 'update'])->whereNumber('order')->name('orders.update');
        Route::post('/orders/{order}/attachments', [PurchaseOrderController::class, 'uploadAttachment'])
            ->whereNumber('order')
            ->name('orders.attachments.store');
    });

    Route::middleware('role:administrador,usuario_solicitante,usuario_autorizado')->group(function (): void {
        Route::post('/orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->whereNumber('order')->name('orders.submit');
    });

    Route::middleware('role:administrador,usuario_autorizado')->group(function (): void {
        Route::get('/approvals', [PurchaseOrderController::class, 'approvalsInbox'])->name('approvals.index');
        Route::post('/orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->whereNumber('order')->name('orders.approve');
        Route::post('/orders/{order}/reject', [PurchaseOrderController::class, 'reject'])->whereNumber('order')->name('orders.reject');
    });

    Route::middleware('role:administrador,usuario_solicitante,usuario_autorizado')->group(function (): void {
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    });

    Route::middleware('role:administrador,usuario_autorizado')->group(function (): void {
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    });

    Route::middleware('role:administrador')->group(function (): void {
        Route::post('/orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->whereNumber('order')
            ->name('orders.cancel');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.password.reset');
    });

    Route::get('/admin', function () {
        return view('admin');
    })->middleware('role:administrador')->name('admin');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('role:administrador')
        ->name('audit-logs.index');

    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])
        ->middleware('role:administrador')
        ->name('audit-logs.export');

    Route::post('/logout', LogoutController::class)->name('logout');
});
