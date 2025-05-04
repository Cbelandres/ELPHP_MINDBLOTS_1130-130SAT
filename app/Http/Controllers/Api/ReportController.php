<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Admin Reports
    public function adminDashboard()
    {
        try {
            // Get all users with their details
            $users = User::select('id', 'name', 'email', 'phone', 'role', 'created_at')
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'phone' => $user->phone,
                                'role' => $user->role,
                                'joined_at' => $user->created_at->format('Y-m-d H:i:s'),
                                'total_campaigns' => $user->role === 'farmer' ? $user->projects->count() : 0,
                                'total_investments' => $user->role === 'investor' ? $user->investments->sum('amount') : 0
                            ];
                        });

            // Get all campaigns with their details and funds
            $campaigns = Campaign::with(['project', 'investments'])
                                ->get()
                                ->map(function ($campaign) {
                                    $totalFunds = $campaign->investments->sum('amount');
                                    $investorCount = $campaign->investments->count();
                                    $fundingProgress = $campaign->target_amount > 0 
                                        ? round(($totalFunds / $campaign->target_amount) * 100, 2)
                                        : 0;

                                    return [
                                        'id' => $campaign->id,
                                        'project_name' => $campaign->project->name,
                                        'farmer_name' => $campaign->project->farmer->name,
                                        'target_amount' => $campaign->target_amount,
                                        'start_date' => $campaign->start_date->format('Y-m-d'),
                                        'end_date' => $campaign->end_date->format('Y-m-d'),
                                        'status' => $campaign->status,
                                        'funding_details' => [
                                            'total_funds' => $totalFunds,
                                            'investor_count' => $investorCount,
                                            'funding_progress' => $fundingProgress . '%',
                                            'remaining_amount' => max(0, $campaign->target_amount - $totalFunds)
                                        ],
                                        'investments' => $campaign->investments->map(function ($investment) {
                                            return [
                                                'investor_name' => $investment->investor->name,
                                                'amount' => $investment->amount,
                                                'invested_at' => $investment->created_at->format('Y-m-d H:i:s')
                                            ];
                                        })
                                    ];
                                });

            // Get total funds raised across all campaigns
            $totalFunds = Investment::sum('amount');

            // Group funds by campaign status
            $fundsByStatus = Campaign::with('investments')
                                    ->get()
                                    ->groupBy('status')
                                    ->map(function ($campaigns) {
                                        return $campaigns->sum(function ($campaign) {
                                            return $campaign->investments->sum('amount');
                                        });
                                    });

            return response()->json([
                'message' => 'Admin dashboard data retrieved successfully',
                'data' => [
                    'users' => $users,
                    'campaigns' => $campaigns,
                    'total_funds' => $totalFunds,
                    'funds_by_status' => $fundsByStatus
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Admin dashboard error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to retrieve admin dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function adminCampaignsReport()
    {
        $campaigns = Campaign::with(['farmer', 'investments', 'investors'])
            ->withCount('investments')
            ->withSum('investments', 'amount')
            ->get();

        return response()->json(['campaigns' => $campaigns]);
    }

    // Farmer Reports
    public function farmerDashboard(Request $request)
    {
        $farmer = $request->user();
        
        $totalCampaigns = Campaign::where('farmer_id', $farmer->id)->count();
        $totalInvestments = Investment::whereHas('campaign', function($query) use ($farmer) {
            $query->where('farmer_id', $farmer->id);
        })->sum('amount');

        $campaigns = Campaign::where('farmer_id', $farmer->id)
            ->with(['investments', 'investors'])
            ->withSum('investments', 'amount')
            ->get();

        return response()->json([
            'total_campaigns' => $totalCampaigns,
            'total_investments' => $totalInvestments,
            'campaigns' => $campaigns,
        ]);
    }

    public function farmerCampaignReport(Request $request, $campaignId)
    {
        $campaign = Campaign::where('farmer_id', $request->user()->id)
            ->with(['investments', 'investors'])
            ->withSum('investments', 'amount')
            ->findOrFail($campaignId);

        $investmentDetails = $campaign->investments()
            ->with('investor')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'campaign' => $campaign,
            'investment_details' => $investmentDetails,
        ]);
    }

    public function farmerCampaignsReport(Request $request)
    {
        try {
            $farmer = $request->user();
            
            if ($farmer->role !== 'farmer') {
                return response()->json([
                    'message' => 'Unauthorized',
                    'error' => 'Only farmers can access this report.'
                ], 403);
            }

            $campaigns = Campaign::whereHas('project', function($query) use ($farmer) {
                    $query->where('farmer_id', $farmer->id);
                })
                ->with(['project', 'investments', 'investments.investor'])
                ->get()
                ->map(function ($campaign) {
                    $totalFunds = $campaign->investments->sum('amount');
                    $investorCount = $campaign->investments->count();
                    $fundingProgress = $campaign->target_amount > 0 
                        ? round(($totalFunds / $campaign->target_amount) * 100, 2)
                        : 0;

                    return [
                        'id' => $campaign->id,
                        'project_name' => $campaign->project->name,
                        'project_description' => $campaign->project->description,
                        'target_amount' => $campaign->target_amount,
                        'start_date' => $campaign->start_date->format('Y-m-d'),
                        'end_date' => $campaign->end_date->format('Y-m-d'),
                        'status' => $campaign->status,
                        'funding_details' => [
                            'total_funds' => $totalFunds,
                            'investor_count' => $investorCount,
                            'funding_progress' => $fundingProgress . '%',
                            'remaining_amount' => max(0, $campaign->target_amount - $totalFunds)
                        ],
                        'investments' => $campaign->investments->map(function ($investment) {
                            return [
                                'investor_name' => $investment->investor->name,
                                'amount' => $investment->amount,
                                'invested_at' => $investment->created_at->format('Y-m-d H:i:s')
                            ];
                        })
                    ];
                });

            $totalFunds = $campaigns->sum(function ($campaign) {
                return $campaign['funding_details']['total_funds'];
            });

            return response()->json([
                'message' => 'Farmer campaigns report retrieved successfully',
                'data' => [
                    'total_campaigns' => $campaigns->count(),
                    'total_funds_received' => $totalFunds,
                    'campaigns' => $campaigns
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Farmer campaigns report error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to retrieve farmer campaigns report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Investor Reports
    public function investorDashboard(Request $request)
    {
        try {
            $investor = $request->user();
            
            if ($investor->role !== 'investor') {
                return response()->json([
                    'message' => 'Unauthorized',
                    'error' => 'Only investors can access this report.'
                ], 403);
            }

            $investments = Investment::where('investor_id', $investor->id)
                ->with(['campaign.project', 'campaign.project.farmer'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($investment) {
                    $campaign = $investment->campaign;
                    $totalCampaignFunds = $campaign->investments->sum('amount');
                    $fundingProgress = $campaign->target_amount > 0 
                        ? round(($totalCampaignFunds / $campaign->target_amount) * 100, 2)
                        : 0;

                    return [
                        'investment_id' => $investment->id,
                        'amount' => $investment->amount,
                        'invested_at' => $investment->created_at->format('Y-m-d H:i:s'),
                        'campaign' => [
                            'id' => $campaign->id,
                            'project_name' => $campaign->project->name,
                            'project_description' => $campaign->project->description,
                            'farmer_name' => $campaign->project->farmer->name,
                            'target_amount' => $campaign->target_amount,
                            'start_date' => $campaign->start_date->format('Y-m-d'),
                            'end_date' => $campaign->end_date->format('Y-m-d'),
                            'status' => $campaign->status,
                            'funding_details' => [
                                'total_funds' => $totalCampaignFunds,
                                'investor_count' => $campaign->investments->count(),
                                'funding_progress' => $fundingProgress . '%',
                                'remaining_amount' => max(0, $campaign->target_amount - $totalCampaignFunds)
                            ]
                        ]
                    ];
                });

            $totalInvestments = $investments->sum('amount');
            $totalCampaigns = $investments->pluck('campaign.id')->unique()->count();

            return response()->json([
                'message' => 'Investor dashboard data retrieved successfully',
                'data' => [
                    'total_investments' => $totalInvestments,
                    'total_campaigns' => $totalCampaigns,
                    'investments' => $investments
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Investor dashboard error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to retrieve investor dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function investorCampaignReport(Request $request, $campaignId)
    {
        $investment = Investment::where('investor_id', $request->user()->id)
            ->where('campaign_id', $campaignId)
            ->with(['campaign', 'campaign.farmer'])
            ->firstOrFail();

        $campaign = $investment->campaign;
        $totalInvestments = $campaign->investments()->sum('amount');
        $totalInvestors = $campaign->investors()->count();

        return response()->json([
            'investment' => $investment,
            'campaign' => $campaign,
            'total_investments' => $totalInvestments,
            'total_investors' => $totalInvestors,
        ]);
    }
} 