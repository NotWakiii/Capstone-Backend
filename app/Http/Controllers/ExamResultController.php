<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\MonitorLog;
use Illuminate\Http\Request;

class ExamResultController extends Controller
{
    /**
     * Show all exams that have submitted student results.
     */
    public function index(Request $request)
    {
        $faculty = $request->user();

        $exams = Exam::where('created_by', $faculty->id)
            ->whereHas('sessions', function ($query) {
                $query->where('status', 'submitted');
            })
            ->with([
                'sessions' => function ($query) {
                    $query->where('status', 'submitted');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($exam) {
                $sessions = $exam->sessions;

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'grade' => $exam->grade,
                    'section' => $exam->section,
                    'subject' => $exam->subject,
                    'access_code' => $exam->access_code,
                    'passing' => $exam->passing,
                    'status' => $exam->status,
                    'students_count' => $sessions->count(),
                    'average_score' => $sessions->count() > 0
                        ? round($sessions->avg('score'), 2)
                        : 0,
                    'average_percentage' => $sessions->count() > 0
                        ? round($sessions->avg('percentage'), 2)
                        : 0,
                    'highest_score' => $sessions->count() > 0
                        ? $sessions->max('score')
                        : 0,
                    'lowest_score' => $sessions->count() > 0
                        ? $sessions->min('score')
                        : 0,
                    'created_at' => $exam->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $exams
        ]);
    }

    /**
     * Show all student results for one exam.
     */
    public function show(Request $request, $examId)
    {
        $faculty = $request->user();

        $exam = Exam::where('id', $examId)
            ->where('created_by', $faculty->id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | GET SUBMITTED SESSIONS
        |--------------------------------------------------------------------------
        */
        $sessions = ExamSession::where('exam_id', $exam->id)
            ->where('status', 'submitted')
            ->orderBy('submitted_at', 'desc')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | GET VIOLATION COUNTS
        |--------------------------------------------------------------------------
        */
        $sessionIds = $sessions->pluck('id');

        $violationCounts = MonitorLog::whereIn(
            'exam_session_id',
            $sessionIds
        )
            ->whereIn(
                'activity',
                [
                    'copy_attempt',
                    'paste_attempt',
                    'fullscreen_exit',
                ]
            )
            ->selectRaw(
                'exam_session_id, activity, COUNT(*) as total'
            )
            ->groupBy(
                'exam_session_id',
                'activity'
            )
            ->get()
            ->groupBy('exam_session_id');

        /*
        |--------------------------------------------------------------------------
        | BUILD STUDENT RESULTS
        |--------------------------------------------------------------------------
        */
        $sessions = $sessions->map(
            function ($session) use (
                $exam,
                $violationCounts
            ) {
                $logs = $violationCounts->get(
                    $session->id,
                    collect()
                );

                $copyAttempts = (int) (
                    $logs
                        ->firstWhere(
                            'activity',
                            'copy_attempt'
                        )
                        ?->total ?? 0
                );

                $pasteAttempts = (int) (
                    $logs
                        ->firstWhere(
                            'activity',
                            'paste_attempt'
                        )
                        ?->total ?? 0
                );

                $fullscreenExits = (int) (
                    $logs
                        ->firstWhere(
                            'activity',
                            'fullscreen_exit'
                        )
                        ?->total ?? 0
                );

                return [
                    'id' => $session->id,
                    'student_name' => $session->student_name,
                    'score' => $session->score,
                    'percentage' => $session->percentage,
                    'passed' =>
                        (float) $session->percentage >=
                        (float) $exam->passing,
                    'started_at' => $session->started_at,
                    'submitted_at' => $session->submitted_at,
                    'time_spent' => $session->time_spent,
                    'tab_switches' =>
                        (int) ($session->tab_switches ?? 0),
                    'copy_attempts' =>
                        $copyAttempts,
                    'paste_attempts' =>
                        $pasteAttempts,
                    'fullscreen_exits' =>
                        $fullscreenExits,
                    'idle_seconds' =>
                        (int) ($session->idle_seconds ?? 0),
                    'status' => $session->status,
                ];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'success' => true,
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'grade' => $exam->grade,
                'section' => $exam->section,
                'subject' => $exam->subject,
                'access_code' => $exam->access_code,
                'passing' => $exam->passing,
            ],
            'summary' => [
                'students_count' => $sessions->count(),
                'highest_score' => $sessions->count() > 0
                    ? $sessions->max('score')
                    : 0,
                'lowest_score' => $sessions->count() > 0
                    ? $sessions->min('score')
                    : 0,
                'average_score' => $sessions->count() > 0
                    ? round($sessions->avg('score'), 2)
                    : 0,
                'average_percentage' => $sessions->count() > 0
                    ? round($sessions->avg('percentage'), 2)
                    : 0,
                'passed' => $sessions
                    ->where('passed', true)
                    ->count(),
                'failed' => $sessions
                    ->where('passed', false)
                    ->count(),
            ],
            'data' => $sessions
        ]);
    }

    /**
     * Show one student's detailed exam result and answers.
     */
    public function studentResult(Request $request, $sessionId)
    {
        $faculty = $request->user();

        $session = ExamSession::with([
            'exam',
            'answers.question'
        ])->findOrFail($sessionId);

        /*
         * Faculty must own the exam.
         */
        if (
            (int) $session->exam->created_by !==
            (int) $faculty->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | GET STUDENT VIOLATIONS
        |--------------------------------------------------------------------------
        */
        $violationCounts = MonitorLog::where(
            'exam_session_id',
            $session->id
        )
            ->whereIn(
                'activity',
                [
                    'copy_attempt',
                    'paste_attempt',
                    'fullscreen_exit',
                ]
            )
            ->selectRaw(
                'activity, COUNT(*) as total'
            )
            ->groupBy('activity')
            ->pluck('total', 'activity');

        return response()->json([
            'success' => true,
            'student' => [
                'name' => $session->student_name,
                'score' => $session->score,
                'percentage' => $session->percentage,
                'passed' =>
                    (float) $session->percentage >=
                    (float) $session->exam->passing,
                'started_at' => $session->started_at,
                'submitted_at' => $session->submitted_at,
                'time_spent' => $session->time_spent,
                'tab_switches' =>
                    (int) ($session->tab_switches ?? 0),
                'copy_attempts' =>
                    (int) ($violationCounts['copy_attempt'] ?? 0),
                'paste_attempts' =>
                    (int) ($violationCounts['paste_attempt'] ?? 0),
                'fullscreen_exits' =>
                    (int) ($violationCounts['fullscreen_exit'] ?? 0),
                'idle_seconds' =>
                    (int) ($session->idle_seconds ?? 0),
            ],
            'exam' => [
                'id' => $session->exam->id,
                'title' => $session->exam->title,
                'passing' => $session->exam->passing,
            ],
            'answers' => $session->answers->map(
                function ($answer) {
                    return [
                        'id' => $answer->id,
                        'question_id' => $answer->question_id,
                        'question' => $answer->question,
                        'answer' => $answer->answer,
                        'is_correct' =>
                            (bool) $answer->is_correct,
                    ];
                }
            )
        ]);
    }
}
