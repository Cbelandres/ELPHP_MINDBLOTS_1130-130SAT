<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class FarmerController extends Controller
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
            'role' => 'farmer',
        ]);

        return response()->json([
            'message' => 'Farmer registered successfully',
            'user' => $user
        ], 201);
    }

    public function index()
    {
        $farmers = User::where('role', 'farmer')->get();
        return response()->json(['farmers' => $farmers]);
    }

    public function show($id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);
        return response()->json(['farmer' => $farmer]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_fname' => 'required|string|max:30',
            'farmer_lname' => 'required|string|max:30',
            'farmer_contact' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $farmer = User::create($request->all());
        return response()->json($farmer, 201);
    }

    public function update(Request $request, User $farmer)
    {
        $validator = Validator::make($request->all(), [
            'farmer_fname' => 'required|string|max:30',
            'farmer_lname' => 'required|string|max:30',
            'farmer_contact' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $farmer->update($request->all());
        return response()->json($farmer);
    }

    public function destroy(User $farmer)
    {
        $farmer->delete();
        return response()->json(null, 204);
    }
} 