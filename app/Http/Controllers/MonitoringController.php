<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonitorLog;
use App\Models\ExamSession;

class MonitoringController extends Controller
{
    public function logActivity(Request $request)
    {
        $validated = $request->validate([
            'exam_session_id' => [
                'required',
                'integer',
                'exists:exam_sessions,id',
            ],

            'activity' => [
                'required',
                'string',
                'max:255',
            ],

            'idle_seconds' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $session = ExamSession::findOrFail(
            $validated['exam_session_id']
        );

        $activity = strtolower(
            trim($validated['activity'])
        );

        $log = MonitorLog::create([
            'exam_session_id' => $session->id,

            // Students are not authenticated Laravel users.
            'student_id' => null,

            'activity' => $activity,
        ]);

        if (
            in_array(
                $activity,
                [
                    'tab_switch',
                    'window_blur',
                    'developer_tools_attempt',
                ],
                true
            )
        ) {
            $session->increment('tab_switches');
        }

        if ($activity === 'idle') {
            $session->idle_seconds = max(
                (int) $request->input(
                    'idle_seconds',
                    30
                ),
                30
            );
        }

        $session->last_seen_at = now();
        $session->save();

        return response()->json([
            'status' => true,
            'message' => 'Activity logged successfully.',
            'data' => [
                'log' => $log,
                'session' => $session->fresh(),
            ],
        ]);
    }

    public function updateSessionStatus(Request $request)
    {
        $validated = $request->validate([
            'exam_session_id' => [
                'required',
                'integer',
                'exists:exam_sessions,id',
            ],

            'current_question' => [
                'required',
                'integer',
                'min:1',
            ],

            'progress' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'idle_seconds' => [
                'required',
                'integer',
                'min:0',
            ],

            'time_remaining' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $session = ExamSession::findOrFail(
            $validated['exam_session_id']
        );

        if ($session->status === 'submitted') {
            return response()->json([
                'status' => false,
                'message' => 'The examination is already submitted.',
            ], 409);
        }

        $session->update([
            'current_question' =>
                $validated['current_question'],

            'progress' =>
                round((float) $validated['progress']),

            'idle_seconds' =>
                $validated['idle_seconds'],

            'time_remaining' =>
                $validated['time_remaining'],

            'last_seen_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Live monitoring updated.',
            'data' => $session->fresh(),
        ]);
    }

    public function getLogs($sessionId)
    {
        $logs = MonitorLog::where(
            'exam_session_id',
            $sessionId
        )
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $logs,
        ]);
    }
}
