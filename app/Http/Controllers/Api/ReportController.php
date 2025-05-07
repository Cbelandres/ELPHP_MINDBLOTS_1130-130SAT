<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Get admin dashboard data
     */
    public function adminDashboard()
    {
        try {
            $dashboardData = [
                'users' => $this->getUserStatistics(),
                'campaigns' => $this->getCampaignStatistics(),
                'total_funds' => $this->getTotalFunds(),
                'funds_by_status' => $this->getFundsByStatus()
            ];

            return $this->sendSuccessResponse('Admin dashboard data retrieved successfully', $dashboardData);
        } catch (\Exception $e) {
            $this->logError('Admin dashboard error', $e);
            return $this->sendErrorResponse('Failed to retrieve admin dashboard data', $e->getMessage());
        }
    }

    /**
     * Get admin campaigns report
     */
    public function adminCampaignsReport()
    {
        $campaigns = $this->getDetailedCampaigns();
        return $this->sendSuccessResponse('Campaigns report retrieved successfully', ['campaigns' => $campaigns]);
    }

    /**
     * Get farmer dashboard data
     */
    public function farmerDashboard(Request $request)
    {
        $farmer = $request->user();
        $dashboardData = [
            'total_campaigns' => $this->getFarmerCampaignCount($farmer),
            'total_investments' => $this->getFarmerTotalInvestments($farmer),
            'campaigns' => $this->getFarmerCampaigns($farmer)
        ];

        return $this->sendSuccessResponse('Farmer dashboard data retrieved successfully', $dashboardData);
    }

    /**
     * Get farmer campaign report
     */
    public function farmerCampaignReport(Request $request, $campaignId)
    {
        $campaign = $this->getFarmerCampaignDetails($request->user(), $campaignId);
        $investmentDetails = $this->getCampaignInvestmentDetails($campaign);

        return $this->sendSuccessResponse('Campaign report retrieved successfully', [
            'campaign' => $campaign,
            'investment_details' => $investmentDetails
        ]);
    }

    /**
     * Get farmer campaigns report
     */
    public function farmerCampaignsReport(Request $request)
    {
        try {
            $farmer = $request->user();
            
            if (!$this->isFarmer($farmer)) {
                return $this->sendErrorResponse('Unauthorized', 'Only farmers can access this report.', 403);
            }

            $campaigns = $this->getFarmerCampaignsWithDetails($farmer);
            $totalFunds = $this->calculateTotalFunds($campaigns);

            return $this->sendSuccessResponse('Farmer campaigns report retrieved successfully', [
                'total_campaigns' => $campaigns->count(),
                'total_funds_received' => $totalFunds,
                'campaigns' => $campaigns
            ]);
        } catch (\Exception $e) {
            $this->logError('Farmer campaigns report error', $e);
            return $this->sendErrorResponse('Failed to retrieve farmer campaigns report', $e->getMessage());
        }
    }

    /**
     * Get investor dashboard data
     */
    public function investorDashboard(Request $request)
    {
        try {
            $investor = $request->user();
            
            if (!$this->isInvestor($investor)) {
                return $this->sendErrorResponse('Unauthorized', 'Only investors can access this report.', 403);
            }

            $investments = $this->getInvestorInvestments($investor);

            return $this->sendSuccessResponse('Investor dashboard data retrieved successfully', [
                'total_investments' => $investments->count(),
                'total_amount_invested' => $investments->sum('amount'),
                'investments' => $investments
            ]);
        } catch (\Exception $e) {
            $this->logError('Investor dashboard error', $e);
            return $this->sendErrorResponse('Failed to retrieve investor dashboard data', $e->getMessage());
        }
    }

    /**
     * Helper methods
     */
    private function getUserStatistics()
    {
        return User::select('id', 'name', 'email', 'phone', 'role', 'created_at')
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
    }

    private function getCampaignStatistics()
    {
        return Campaign::with(['project', 'investments'])
            ->get()
            ->map(function ($campaign) {
                $totalFunds = $campaign->investments->sum('amount');
                $investorCount = $campaign->investments->count();
                $fundingProgress = $this->calculateFundingProgress($totalFunds, $campaign->target_amount);

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
                    'investments' => $this->formatInvestmentDetails($campaign->investments)
                ];
            });
    }

    private function getTotalFunds()
    {
        return Investment::sum('amount');
    }

    private function getFundsByStatus()
    {
        return Campaign::with('investments')
            ->get()
            ->groupBy('status')
            ->map(function ($campaigns) {
                return $campaigns->sum(function ($campaign) {
                    return $campaign->investments->sum('amount');
                });
            });
    }

    private function getDetailedCampaigns()
    {
        return Campaign::with(['farmer', 'investments', 'investors'])
            ->withCount('investments')
            ->withSum('investments', 'amount')
            ->get();
    }

    private function getFarmerCampaignCount($farmer)
    {
        return Campaign::where('farmer_id', $farmer->id)->count();
    }

    private function getFarmerTotalInvestments($farmer)
    {
        return Investment::whereHas('campaign', function($query) use ($farmer) {
            $query->where('farmer_id', $farmer->id);
        })->sum('amount');
    }

    private function getFarmerCampaigns($farmer)
    {
        return Campaign::where('farmer_id', $farmer->id)
            ->with(['investments', 'investors'])
            ->withSum('investments', 'amount')
            ->get();
    }

    private function getFarmerCampaignDetails($farmer, $campaignId)
    {
        return Campaign::where('farmer_id', $farmer->id)
            ->with(['investments', 'investors'])
            ->withSum('investments', 'amount')
            ->findOrFail($campaignId);
    }

    private function getCampaignInvestmentDetails($campaign)
    {
        return $campaign->investments()
            ->with('investor')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getFarmerCampaignsWithDetails($farmer)
    {
        return Campaign::whereHas('project', function($query) use ($farmer) {
                $query->where('farmer_id', $farmer->id);
            })
            ->with(['project', 'investments', 'investments.investor'])
            ->get()
            ->map(function ($campaign) {
                $totalFunds = $campaign->investments->sum('amount');
                $investorCount = $campaign->investments->count();
                $fundingProgress = $this->calculateFundingProgress($totalFunds, $campaign->target_amount);

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
                    'investments' => $this->formatInvestmentDetails($campaign->investments)
                ];
            });
    }

    private function getInvestorInvestments($investor)
    {
        return Investment::where('investor_id', $investor->id)
            ->with(['campaign.project', 'campaign.project.farmer'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($investment) {
                $campaign = $investment->campaign;
                $totalCampaignFunds = $campaign->investments->sum('amount');
                $fundingProgress = $this->calculateFundingProgress($totalCampaignFunds, $campaign->target_amount);

                return [
                    'investment_id' => $investment->id,
                    'amount' => $investment->amount,
                    'invested_at' => $investment->created_at->format('Y-m-d H:i:s'),
                    'campaign' => [
                        'id' => $campaign->id,
                        'project_name' => $campaign->project->name,
                        'farmer_name' => $campaign->project->farmer->name,
                        'target_amount' => $campaign->target_amount,
                        'funding_progress' => $fundingProgress . '%',
                        'status' => $campaign->status
                    ]
                ];
            });
    }

    private function calculateFundingProgress($totalFunds, $targetAmount)
    {
        return $targetAmount > 0 ? round(($totalFunds / $targetAmount) * 100, 2) : 0;
    }

    private function calculateTotalFunds($campaigns)
    {
        return $campaigns->sum(function ($campaign) {
            return $campaign['funding_details']['total_funds'];
        });
    }

    private function formatInvestmentDetails($investments)
    {
        return $investments->map(function ($investment) {
            return [
                'investor_name' => $investment->investor->name,
                'amount' => $investment->amount,
                'invested_at' => $investment->created_at->format('Y-m-d H:i:s')
            ];
        });
    }

    private function isFarmer($user)
    {
        return $user->role === 'farmer';
    }

    private function isInvestor($user)
    {
        return $user->role === 'investor';
    }

    private function logError($message, \Exception $e)
    {
        Log::error($message . ': ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
    }

    private function sendSuccessResponse($message, $data = [], $status = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $status);
    }

    private function sendErrorResponse($message, $error, $status = 500)
    {
        return response()->json([
            'message' => $message,
            'error' => $error
        ], $status);
    }
} 