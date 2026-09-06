<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonitorLog;
use App\Models\ExamSession;

class MonitoringController extends Controller
{
    /**
     * Find a session that the authenticated
     * Faculty/Admin is allowed to access.
     */
    private function findAccessibleSession($sessionId)
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        $query = ExamSession::with('exam')
            ->where('id', $sessionId);

        if ($user->role !== 'admin') {
            $query->whereHas(
                'exam',
                function ($examQuery) use ($user) {
                    $examQuery->where(
                        'created_by',
                        $user->id
                    );
                }
            );
        }

        return $query->first();
    }

    /**
     * Log student monitoring activity.
     */
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

        if ($session->status === 'submitted') {
            return response()->json([
                'status' => false,
                'message' =>
                    'The examination is already submitted.',
            ], 409);
        }

        $activity = strtolower(
            trim($validated['activity'])
        );

        /**
         * Activities currently supported:
         *
         * tab_switch
         * copy_attempt
         * paste_attempt
         * cut_attempt
         * right_click
         * fullscreen_exit
         * idle
         * window_blur
         * developer_tools_attempt
         */
        $log = MonitorLog::create([
            'exam_session_id' =>
                $session->id,
            'student_id' => null,
            'activity' =>
                $activity,
        ]);

        /**
         * Only an actual tab switch should
         * increase the tab_switches column.
         *
         * Copy, paste and fullscreen exit
         * are counted from monitor_logs.
         */
        if ($activity === 'tab_switch') {
            $session->increment(
                'tab_switches'
            );
        }

        /**
         * Update idle time.
         */
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
            'message' =>
                'Activity logged successfully.',
            'data' => [
                'log' => $log,
                'session' =>
                    $session->fresh(),
                'violations' =>
                    $this->getViolationCounts(
                        $session->id
                    ),
            ],
        ]);
    }

    /**
     * Update live student status.
     */
    public function updateSessionStatus(
        Request $request
    ) {
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

        if (
            $session->status ===
            'submitted'
        ) {
            return response()->json([
                'status' => false,
                'message' =>
                    'The examination is already submitted.',
            ], 409);
        }

        $session->update([
            'current_question' =>
                $validated[
                    'current_question'
                ],
            'progress' =>
                round(
                    (float) $validated[
                        'progress'
                    ]
                ),
            'idle_seconds' =>
                $validated[
                    'idle_seconds'
                ],
            'time_remaining' =>
                $validated[
                    'time_remaining'
                ],
            'last_seen_at' =>
                now(),
        ]);

        return response()->json([
            'status' => true,
            'message' =>
                'Live monitoring updated.',
            'data' =>
                $session->fresh(),
        ]);
    }

    /**
     * Get all monitoring logs for
     * a specific exam session.
     */
    public function getLogs($sessionId)
    {
        $session =
            $this->findAccessibleSession(
                $sessionId
            );

        if (!$session) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Session not found or access denied.',
            ], 404);
        }

        $logs = MonitorLog::where(
            'exam_session_id',
            $session->id
        )
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $logs,
            'violations' =>
                $this->getViolationCounts(
                    $session->id
                ),
        ]);
    }

    /**
     * Count each violation type.
     */
    private function getViolationCounts(
        $sessionId
    ) {
        $counts = MonitorLog::where(
            'exam_session_id',
            $sessionId
        )
            ->selectRaw(
                'activity, COUNT(*) as total'
            )
            ->groupBy('activity')
            ->pluck(
                'total',
                'activity'
            );

        return [
            'tab_switches' =>
                (int) (
                    $counts[
                        'tab_switch'
                    ] ?? 0
                ),
            'copy_attempts' =>
                (int) (
                    $counts[
                        'copy_attempt'
                    ] ?? 0
                ),
            'paste_attempts' =>
                (int) (
                    $counts[
                        'paste_attempt'
                    ] ?? 0
                ),
            'fullscreen_exits' =>
                (int) (
                    $counts[
                        'fullscreen_exit'
                    ] ?? 0
                ),
            'cut_attempts' =>
                (int) (
                    $counts[
                        'cut_attempt'
                    ] ?? 0
                ),
            'right_click_attempts' =>
                (int) (
                    $counts[
                        'right_click'
                    ] ?? 0
                ),
            'developer_tools_attempts' =>
                (int) (
                    $counts[
                        'developer_tools_attempt'
                    ] ?? 0
                ),
            'window_blurs' =>
                (int) (
                    $counts[
                        'window_blur'
                    ] ?? 0
                ),
        ];
    }
}
