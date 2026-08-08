<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
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
                    'course' => $exam->course,
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

        $sessions = ExamSession::where('exam_id', $exam->id)
            ->where('status', 'submitted')
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($session) use ($exam) {

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

                    'tab_switches' => $session->tab_switches,

                    'idle_seconds' => $session->idle_seconds,

                    'status' => $session->status,
                ];
            });

        return response()->json([
            'success' => true,

            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'course' => $exam->course,
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
        if ((int) $session->exam->created_by !== (int) $faculty->id) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

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
                'tab_switches' => $session->tab_switches,
                'idle_seconds' => $session->idle_seconds,
            ],

            'exam' => [
                'id' => $session->exam->id,
                'title' => $session->exam->title,
                'course' => $session->exam->course,
                'passing' => $session->exam->passing,
            ],

            'answers' => $session->answers->map(function ($answer) {

                return [
                    'id' => $answer->id,
                    'question_id' => $answer->question_id,
                    'question' => $answer->question,
                    'answer' => $answer->answer,
                    'is_correct' => (bool) $answer->is_correct,
                ];
            })
        ]);
    }
}
