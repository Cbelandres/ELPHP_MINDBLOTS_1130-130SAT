<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function createAdmin(Request $request)
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

        try {
            $admin = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'admin',
            ]);

            return response()->json([
                'message' => 'Admin user created successfully',
                'admin' => $admin
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating admin user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listAdmins()
    {
        $admins = User::where('role', 'admin')->get();
        return response()->json(['admins' => $admins]);
    }
} 