<?php

use Illuminate\Support\Facades\Auth;
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

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/dashboard', 'HomeController@index')->name('dashboard');

    Route::get('/profile', 'ProfileController@index')->name('profile');
    Route::put('/profile', 'ProfileController@update')->name('profile.update');

    // Router Management
    Route::resource('routers', 'RouterController');
    Route::post('routers/{router}/test-connection', 'RouterController@testConnection')->name('routers.test-connection');
    Route::post('routers/{router}/sync-users', 'RouterController@syncUsers')->name('routers.sync-users');

    // Billing Plans
    Route::resource('billing-plans', 'BillingPlanController');
    Route::post('billing-plans/{billingPlan}/toggle', 'BillingPlanController@toggle')->name('billing-plans.toggle');

    // Hotspot Users
    Route::resource('hotspot-users', 'HotspotUserController');
    Route::post('hotspot-users/{hotspotUser}/disconnect', 'HotspotUserController@disconnect')->name('hotspot-users.disconnect');
    Route::get('hotspot-users-batch', 'HotspotUserController@generateBatch')->name('hotspot-users.batch');
    Route::post('hotspot-users-batch', 'HotspotUserController@storeBatch')->name('hotspot-users.store-batch');

    // Vouchers
    Route::resource('vouchers', 'VoucherController');
    Route::post('vouchers/{voucher}/activate', 'VoucherController@activate')->name('vouchers.activate');
    Route::get('vouchers/print/{batch_id}', 'VoucherController@print')->name('vouchers.print');
    Route::delete('vouchers/batch/{batch_id}', 'VoucherController@bulkDelete')->name('vouchers.bulk-delete');

    // Monitoring & Reports
    Route::get('monitoring', 'MonitoringController@index')->name('monitoring.index');
    Route::get('monitoring/realtime/{router}', 'MonitoringController@getRealtimeData')->name('monitoring.realtime');
    Route::get('monitoring/bandwidth/{router}', 'MonitoringController@bandwidthChart')->name('monitoring.bandwidth');
    Route::get('reports', 'ReportController@index')->name('reports.index');
    Route::get('reports/revenue', 'ReportController@revenue')->name('reports.revenue');
    Route::get('reports/usage', 'ReportController@usage')->name('reports.usage');

    // Transactions
    Route::resource('transactions', 'TransactionController')->only(['index', 'show']);
});

Route::get('/about', function () {
    return view('about');
})->name('about');
