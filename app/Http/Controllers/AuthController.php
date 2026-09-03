<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Helpers\AuditLogger;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' =>
                'required|string|max:255',

            'email' =>
                'required|email|unique:users,email',

            'password' =>
                'required|min:6',
        ]);

        $user = User::create([
            'name' =>
                $request->name,

            'email' =>
                $request->email,

            'role' =>
                'faculty',

            'password' =>
                Hash::make(
                    $request->password
                ),
        ]);

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return response()->json([
            'message' =>
                'User registered successfully',

            'token' =>
                $token,

            'user' =>
                $user,
        ], 201);
    }


    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' =>
                'required|email',

            'password' =>
                'required'
        ]);

        // Find account
        $user = User::where(
            'email',
            $request->email
        )->first();


        // Check credentials
        if (
            !$user ||
            !Hash::check(
                $request->password,
                $user->password
            )
        ) {

            throw ValidationException::withMessages([
                'email' => [
                    'Invalid email or password'
                ]
            ]);
        }


        // BLOCK INACTIVE FACULTY
        if (
            $user->role === 'faculty' &&
            $user->status !== 'active'
        ) {

            return response()->json([
                'message' =>
                    'Your account has been deactivated. Please contact the administrator.'
            ], 403);
        }


        // Only allowed roles
        if (
            !in_array(
                $user->role,
                ['faculty', 'admin']
            )
        ) {

            return response()->json([
                'message' =>
                    'Unauthorized account role.'
            ], 403);
        }


        // Create token after successful checks
        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;


        // =====================================
        // AUDIT LOG - LOGIN
        // =====================================

        AuditLogger::log(
            'LOGIN',
            'Authentication',
            ucfirst($user->role) .
                ' logged into the system.',
            $user
        );


        return response()->json([
            'message' =>
                'Login successful',

            'token' =>
                $token,

            'user' =>
                $user
        ]);
    }


    // LOGOUT
    public function logout(Request $request)
    {
        $user = $request->user();


        // =====================================
        // AUDIT LOG - LOGOUT
        // Must happen BEFORE deleting token
        // =====================================

        AuditLogger::log(
            'LOGOUT',
            'Authentication',
            ucfirst($user->role) .
                ' logged out of the system.',
            $user
        );


        // Delete tokens after logging activity
        $user
            ->tokens()
            ->delete();


        return response()->json([
            'message' =>
                'Logged out successfully'
        ]);
    }
}
