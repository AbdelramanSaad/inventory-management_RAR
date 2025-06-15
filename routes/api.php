<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\AuditLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication Routes
Route::group(['prefix' => 'auth'], function () {
    // Public authentication routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    
    // Protected authentication routes
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'userProfile']);
    });
});

// Protected Routes
Route::group(['middleware' => 'auth:api'], function () {
    // Inventory Items Routes
    Route::get('inventory-items', [InventoryItemController::class, 'index']);
    Route::get('inventory-items/{id}', [InventoryItemController::class, 'show']);
    
    // Routes for warehouse managers and admins
    Route::middleware('can:manage-inventory')->group(function () {
        Route::post('inventory-items', [InventoryItemController::class, 'store']);
        Route::put('inventory-items/{id}', [InventoryItemController::class, 'update']);
    });
    
    // Routes for admins only
    Route::middleware('can:admin-actions')->group(function () {
        Route::delete('inventory-items/{id}', [InventoryItemController::class, 'destroy']);
    });
    
    // Audit Logs Routes
    Route::get('audit-logs', [AuditLogController::class, 'index']);
});
