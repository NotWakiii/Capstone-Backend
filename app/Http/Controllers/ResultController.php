<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\Question;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    /**
     * Faculty View All Results
     */
    public function index()
    {
        $results = ExamSession::with([
            'student',
            'exam'
        ])->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $results
        ]);
    }

    /**
     * View Single Result
     */
    public function show($session_id)
    {
        $session = ExamSession::with([
            'student',
            'exam',
            'answers.question'
        ])->find($session_id);

        if (!$session) {

            return response()->json([
                'status' => false,
                'message' => 'Result not found'
            ], 404);
        }

        $totalQuestions = Question::where(
            'exam_id',
            $session->exam_id
        )->count();

        $score = $session->score;

        $percentage =
            $totalQuestions > 0
            ? round(($score / $totalQuestions) * 100, 2)
            : 0;

        // Passing score
        $passingScore = 75;

        // Pass or Fail
        $status = $percentage >= $passingScore
            ? 'Passed'
            : 'Failed';

        return response()->json([
            'status' => true,
            'student' => $session->student,
            'exam' => $session->exam,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'percentage' => $percentage,
            'result_status' => $status,
            'answers' => $session->answers
        ]);
    }
    /**
     * Student View Own Results
     */
    public function myResults()
    {
        $results = ExamSession::with([
            'exam'
        ])
        ->where('student_id', auth()->id())
        ->latest()
        ->get();

        return response()->json([
            'status' => true,
            'data' => $results
        ]);
    }
    public function summary()
    {
        $sessions = ExamSession::all();

        return response()->json([

            'total_attempts' => $sessions->count(),

            'highest_score' => $sessions->max('score'),

            'lowest_score' => $sessions->min('score'),

            'average_score' => round(
                $sessions->avg('score'),
                2
            )
        ]);
    }
}
