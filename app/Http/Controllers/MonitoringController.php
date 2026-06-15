<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonitorLog;

class MonitoringController extends Controller
{
    public function logActivity(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required',
            'activity' => 'required'
        ]);

        $log = MonitorLog::create([
            'exam_session_id' => $request->exam_session_id,
            'student_id' => auth()->id(),
            'activity' => $request->activity
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Activity logged',
            'data' => $log
        ]);
    }

    public function getLogs($session_id)
    {
        $logs = MonitorLog::where(
            'exam_session_id',
            $session_id
        )->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $logs
        ]);
    }
}
