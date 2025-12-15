<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DisposalEventController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\BidderController;
use App\Http\Controllers\Admin\AssetManagementController;
use App\Http\Controllers\BidderRegistrationController;
use App\Http\Controllers\BiddingController;

Auth::routes();

Route::get('/', function () {
    return view('welcome');
});

// Redirect target used by Laravel auth after login/register
Route::get('/home', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('bidder.dashboard');
    }
    return redirect('/');
})->name('home');

// Admin Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('events', DisposalEventController::class);
    Route::post('events/{event}/publish', [DisposalEventController::class, 'publish'])->name('events.publish');
    Route::post('events/{event}/close', [DisposalEventController::class, 'close'])->name('events.close');
    Route::post('events/{event}/complete', [DisposalEventController::class, 'complete'])->name('events.complete');
    Route::post('events/{event}/notify-winners', [DisposalEventController::class, 'notifyWinners'])->name('events.notify-winners');
    Route::get('events/{event}/report', [DisposalEventController::class, 'report'])->name('events.report');

    Route::get('events/{event}/assets/create', [AssetController::class, 'create'])->name('assets.create');
    Route::post('events/{event}/assets', [AssetController::class, 'store'])->name('assets.store');
    Route::get('assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
    Route::put('assets/{asset}', [AssetController::class, 'update'])->name('assets.update');

    Route::get('bidders', [BidderController::class, 'index'])->name('bidders.index');
    Route::get('bidders/{bidder}', [BidderController::class, 'show'])->name('bidders.show');
    Route::post('bidders/{bidder}/approve', [BidderController::class, 'approve'])->name('bidders.approve');
    Route::post('bidders/{bidder}/reject', [BidderController::class, 'reject'])->name('bidders.reject');

    // Asset Management (Winners, Payment, Handover)
    Route::get('events/{event}/winners', [AssetManagementController::class, 'winners'])->name('assets.winners');
    Route::post('bids/{bid}/nullify', [AssetManagementController::class, 'nullifyBid'])->name('bids.nullify');
    Route::post('assets/{asset}/payment', [AssetManagementController::class, 'updatePayment'])->name('assets.payment');
    Route::post('assets/{asset}/handover', [AssetManagementController::class, 'recordHandover'])->name('assets.handover');
});

// Bidder Routes
Route::middleware(['auth'])->prefix('bidder')->name('bidder.')->group(function () {
    // Dashboard view file lives at resources/views/admin/bidders/dashboard.blade.php
    Route::get('/dashboard', function () {
        return view('admin.bidders.dashboard');
    })->name('dashboard');

    Route::get('/register', [BidderRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [BidderRegistrationController::class, 'store'])->name('register.store');

    Route::get('/events', [BiddingController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [BiddingController::class, 'show'])->name('events.show');
    Route::post('/assets/{asset}/bid', [BiddingController::class, 'placeBid'])->name('assets.bid');
    Route::get('/my-bids', [BiddingController::class, 'myBids'])->name('bids.index');
    Route::get('/my-winnings', [BiddingController::class, 'myWinnings'])->name('bids.winnings');
    Route::get('/assets/{asset}/bid-info', [BiddingController::class, 'assetBidInfo'])->name('assets.bid-info');
});
