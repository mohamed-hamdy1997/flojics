<?php

use App\Http\Controllers\Api\TicketEscalationController;
use Illuminate\Support\Facades\Route;

Route::post('/tickets/{ticket}/escalate', TicketEscalationController::class)->name('api.tickets.escalate');
