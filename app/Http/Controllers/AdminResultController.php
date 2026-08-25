<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;

class AdminResultController extends Controller
{
    /**
     * Admin overview of all exam results.
     */
    public function index()
    {
        $exams = Exam::with([
            'user',
            'sessions' => function ($query) {
                $query->where(
                    'status',
                    'submitted'
                );
            }
        ])
        ->whereHas(
            'sessions',
            function ($query) {
                $query->where(
                    'status',
                    'submitted'
                );
            }
        )
        ->latest()
        ->get()
        ->map(function ($exam) {

            $sessions =
                $exam->sessions;

            $passed =
                $sessions
                    ->filter(
                        function ($session) use ($exam) {
                            return
                                (float)
                                $session->percentage
                                >=
                                (float)
                                $exam->passing;
                        }
                    )
                    ->count();

            $failed =
                $sessions->count()
                -
                $passed;

            return [
                'id' =>
                    $exam->id,

                'title' =>
                    $exam->title,

                'faculty' => [
                    'id' =>
                        $exam->user?->id,

                    'name' =>
                        $exam->user?->name
                        ?? 'Unknown Faculty',

                    'email' =>
                        $exam->user?->email,
                ],

                'grade' =>
                    $exam->grade,

                'section' =>
                    $exam->section,

                'subject' =>
                    $exam->subject,

                'passing' =>
                    $exam->passing,

                'students_count' =>
                    $sessions->count(),

                'passed' =>
                    $passed,

                'failed' =>
                    $failed,

                'average_score' =>
                    $sessions->count() > 0
                        ? round(
                            $sessions->avg(
                                'score'
                            ),
                            2
                        )
                        : 0,

                'average_percentage' =>
                    $sessions->count() > 0
                        ? round(
                            $sessions->avg(
                                'percentage'
                            ),
                            2
                        )
                        : 0,

                'highest_score' =>
                    $sessions->count() > 0
                        ? $sessions->max(
                            'score'
                        )
                        : 0,

                'lowest_score' =>
                    $sessions->count() > 0
                        ? $sessions->min(
                            'score'
                        )
                        : 0,

                'created_at' =>
                    $exam->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $exams,
        ]);
    }


    /**
     * Admin view of submitted students
     * for one examination.
     */
    public function show($examId)
    {
        $exam = Exam::with('user')
            ->find($examId);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Examination not found.',
            ], 404);
        }

        $sessions =
            ExamSession::where(
                'exam_id',
                $exam->id
            )
            ->where(
                'status',
                'submitted'
            )
            ->latest(
                'submitted_at'
            )
            ->get()
            ->map(
                function ($session) use ($exam) {

                    $percentage =
                        (float)
                        $session->percentage;

                    return [
                        'id' =>
                            $session->id,

                        'student_name' =>
                            $session->student_name,

                        'score' =>
                            $session->score,

                        'percentage' =>
                            $percentage,

                        'result' =>
                            $percentage
                            >=
                            (float)
                            $exam->passing
                                ? 'Passed'
                                : 'Failed',

                        'time_spent' =>
                            $session->time_spent,

                        'tab_switches' =>
                            $session->tab_switches,

                        'idle_seconds' =>
                            $session->idle_seconds,

                        'started_at' =>
                            $session->started_at,

                        'submitted_at' =>
                            $session->submitted_at,
                    ];
                }
            );

        return response()->json([
            'status' => true,

            'exam' => [
                'id' =>
                    $exam->id,

                'title' =>
                    $exam->title,

                'faculty' => [
                    'id' =>
                        $exam->user?->id,

                    'name' =>
                        $exam->user?->name
                        ?? 'Unknown Faculty',

                    'email' =>
                        $exam->user?->email,
                ],

                'grade' =>
                    $exam->grade,

                'section' =>
                    $exam->section,

                'subject' =>
                    $exam->subject,

                'passing' =>
                    $exam->passing,
            ],

            'summary' => [
                'students_count' =>
                    $sessions->count(),

                'passed' =>
                    $sessions
                        ->where(
                            'result',
                            'Passed'
                        )
                        ->count(),

                'failed' =>
                    $sessions
                        ->where(
                            'result',
                            'Failed'
                        )
                        ->count(),

                'average_score' =>
                    $sessions->count() > 0
                        ? round(
                            $sessions->avg(
                                'score'
                            ),
                            2
                        )
                        : 0,

                'average_percentage' =>
                    $sessions->count() > 0
                        ? round(
                            $sessions->avg(
                                'percentage'
                            ),
                            2
                        )
                        : 0,
            ],

            'data' =>
                $sessions,
        ]);
    }
}
