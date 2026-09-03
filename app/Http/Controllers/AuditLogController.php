<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        $query = AuditLog::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $logs
        ]);
    }
}
