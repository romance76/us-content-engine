<?php

use App\Http\Controllers\Api\IngestArticleController;
use App\Http\Controllers\Api\IngestKeywordController;
use Illuminate\Support\Facades\Route;

// Authenticated by a shared bearer token (INGEST_API_TOKEN in .env), not
// Sanctum sessions — this is called by an external automation pipeline,
// not a browser.
Route::middleware('ingest.token')->prefix('ingest')->group(function () {
    Route::post('/articles', [IngestArticleController::class, 'store']);
    Route::get('/keywords', [IngestKeywordController::class, 'index']);
    Route::post('/keywords', [IngestKeywordController::class, 'store']);
});
