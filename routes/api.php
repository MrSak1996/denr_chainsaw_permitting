<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Application\ApplicationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::middleware('api')->group(function () {

Route::get('/getProvinces', [ApplicationController::class, 'getProvinces']);
Route::get('/provinces/{provinceId}/cities', [ApplicationController::class, 'getCitiesByProvince']);
Route::get('/barangays', [ApplicationController::class, 'getBarangays']);
Route::get('/generateApplicationNumber', [ApplicationController::class, 'generateApplicationNumber']);


Route::post('/chainsaw/apply', [ApplicationController::class, 'apply']);
Route::post('/chainsaw/company_apply', [ApplicationController::class, 'company_apply']);


});