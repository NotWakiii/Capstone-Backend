<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Exam;

class ResultController extends Controller
{
    /**
 * Return the student's own examination result.
 */
public function studentResult($sessionId)
    {
        $session = ExamSession::with([
            'exam',
            'answers.question',
        ])->find($sessionId);

        if (!$session) {
            return response()->json([
                'status' => false,
                'message' => 'Student result not found.',
            ], 404);
        }

        if ($session->status !== 'submitted') {
            return response()->json([
                'status' => false,
                'message' => 'The examination has not been submitted yet.',
            ], 403);
        }

        if (!$session->exam) {
            return response()->json([
                'status' => false,
                'message' => 'The examination record is unavailable.',
            ], 404);
        }

        $totalPoints = (int) Question::where(
            'exam_id',
            $session->exam_id
        )->sum('points');

        $totalQuestions = Question::where(
            'exam_id',
            $session->exam_id
        )->count();

        $correctAnswers = $session->answers
            ->where('is_correct', true)
            ->count();

        $wrongAnswers = max(
            $totalQuestions - $correctAnswers,
            0
        );

        $percentage = (float) $session->percentage;

        if ($totalPoints > 0) {
            $percentage = round(
                ((int) $session->score / $totalPoints) * 100,
                2
            );
        }

        $passing = (float) (
            $session->exam->passing ?? 75
        );

        $ongoingStudents = ExamSession::where(
            'exam_id',
            $session->exam_id
        )
            ->where('status', 'ongoing')
            ->count();

        $leaderboardAvailable =
            strtolower((string) $session->exam->status) === 'finished'
            || $ongoingStudents === 0;

        $rank = null;

        if ($leaderboardAvailable) {
            $rank = ExamSession::where(
                'exam_id',
                $session->exam_id
            )
                ->where('status', 'submitted')
                ->where(function ($query) use ($session) {
                    $query
                        ->where(
                            'percentage',
                            '>',
                            $session->percentage
                        )
                        ->orWhere(function ($samePercentage) use ($session) {
                            $samePercentage
                                ->where(
                                    'percentage',
                                    $session->percentage
                                )
                                ->where(
                                    'score',
                                    '>',
                                    $session->score
                                );
                        })
                        ->orWhere(function ($sameScore) use ($session) {
                            $sameScore
                                ->where(
                                    'percentage',
                                    $session->percentage
                                )
                                ->where(
                                    'score',
                                    $session->score
                                )
                                ->where(
                                    'time_spent',
                                    '<',
                                    $session->time_spent
                                );
                        });
                })
                ->count() + 1;
        }

        return response()->json([
            'status' => true,
            'data' => [
                'session_id' => $session->id,
                'exam_id' => $session->exam_id,
                'student_name' => $session->student_name,
                'exam_title' => $session->exam->title ?? 'Examination',
                'course' => $session->exam->course ?? 'No Course',
                'score' => (int) $session->score,
                'total_points' => $totalPoints,
                'percentage' => $percentage,
                'passing' => $passing,
                'correct_answers' => $correctAnswers,
                'wrong_answers' => $wrongAnswers,
                'total_questions' => $totalQuestions,
                'time_spent' => (int) $session->time_spent,
                'rank' => $rank,
                'leaderboard_available' => $leaderboardAvailable,
                'result_status' =>
                    $percentage >= $passing
                        ? 'Passed'
                        : 'Failed',
                'submitted_at' => $session->submitted_at,
            ],
        ]);
    }


/**
 * Return the Top 5 students with their visible scores.
 */
public function studentLeaderboard($examId)
    {
        $exam = Exam::with('questions')->find($examId);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Examination not found.',
            ], 404);
        }

        $ongoingStudents = ExamSession::where(
            'exam_id',
            $exam->id
        )
            ->where('status', 'ongoing')
            ->count();

        $leaderboardAvailable =
            strtolower((string) $exam->status) === 'finished'
            || $ongoingStudents === 0;

        if (!$leaderboardAvailable) {
            return response()->json([
                'status' => true,
                'leaderboard_available' => false,
                'message' =>
                    'The Top 5 ranking will be available after the examination ends.',
                'data' => [],
            ]);
        }

        $totalPoints = (int) $exam->questions->sum('points');
        $passing = (float) ($exam->passing ?? 75);

        $sessions = ExamSession::where(
            'exam_id',
            $exam->id
        )
            ->where('status', 'submitted')
            ->orderByDesc('percentage')
            ->orderByDesc('score')
            ->orderBy('time_spent')
            ->orderBy('submitted_at')
            ->limit(5)
            ->get();

        $leaderboard = $sessions
            ->values()
            ->map(function ($session, $index) use (
                $totalPoints,
                $passing
            ) {
                $percentage = (float) $session->percentage;

                if ($totalPoints > 0) {
                    $percentage = round(
                        ((int) $session->score / $totalPoints) * 100,
                        2
                    );
                }

                return [
                    'rank' => $index + 1,
                    'session_id' => $session->id,
                    'student_name' => $session->student_name,
                    'score' => (int) $session->score,
                    'total_points' => $totalPoints,
                    'percentage' => $percentage,
                    'time_spent' => (int) $session->time_spent,
                    'result_status' =>
                        $percentage >= $passing
                            ? 'Passed'
                            : 'Failed',
                ];
            });

        return response()->json([
            'status' => true,
            'leaderboard_available' => true,
            'data' => $leaderboard,
        ]);
    }

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

        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Examination not found.',
            ], 404);
        }

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