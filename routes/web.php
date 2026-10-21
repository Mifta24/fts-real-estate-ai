<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HandoverController;
use App\Http\Controllers\Admin\KnowledgeItemController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\ViewingController as AdminViewingController;
use App\Http\Controllers\AdvisorChatController;
use App\Http\Controllers\AgencyPageController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MortgageController;
use App\Http\Controllers\ViewingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AgencyPageController::class, 'index'])->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('properties', PropertyController::class)->only('index');
        Route::resource('knowledge-items', KnowledgeItemController::class)->only('index');

        Route::middleware('can:manage-agency-content')->group(function () {
            Route::resource('properties', PropertyController::class)->except(['index', 'show']);
            Route::resource('knowledge-items', KnowledgeItemController::class)->except(['index', 'show']);
        });

        Route::get('viewings', [AdminViewingController::class, 'index'])->name('viewings.index');
        Route::patch('viewings/{viewing}/status', [AdminViewingController::class, 'updateStatus'])->name('viewings.status');

        Route::get('handovers', [HandoverController::class, 'index'])->name('handovers.index');
        Route::get('handovers/{handover}', [HandoverController::class, 'show'])->name('handovers.show');
        Route::post('handovers/{handover}/reply', [HandoverController::class, 'reply'])->name('handovers.reply');
        Route::post('handovers/{handover}/resolve', [HandoverController::class, 'resolve'])->name('handovers.resolve');
    });
});

Route::prefix('{agencySlug}')->group(function () {
    Route::get('/', [AgencyPageController::class, 'home'])->name('agency.home');
    Route::get('listings', [AgencyPageController::class, 'listings'])->name('agency.listings');
    Route::get('listings/{propertySlug}', [AgencyPageController::class, 'listing'])->name('agency.listing');
    Route::get('area', [AgencyPageController::class, 'area'])->name('agency.area');
    Route::get('area/{placeId}', [AgencyPageController::class, 'place'])->whereNumber('placeId')->name('agency.place');
    Route::get('guide', [AgencyPageController::class, 'info'])->name('agency.info');
    Route::get('agents', [AgencyPageController::class, 'agents'])->name('agency.agents');
    Route::get('viewing', [AgencyPageController::class, 'viewing'])->name('agency.viewing');

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('viewing/slots', [ViewingController::class, 'slots'])->name('viewing.slots');
        Route::post('viewing', [ViewingController::class, 'store'])->name('viewing.store');
        Route::post('mortgage', [MortgageController::class, 'simulate'])->name('mortgage.simulate');
    });

    Route::prefix('advisor')->name('advisor.')->group(function () {
        Route::post('start', [AdvisorChatController::class, 'start'])->middleware('throttle:advisor-start')->name('start');
        Route::post('message', [AdvisorChatController::class, 'message'])->middleware('throttle:advisor-message')->name('message');
        Route::get('history', [AdvisorChatController::class, 'history'])->middleware('throttle:advisor-history')->name('history');
    });
});
