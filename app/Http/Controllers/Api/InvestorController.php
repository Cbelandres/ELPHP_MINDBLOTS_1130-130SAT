<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InvestorController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'investor',
        ]);

        return response()->json([
            'message' => 'Investor registered successfully',
            'user' => $user
        ], 201);
    }

    public function index()
    {
        $investors = User::where('role', 'investor')->get();
        return response()->json(['investors' => $investors]);
    }

    public function show($id)
    {
        $investor = User::where('role', 'investor')->findOrFail($id);
        return response()->json(['investor' => $investor]);
    }
} 