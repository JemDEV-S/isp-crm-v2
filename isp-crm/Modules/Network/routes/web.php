<?php

use Illuminate\Support\Facades\Route;
use Modules\Network\Http\Controllers\DeviceController;
use Modules\Network\Http\Controllers\FiberRouteController;
use Modules\Network\Http\Controllers\IpPoolController;
use Modules\Network\Http\Controllers\NapBoxController;
use Modules\Network\Http\Controllers\NodeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('network')->name('network.')->group(function () {
    // Nodes
    Route::get('topology', [NodeController::class, 'topology'])->name('topology');
    Route::get('nodes/json', [NodeController::class, 'json'])->name('nodes.json');
    Route::resource('nodes', NodeController::class);

    // Devices
    Route::get('devices/{device}/test-connection', [DeviceController::class, 'testConnection'])->name('devices.test-connection');
    Route::get('devices/{device}/system-info', [DeviceController::class, 'systemInfo'])->name('devices.system-info');
    Route::get('devices/{device}/pppoe-profiles', [DeviceController::class, 'pppoeProfiles'])->name('devices.pppoe-profiles');
    Route::get('devices/{device}/active-connections', [DeviceController::class, 'activeConnections'])->name('devices.active-connections');
    Route::get('devices/{device}/unauthorized-onus', [DeviceController::class, 'unauthorizedOnus'])->name('devices.unauthorized-onus');
    Route::resource('devices', DeviceController::class);

    // IP Pools
    Route::get('ip-pools/{ipPool}/addresses', [IpPoolController::class, 'addresses'])->name('ip-pools.addresses');
    Route::get('ip-pools/{ipPool}/stats', [IpPoolController::class, 'stats'])->name('ip-pools.stats');
    Route::post('ip-pools/assign-ip', [IpPoolController::class, 'assignIp'])->name('ip-pools.assign-ip');
    Route::post('ip-addresses/{ipAddress}/release', [IpPoolController::class, 'releaseIp'])->name('ip-addresses.release');
    Route::post('ip-addresses/{ipAddress}/reserve', [IpPoolController::class, 'reserveIp'])->name('ip-addresses.reserve');
    Route::post('ip-addresses/{ipAddress}/blacklist', [IpPoolController::class, 'blacklistIp'])->name('ip-addresses.blacklist');
    Route::resource('ip-pools', IpPoolController::class);

    // NAP Boxes
    Route::get('nap-boxes/geojson', [NapBoxController::class, 'geoJson'])->name('nap-boxes.geojson');
    Route::get('nap-boxes/find-nearest', [NapBoxController::class, 'findNearest'])->name('nap-boxes.find-nearest');
    Route::post('nap-boxes/check-feasibility', [NapBoxController::class, 'checkFeasibility'])->name('nap-boxes.check-feasibility');
    Route::get('nap-boxes/{napBox}/ports', [NapBoxController::class, 'ports'])->name('nap-boxes.ports');
    Route::post('nap-boxes/{napBox}/assign-port', [NapBoxController::class, 'assignPort'])->name('nap-boxes.assign-port');
    Route::post('nap-ports/{napPort}/release', [NapBoxController::class, 'releasePort'])->name('nap-ports.release');
    Route::patch('nap-ports/{napPort}/status', [NapBoxController::class, 'updatePortStatus'])->name('nap-ports.update-status');
    Route::resource('nap-boxes', NapBoxController::class);

    // Fiber Routes
    Route::get('fiber-routes/geojson', [FiberRouteController::class, 'geoJson'])->name('fiber-routes.geojson');
    Route::resource('fiber-routes', FiberRouteController::class);
});
