<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//
//
Route::get('es/filter-parts', function () {
    $categoryId = 278;
    $codeString = null;
    $codeString = 'FDC63';

    $partFilterRequest = new \App\CommonSDK\ElasticSearch\PartFilter\Request('parts_v2', $categoryId, $codeString);

    return $partFilterRequest->getParts();
});

Route::get('es/filter-parts/attributes', function () {
    $categoryId = 278;
    $codeString = null;
    $codeString = 'FDC63';

    $partFilterRequest = new \App\CommonSDK\ElasticSearch\PartFilter\Request('parts_v2', $categoryId, $codeString);

    return $partFilterRequest->getAttributes();
});
