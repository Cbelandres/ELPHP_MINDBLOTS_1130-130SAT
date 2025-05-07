<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    /**
     * List all campaigns
     */
    public function index()
    {
        try {
            $campaigns = $this->fetchAllCampaigns();
            return $this->sendSuccessResponse('Campaigns retrieved successfully', ['campaigns' => $campaigns]);
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Failed to fetch campaigns', 'Unable to retrieve campaign data. Please try again.');
        }
    }

    /**
     * Get campaign details
     */
    public function show($id)
    {
        try {
            $campaign = $this->fetchCampaignById($id);
            return $this->sendSuccessResponse('Campaign retrieved successfully', ['campaign' => $campaign]);
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Campaign not found', 'The requested campaign does not exist.', 404);
        }
    }

    /**
     * Create new campaign
     */
    public function store(Request $request)
    {
        if (!$this->isFarmer($request->user())) {
            return $this->sendErrorResponse('Unauthorized', 'Only farmers can create campaigns.', 403);
        }

        $validationRules = [
            'project_name' => 'required|string|max:255',
            'project_description' => 'required|string',
            'project_capital' => 'required|numeric|min:0',
            'project_duration' => 'required|integer|min:1',
            'project_location' => 'required|string|max:255',
            'project_benefits' => 'required|string',
            'project_risks' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return $this->sendErrorResponse('Validation failed', $validator->errors(), 422);
        }

        try {
            $project = $this->createProject($request);
            $campaign = $this->createCampaign($project, $request);

            return $this->sendSuccessResponse(
                'Campaign created successfully',
                ['campaign' => $campaign->load('project')],
                201
            );
        } catch (\Exception $e) {
            $this->logError('Campaign creation failed', $e);
            return $this->sendErrorResponse('Failed to create campaign', $e->getMessage());
        }
    }

    /**
     * Approve campaign
     */
    public function approve($id)
    {
        if (!$this->isAdmin()) {
            return $this->sendErrorResponse('Unauthorized', 'Only admins can approve campaigns.', 403);
        }

        try {
            $campaign = $this->fetchCampaignById($id);
            
            if ($this->isCampaignActive($campaign)) {
                return $this->sendErrorResponse('Invalid operation', 'Campaign is already approved.', 400);
            }

            $this->updateCampaignStatus($campaign, 'active');

            return $this->sendSuccessResponse('Campaign approved successfully', ['campaign' => $campaign]);
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Failed to approve campaign', 'Unable to approve campaign. Please try again.');
        }
    }

    /**
     * Reject campaign
     */
    public function reject($id)
    {
        if (!$this->isAdmin()) {
            return $this->sendErrorResponse('Unauthorized', 'Only admins can reject campaigns.', 403);
        }

        try {
            $campaign = $this->fetchCampaignById($id);
            
            if ($this->isCampaignRejected($campaign)) {
                return $this->sendErrorResponse('Invalid operation', 'Campaign is already rejected.', 400);
            }

            $this->updateCampaignStatus($campaign, 'rejected');

            return $this->sendSuccessResponse('Campaign rejected successfully', ['campaign' => $campaign]);
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Failed to reject campaign', 'Unable to reject campaign. Please try again.');
        }
    }

    /**
     * Fund campaign
     */
    public function fund(Request $request, $id)
    {
        if (!$this->isInvestor()) {
            return $this->sendErrorResponse('Unauthorized', 'Only investors can fund campaigns.', 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse('Validation failed', $validator->errors(), 422);
        }

        try {
            $campaign = $this->fetchCampaignById($id);

            if (!$this->canBeFunded($campaign)) {
                return $this->sendErrorResponse('Invalid operation', 'Campaign cannot be funded at this time.', 403);
            }

            $investment = $this->createInvestment($campaign, $request->amount);

            return $this->sendSuccessResponse(
                'Campaign funded successfully',
                [
                    'investment' => $investment,
                    'campaign' => $campaign->load('investments')
                ]
            );
        } catch (\Exception $e) {
            $this->logError('Campaign funding failed', $e);
            return $this->sendErrorResponse('Failed to fund campaign', $e->getMessage());
        }
    }

    /**
     * Helper methods
     */
    private function fetchAllCampaigns()
    {
        return Campaign::with(['project', 'investments'])->get();
    }

    private function fetchCampaignById($id)
    {
        return Campaign::with(['project', 'investments'])->findOrFail($id);
    }

    private function isFarmer($user)
    {
        return $user->role === 'farmer';
    }

    private function isAdmin()
    {
        return auth()->user()->role === 'admin';
    }

    private function isInvestor()
    {
        return auth()->user()->role === 'investor';
    }

    private function createProject(Request $request)
    {
        $project = Project::create([
            'name' => $request->project_name,
            'description' => $request->project_description,
            'location' => $request->project_location,
            'capital_needed' => $request->project_capital,
            'duration_months' => $request->project_duration,
            'benefits' => $request->project_benefits,
            'risks' => $request->project_risks,
            'farmer_id' => $request->user()->id,
        ]);

        if (!$project) {
            throw new \Exception('Failed to create project');
        }

        return $project;
    }

    private function createCampaign($project, Request $request)
    {
        $campaign = Campaign::create([
            'project_id' => $project->id,
            'target_amount' => $request->project_capital,
            'start_date' => now(),
            'end_date' => now()->addMonths($request->project_duration),
            'status' => 'pending',
        ]);

        if (!$campaign) {
            throw new \Exception('Failed to create campaign');
        }

        return $campaign;
    }

    private function isCampaignActive($campaign)
    {
        return $campaign->status === 'active';
    }

    private function isCampaignRejected($campaign)
    {
        return $campaign->status === 'rejected';
    }

    private function updateCampaignStatus($campaign, $status)
    {
        $campaign->status = $status;
        $campaign->save();
    }

    private function canBeFunded($campaign)
    {
        return $campaign->status === 'active' && !now()->gt($campaign->end_date);
    }

    private function createInvestment($campaign, $amount)
    {
        $investment = Investment::create([
            'campaign_id' => $campaign->id,
            'investor_id' => auth()->id(),
            'amount' => $amount,
        ]);

        if (!$investment) {
            throw new \Exception('Failed to create investment record');
        }

        return $investment;
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