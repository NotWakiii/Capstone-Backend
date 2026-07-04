<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamSession;
use App\Models\Question;

class ResultController extends Controller
{
    /**
     * Faculty View All Results
     */
   public function index()
{
    $results = ExamSession::with([
        'exam',
        'answers.question'
    ])
    ->latest()
    ->get();

    return response()->json([
        'status' => true,
        'data' => $results
    ]);
}

    /**
     * Results of a specific exam
     */
    public function examResults($id)
    {
        $sessions = ExamSession::with([
            'exam',
            'answers.question'
        ])
        ->where('exam_id', $id)
        ->where('status', 'submitted')
        ->latest()
        ->get();

        $exam = $sessions->first()?->exam;

        $results = $sessions->map(function ($session) {

            $totalQuestions = Question::where(
                'exam_id',
                $session->exam_id
            )->count();

            $correct = $session->answers
                ->where('is_correct', true)
                ->count();

            $wrong = max(
                $totalQuestions - $correct,
                0
            );

            $percentage = $session->percentage;

            if ($percentage <= 0 && $totalQuestions > 0) {
                $percentage = round(
                    ($correct / $totalQuestions) * 100,
                    2
                );
            }

            return [
                'id' => $session->id,
                'student_name' => $session->student_name,
                'score' => $session->score,
                'percentage' => $percentage,
                'correct' => $correct,
                'wrong' => $wrong,
                'time_spent' => $session->time_spent,
                'submitted_at' => $session->submitted_at
            ];
        });

        return response()->json([
            'status' => true,
          'exam' => [
    'id' => $exam->id,
    'title' => $exam->title,
    'passing' => $exam->passing,
    'questions_count' => Question::where('exam_id', $exam->id)->count(),
],
            'data' => $results
        ]);
    }

    /**
     * View Single Result
     */
    public function show($session_id)
    {
        $session = ExamSession::with([
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

        $correct = $session->answers
            ->where('is_correct', true)
            ->count();

        $wrong = max(
            $totalQuestions - $correct,
            0
        );

        $percentage = $session->percentage;

        if ($percentage <= 0 && $totalQuestions > 0) {

            $percentage = round(
                ($correct / $totalQuestions) * 100,
                2
            );
        }

        $passingScore = $session->exam->passing ?? 75;

        $status = $percentage >= $passingScore
            ? 'Passed'
            : 'Failed';

        return response()->json([
            'status' => true,
            'student_name' => $session->student_name,
            'exam' => $session->exam,
            'score' => $session->score,
            'correct' => $correct,
            'wrong' => $wrong,
            'total_questions' => $totalQuestions,
            'percentage' => $percentage,
            'result_status' => $status,
            'answers' => $session->answers
        ]);
    }

    public function itemAnalysis($id)
{
    $questions = Question::with([
        'options',
        'answers'
    ])
    ->where('exam_id', $id)
    ->orderBy('question_order')
    ->get();

    $totalStudents = ExamSession::where('exam_id', $id)
        ->where('status', 'submitted')
        ->count();

    $items = $questions->map(function ($question, $index) use ($totalStudents) {

        $answers = $question->answers;

        $correct = $answers->where('is_correct', true)->count();

        $wrong = max($totalStudents - $correct, 0);

        $successRate = $totalStudents > 0
            ? round(($correct / $totalStudents) * 100)
            : 0;

        $wrongAnswers = $answers
            ->where('is_correct', false)
            ->groupBy('answer')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        $commonWrongAnswer = $wrongAnswers->keys()->first() ?? 'None';

        return [
            'id' => $question->id,
            'number' => $index + 1,
            'question' => $question->question,
            'type' => $question->question_type,
            'successRate' => $successRate,
            'correct' => $correct,
            'wrong' => $wrong,
            'total' => $totalStudents,
            'discrimination' => 'N/A',
            'commonWrongAnswer' => $commonWrongAnswer
        ];
    });

    return response()->json([
        'status' => true,
        'data' => $items
    ]);
}

    /**
     * Overall Summary
     */
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