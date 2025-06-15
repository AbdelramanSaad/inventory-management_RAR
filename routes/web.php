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
    return response()->view('vendor.l5-swagger.index', [
        'documentation' => 'default',
        'secure' => false,
        'urlToDocs' => '/api/documentation/api-docs.json',
        'operationsSorter' => 'alpha',
        'configUrl' => '',
        'validatorUrl' => null
    ]);
});

// ملف توثيق Swagger JSON
Route::get('/api/documentation/api-docs.json', function () {
    $path = storage_path('api-docs/api-docs.json');
    if (!file_exists($path)) {
        // إذا لم يكن الملف موجودًا، قم بتوليده
        \Artisan::call('l5-swagger:generate');
    }
    
    $content = file_get_contents($path);
    return response($content)
        ->header('Content-Type', 'application/json');
});
