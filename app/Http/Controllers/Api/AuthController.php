<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:farmer,investor',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return $this->sendErrorResponse('Validation failed', $validator->errors(), 422);
        }

        try {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role,
            ];

            $user = $this->createUserWithRole($userData);
            $token = $this->generateAuthToken($user);

            return $this->sendSuccessResponse(
                'Registration successful',
                [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration', 60 * 24 * 7)
                ],
                201
            );
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Registration failed', 'Unable to create user account. Please try again.');
        }
    }

    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        $validationRules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return $this->sendErrorResponse('Validation failed', $validator->errors(), 422);
        }

        if (!$this->attemptLogin($request)) {
            return $this->sendErrorResponse('Invalid credentials', 'The provided credentials are incorrect.', 401);
        }

        try {
            $user = $this->getUserByEmail($request->email);
            $this->revokeExistingTokens($user);
            $token = $this->generateAuthToken($user);

            return $this->sendSuccessResponse(
                'Login successful',
                [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration', 60 * 24 * 7)
                ]
            );
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Login failed', 'Unable to process login. Please try again.');
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        try {
            $this->terminateUserSession($request->user());
            return $this->sendSuccessResponse('Logged out successfully', ['info' => 'Your session has been terminated.']);
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Logout failed', 'Unable to terminate your session. Please try again.');
        }
    }

    /**
     * Get authenticated user profile
     */
    public function me(Request $request)
    {
        return $this->sendSuccessResponse('User profile retrieved', $request->user());
    }

    /**
     * Create user with associated role
     */
    private function createUserWithRole(array $userData)
    {
        $user = User::create($userData);

        if ($userData['role'] === 'farmer') {
            $this->createFarmerProfile($user, $userData);
        } else {
            $this->createInvestorProfile($user, $userData);
        }

        $user->save();
        return $user;
    }

    /**
     * Create farmer profile
     */
    private function createFarmerProfile(User $user, array $userData)
    {
        $farmer = \App\Models\Farmer::create([
            'farmer_fname' => $userData['name'],
            'farmer_lname' => $userData['name'],
            'farmer_contact' => $userData['phone'] ?? '',
        ]);
        $user->userable()->associate($farmer);
    }

    /**
     * Create investor profile
     */
    private function createInvestorProfile(User $user, array $userData)
    {
        $investor = \App\Models\Investor::create([
            'investor_name' => $userData['name'],
            'investor_contact_no' => $userData['phone'] ?? '',
            'investor_budget_range' => '0-0',
            'investor_type' => 'individual',
        ]);
        $user->userable()->associate($investor);
    }

    /**
     * Attempt user login
     */
    private function attemptLogin(Request $request)
    {
        return Auth::attempt($request->only('email', 'password'));
    }

    /**
     * Get user by email
     */
    private function getUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    /**
     * Revoke existing tokens
     */
    private function revokeExistingTokens($user)
    {
        $user->tokens()->delete();
    }

    /**
     * Generate authentication token
     */
    private function generateAuthToken($user)
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * Terminate user session
     */
    private function terminateUserSession($user)
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Send success response
     */
    private function sendSuccessResponse($message, $data = [], $status = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Send error response
     */
    private function sendErrorResponse($message, $error, $status = 500)
    {
        return response()->json([
            'message' => $message,
            'error' => $error
        ], $status);
    }
} 