<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\FarmerController;
use App\Http\Controllers\API\InvestorController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CampaignController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\ReportController;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('farmers/register', [FarmerController::class, 'register']);
Route::post('investors/register', [InvestorController::class, 'register']);
Route::post('admin/create', [AdminController::class, 'createAdmin']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Common routes for all authenticated users
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('campaigns', [CampaignController::class, 'index']);
    Route::get('campaigns/{id}', [CampaignController::class, 'show']);

    // Farmer routes
    Route::middleware([\App\Http\Middleware\CheckRole::class.':farmer'])->group(function () {
        Route::post('campaigns', [CampaignController::class, 'store']);
        Route::get('farmer/campaigns/report', [ReportController::class, 'farmerCampaignsReport']);
    });

    // Admin routes
    Route::middleware([\App\Http\Middleware\CheckRole::class.':admin'])->group(function () {
        Route::post('campaigns/{id}/approve', [CampaignController::class, 'approve']);
        Route::post('campaigns/{id}/reject', [CampaignController::class, 'reject']);
        Route::get('admin/list', [AdminController::class, 'listAdmins']);
        Route::get('admin/dashboard', [ReportController::class, 'adminDashboard']);
    });

    // Investor routes
    Route::middleware([\App\Http\Middleware\CheckRole::class.':investor'])->group(function () {
        Route::post('campaigns/{id}/fund', [CampaignController::class, 'fund']);
        Route::get('investor/dashboard', [ReportController::class, 'investorDashboard']);
    });
}); 