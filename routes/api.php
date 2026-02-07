<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Application\ApplicationController;
use App\Http\Controllers\Chainsaw\ChainsawController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Dashboard\RPSChiefDashboardController;
use App\Http\Controllers\Routing\RoutingController;
use App\Http\Controllers\Reports\PDFController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('api')->group(function () {

Route::get('/getProvinces', [ApplicationController::class, 'getProvinces']);
Route::get('/provinces/{provinceId}/cities', [ApplicationController::class, 'getCitiesByProvince']);
Route::get('/barangays', [ApplicationController::class, 'getBarangays']);
Route::get('/generateApplicationNumber', [ApplicationController::class, 'generateApplicationNumber']);
Route::get('/application-details', [ApplicationController::class,'showApplicationDetails']);
Route::get('/getApplicationDetails/{application_id}',[ApplicationController::class,'getApplicationDetails']);
Route::get('/getApplicantFile/{application_id}',[ApplicationController::class,'getApplicantFile']);
Route::get('/getSignatories',[RPSChiefDashboardController::class,'getSignatories']);
Route::get('/getSignatories/{id}',[RPSChiefDashboardController::class,'getSignatories']);

Route::get('/applicationStatus', [RPSChiefDashboardController::class,'getApplicationsByStatus']);
Route::get( '/chainsaw/{applicationId}/brands', [ChainsawController::class, 'getChainsawBrandsWithModels'] );

Route::post('/chainsaw/apply', [ApplicationController::class, 'apply']);
Route::post('/chainsaw/company_apply', [ApplicationController::class, 'company_apply']);
Route::post('/chainsaw/insertChainsawInfo', [ChainsawController::class,'insertChainsawInfo']);
Route::post('chainsaw/insert_payment', [PaymentController::class,'insert_payment']);
Route::post('/files/update',[ApplicationController::class,'updateApplicantFiles']);

// Route::post('/{id}/generate-table-pdf', [PDFController::class, 'generateTable']);
Route::get('/application-routing/{id}', [RoutingController::class, 'show']);
Route::get('/getCommentsByID/{id}', [RoutingController::class,'getCommentsByID']);

Route::put('/updateApplicantDetails/{id}', [ChainsawController::class, 'updateApplicantDetails']);
Route::put('/updateChainsawInformation/{id}', [ChainsawController::class, 'updateChainsawInformation']);
Route::put('/updateApplicationStatus/{id}', [ChainsawController::class, 'updateApplicationStatus']);
});
