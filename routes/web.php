<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/run-migration', function () {
    try {
        Artisan::call('migrate --force');
        return 'قاعدة البيانات جاهزة والجداول رُفعت بنجاح! 🎉<br><pre>' . Artisan::output() . '</pre>';
    } catch (\Exception $e) {
        return 'حدث خطأ أثناء رفع الجداول: ' . $e->getMessage();
    }
});
Route::get('/', function () {
    return view('welcome');
});
