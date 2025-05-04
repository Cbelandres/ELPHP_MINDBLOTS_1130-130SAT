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
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:farmer,investor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role,
            ]);

            // Create the corresponding farmer or investor record
            if ($request->role === 'farmer') {
                $farmer = \App\Models\Farmer::create([
                    'farmer_fname' => $request->name,
                    'farmer_lname' => $request->name,
                    'farmer_contact' => $request->contact ?? '',
                ]);
                $user->userable()->associate($farmer);
            } else {
                $investor = \App\Models\Investor::create([
                    'investor_name' => $request->name,
                    'investor_contact_no' => $request->contact ?? '',
                    'investor_budget_range' => $request->budget_range ?? '0-0',
                    'investor_type' => $request->investor_type ?? 'individual',
                ]);
                $user->userable()->associate($investor);
            }

            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Registration successful',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('sanctum.expiration', 60 * 24 * 7)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => 'Unable to create user account. Please try again.'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials',
                'error' => 'The provided credentials are incorrect.'
            ], 401);
        }

        try {
            $user = User::where('email', $request->email)->first();
            
            // Revoke existing tokens
            $user->tokens()->delete();
            
            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('sanctum.expiration', 60 * 24 * 7)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => 'Unable to process login. Please try again.'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'message' => 'Logged out successfully',
                'info' => 'Your session has been terminated.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => 'Unable to terminate your session. Please try again.'
            ], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
} 