<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminFacultyController extends Controller
{
    public function index()
    {
        $faculty = User::where(
            'role',
            'faculty'
        )
        ->orderBy('name')
        ->get([
            'id',
            'name',
            'email',
            'role',
            'status',
            'created_at'
        ]);

        return response()->json([
            'status' => true,
            'data' => $faculty
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' =>
                'required|string|max:255',

            'email' =>
                'required|email|unique:users,email',

            'password' =>
                'required|string|min:6',
        ]);

        $faculty = User::create([
            'name' =>
                $validated['name'],

            'email' =>
                $validated['email'],

            'password' =>
                Hash::make(
                    $validated['password']
                ),

            'role' =>
                'faculty',
        ]);

        return response()->json([
            'status' => true,
            'message' =>
                'Faculty account created successfully.',
            'data' =>
                $faculty
        ], 201);
    }


    public function update(
        Request $request,
        $id
    ) {
        $faculty = User::where(
            'id',
            $id
        )
        ->where(
            'role',
            'faculty'
        )
        ->first();

        if (!$faculty) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Faculty account not found.'
            ], 404);
        }

        $validated = $request->validate([
            'name' =>
                'required|string|max:255',

            'email' =>
                'required|email|unique:users,email,'
                . $faculty->id,

            'password' =>
                'nullable|string|min:6',
        ]);

        $faculty->name =
            $validated['name'];

        $faculty->email =
            $validated['email'];

        if (
            !empty(
                $validated['password']
            )
        ) {
            $faculty->password =
                Hash::make(
                    $validated['password']
                );
        }

        $faculty->save();

        return response()->json([
            'status' => true,
            'message' =>
                'Faculty account updated successfully.',
            'data' =>
                $faculty
        ]);
    }


    public function destroy($id)
    {
        $faculty = User::where(
            'id',
            $id
        )
        ->where(
            'role',
            'faculty'
        )
        ->first();

        if (!$faculty) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Faculty account not found.'
            ], 404);
        }

        $faculty->delete();

        return response()->json([
            'status' => true,
            'message' =>
                'Faculty account deleted successfully.'
        ]);
    }
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ]);

        $faculty = User::where('id', $id)
            ->where('role', 'faculty')
            ->first();

        if (!$faculty) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Faculty account not found.',
            ], 404);
        }

        $faculty->status =
            $validated['status'];

        $faculty->save();

        /*
        * If faculty is deactivated,
        * revoke all existing Sanctum tokens.
        */
        if (
            $validated['status'] === 'inactive'
        ) {
            $faculty->tokens()->delete();
        }

        return response()->json([
            'status' => true,

            'message' =>
                $validated['status'] === 'active'
                    ? 'Faculty account activated successfully.'
                    : 'Faculty account deactivated successfully.',

            'data' => $faculty,
        ]);
    }
}
