<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $query->whereHas('exam', function ($examQuery) use ($user) {
                $examQuery->where('created_by', $user->id);
            });
        }

        return $query->first();
    }

    /**
     * Get the configured time penalty
     * for a monitoring activity.
     */
    private function getActivityPenalty(
        ExamSession $session,
        string $activity
    ): int {
        $exam = $session->exam;

        if (!$exam) {
            return 0;
        }

        return match ($activity) {
            'tab_switch' => max(
                0,
                (int) ($exam->tab_switch_penalty_seconds ?? 0)
            ),

            'fullscreen_exit' => max(
                0,
                (int) ($exam->fullscreen_exit_penalty_seconds ?? 0)
            ),

            'copy_attempt' => max(
                0,
                (int) ($exam->copy_attempt_penalty_seconds ?? 0)
            ),

            'paste_attempt' => max(
                0,
                (int) ($exam->paste_attempt_penalty_seconds ?? 0)
            ),

            'idle' => max(
                0,
                (int) ($exam->idle_penalty_seconds ?? 0)
            ),

            default => 0,
        };
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
            'details' => [
                'nullable',
                'string',
            ],
            'idle_seconds' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'duration_seconds' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $activity = strtolower(
            trim($validated['activity'])
        );

        $allowedActivities = [
            'tab_switch',
            'copy_attempt',
            'paste_attempt',
            'cut_attempt',
            'right_click',
            'fullscreen_exit',
            'idle',
            'window_blur',
            'developer_tools_attempt',
        ];

        if (!in_array($activity, $allowedActivities, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid monitoring activity.',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use (
                $validated,
                $activity
            ) {
                /**
                 * Lock the session while applying
                 * the violation and penalty.
                 *
                 * This prevents two monitoring
                 * requests from overwriting the
                 * accumulated penalty.
                 */
                /** @var ExamSession $session */
                $session = ExamSession::with('exam')
                    ->where(
                        'id',
                        $validated['exam_session_id']
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($session->status === 'submitted') {
                    return [
                        'submitted' => true,
                    ];
                }

                /**
                 * Store monitoring log.
                 */
                $log = MonitorLog::create([
                    'exam_session_id' => $session->id,
                    'student_id' => null,
                    'activity' => $activity,
                    'details' =>
                        $validated['details'] ?? null,
                    'duration_seconds' =>
                        $validated['duration_seconds'] ?? null,
                ]);

                /**
                 * Keep existing tab switch counter.
                 */
                if ($activity === 'tab_switch') {
                    $session->tab_switches =
                        (int) ($session->tab_switches ?? 0) + 1;
                }

                /**
                 * Update idle time.
                 */
                if ($activity === 'idle') {
                    $session->idle_seconds = max(
                        (int) (
                            $validated['idle_seconds'] ?? 30
                        ),
                        30
                    );
                }

                /**
                 * Determine how many seconds
                 * should be deducted for this
                 * violation.
                 */
                $penaltySeconds =
                    $this->getActivityPenalty(
                        $session,
                        $activity
                    );

                /**
                 * Add the penalty to the accumulated
                 * session penalty.
                 *
                 * NULL / 0 configured penalty means
                 * no deduction.
                 */
                if ($penaltySeconds > 0) {
                    $session->penalty_seconds =
                        (int) (
                            $session->penalty_seconds ?? 0
                        ) + $penaltySeconds;
                }

                $session->setAttribute('last_seen_at', now());
                $session->save();

                $session->refresh();
                $session->loadMissing('exam');

                return [
                    'submitted' => false,
                    'log' => $log,
                    'session' => $session,
                    'penalty_seconds' =>
                        $penaltySeconds,
                    'total_penalty_seconds' =>
                        (int) (
                            $session->penalty_seconds ?? 0
                        ),
                ];
            });

            if ($result['submitted'] ?? false) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'The examination is already submitted.',
                ], 409);
            }

            return response()->json([
                'status' => true,
                'message' =>
                    'Activity logged successfully.',

                /**
                 * Penalty caused by THIS activity.
                 */
                'penalty_seconds' =>
                    $result['penalty_seconds'],

                /**
                 * Total accumulated penalty for
                 * the whole exam session.
                 */
                'total_penalty_seconds' =>
                    $result['total_penalty_seconds'],

                'data' => [
                    'log' =>
                        $result['log'],

                    'session' =>
                        $result['session'],

                    'penalty_seconds' =>
                        $result['penalty_seconds'],

                    'total_penalty_seconds' =>
                        $result['total_penalty_seconds'],

                    'violations' =>
                        $this->getViolationCounts(
                            $result['session']->id
                        ),
                ],
            ]);
        } catch (\Throwable $error) {
            report($error);

            return response()->json([
                'status' => false,
                'message' =>
                    'Failed to log monitoring activity.',
            ], 500);
        }
    }

    /**
     * Update live student status.
     */
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
                'message' =>
                    'The examination is already submitted.',
            ], 409);
        }

        $session->update([
            'current_question' =>
                $validated['current_question'],

            'progress' => round(
                (float) $validated['progress']
            ),

            'idle_seconds' =>
                $validated['idle_seconds'],

            'time_remaining' =>
                $validated['time_remaining'],

            'last_seen_at' => now(),
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
        $session = $this->findAccessibleSession(
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

            'penalty_seconds' => (int) (
                $session->penalty_seconds ?? 0
            ),

            'violations' =>
                $this->getViolationCounts(
                    $session->id
                ),
        ]);
    }

    /**
     * Count violations and calculate
     * recorded away time.
     */
    private function getViolationCounts($sessionId)
    {
        $query = MonitorLog::where(
            'exam_session_id',
            $sessionId
        );

        $counts = (clone $query)
            ->selectRaw(
                'activity, COUNT(*) as total'
            )
            ->groupBy('activity')
            ->pluck(
                'total',
                'activity'
            );

        $tabSwitchSeconds = (int) (
            clone $query
        )
            ->where(
                'activity',
                'tab_switch'
            )
            ->sum('duration_seconds');

        $fullscreenExitSeconds = (int) (
            clone $query
        )
            ->where(
                'activity',
                'fullscreen_exit'
            )
            ->sum('duration_seconds');

        $totalAwaySeconds =
            $tabSwitchSeconds +
            $fullscreenExitSeconds;

        return [
            'tab_switches' => (int) (
                $counts['tab_switch'] ?? 0
            ),

            'copy_attempts' => (int) (
                $counts['copy_attempt'] ?? 0
            ),

            'paste_attempts' => (int) (
                $counts['paste_attempt'] ?? 0
            ),

            'fullscreen_exits' => (int) (
                $counts['fullscreen_exit'] ?? 0
            ),

            'idle_violations' => (int) (
                $counts['idle'] ?? 0
            ),

            'cut_attempts' => (int) (
                $counts['cut_attempt'] ?? 0
            ),

            'right_click_attempts' => (int) (
                $counts['right_click'] ?? 0
            ),

            'developer_tools_attempts' => (int) (
                $counts['developer_tools_attempt'] ?? 0
            ),

            'window_blurs' => (int) (
                $counts['window_blur'] ?? 0
            ),

            'tab_switch_seconds' =>
                $tabSwitchSeconds,

            'fullscreen_exit_seconds' =>
                $fullscreenExitSeconds,

            'total_away_seconds' =>
                $totalAwaySeconds,
        ];
    }
}
