<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketReplyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Tickets (OpenClaw)
    Route::apiResource('tickets', TicketController::class);

    // Nested ticket replies (OpenClaw)
    Route::get('tickets/{ticket}/replies', [TicketReplyController::class, 'index']);
    Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store']);

    // Org-scoped lookups (for frontend dropdowns)
    Route::get('users', [OrganizationController::class, 'users']);
    Route::get('tags', [OrganizationController::class, 'tags']);
});
