<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

// صفحة عرض الإشعارات التوضيحية
Route::get('/notifications-demo', function () {
    return view('notifications-demo');
});

// لوحة تحكم WebSockets
Route::get('/laravel-websockets', function () {
    return redirect('/laravel-websockets');
});

// توثيق Swagger
Route::get('/api/documentation', function () {
    return view('vendor.l5-swagger.index');
});

// ملف توثيق Swagger JSON
Route::get('/api/documentation/api-docs.json', function () {
    return response()
        ->file(storage_path('api-docs/api-docs.json'), [
            'Content-Type' => 'application/json'
        ]);
});
