<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets');

Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
