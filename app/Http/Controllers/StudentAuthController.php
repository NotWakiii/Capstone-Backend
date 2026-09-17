<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class StudentAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'lrn' => 'required|string',
        ]);

        $student = User::where('email', $validated['email'])
            ->where('lrn', $validated['lrn'])
            ->where('role', 'student')
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or LRN.',
            ], 401);
        }

        if ($student->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your student account is inactive.',
            ], 403);
        }

        $student->tokens()->delete();

        $token = $student->createToken('student-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'data' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'lrn' => $student->lrn,
                'role' => $student->role,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $student = $request->user();

        if (!$student || $student->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'lrn' => $student->lrn,
                'role' => $student->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
