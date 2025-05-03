<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    public function index()
    {
        try {
            $campaigns = Campaign::with(['project', 'investments'])->get();
            return response()->json(['campaigns' => $campaigns]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch campaigns',
                'error' => 'Unable to retrieve campaign data. Please try again.'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $campaign = Campaign::with(['project', 'investments'])->findOrFail($id);
            return response()->json(['campaign' => $campaign]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Campaign not found',
                'error' => 'The requested campaign does not exist.'
            ], 404);
        }
    }

    public function store(Request $request)
    {
        // Check if user is a farmer
        if ($request->user()->role !== 'farmer') {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Only farmers can create campaigns.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string|max:255',
            'project_description' => 'required|string',
            'project_capital' => 'required|numeric|min:0',
            'project_duration' => 'required|integer|min:1',
            'project_location' => 'required|string|max:255',
            'project_benefits' => 'required|string',
            'project_risks' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create the project
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

            // Create the campaign
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

            return response()->json([
                'message' => 'Campaign created successfully',
                'campaign' => $campaign->load('project')
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Campaign creation failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approve($id)
    {
        // Check if user is an admin
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Only admins can approve campaigns.'
            ], 403);
        }

        try {
            $campaign = Campaign::findOrFail($id);
            
            if ($campaign->status === 'active') {
                return response()->json([
                    'message' => 'Invalid operation',
                    'error' => 'Campaign is already approved.'
                ], 400);
            }

            $campaign->status = 'active';
            $campaign->save();

            return response()->json([
                'message' => 'Campaign approved successfully',
                'campaign' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve campaign',
                'error' => 'Unable to approve campaign. Please try again.'
            ], 500);
        }
    }

    public function reject($id)
    {
        // Check if user is an admin
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Only admins can reject campaigns.'
            ], 403);
        }

        try {
            $campaign = Campaign::findOrFail($id);
            
            if ($campaign->status === 'rejected') {
                return response()->json([
                    'message' => 'Invalid operation',
                    'error' => 'Campaign is already rejected.'
                ], 400);
            }

            $campaign->status = 'rejected';
            $campaign->save();

            return response()->json([
                'message' => 'Campaign rejected successfully',
                'campaign' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject campaign',
                'error' => 'Unable to reject campaign. Please try again.'
            ], 500);
        }
    }

    public function fund(Request $request, $id)
    {
        // Check if user is an investor
        if (auth()->user()->role !== 'investor') {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Only investors can fund campaigns.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $campaign = Campaign::findOrFail($id);

            if ($campaign->status !== 'active') {
                return response()->json([
                    'message' => 'Invalid operation',
                    'error' => 'Only active campaigns can be funded.'
                ], 403);
            }

            if (now()->gt($campaign->end_date)) {
                return response()->json([
                    'message' => 'Invalid operation',
                    'error' => 'Campaign has ended.'
                ], 403);
            }

            // Create the investment
            $investment = Investment::create([
                'campaign_id' => $campaign->id,
                'investor_id' => auth()->id(),
                'amount' => $request->amount,
            ]);

            if (!$investment) {
                throw new \Exception('Failed to create investment record');
            }

            return response()->json([
                'message' => 'Campaign funded successfully',
                'investment' => $investment,
                'campaign' => $campaign->load('investments')
            ]);
        } catch (\Exception $e) {
            \Log::error('Campaign funding failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to fund campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 