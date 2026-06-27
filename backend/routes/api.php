<?php

use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketReplyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tickets', TicketController::class);

    // Nested replies
    Route::get('tickets/{ticket}/replies', [TicketReplyController::class, 'index']);
    Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store']);
});
