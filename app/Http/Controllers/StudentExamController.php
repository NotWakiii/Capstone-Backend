<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamSession;
use App\Models\StudentAnswer;

class StudentExamController extends Controller
{
    /**
     * Join exam using access code
     */
    public function joinExam(Request $request)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|min:3|max:255',
            'section' => 'required|string|max:100',
            'access_code' => 'required|string|max:20',
        ]);

        $exam = Exam::withCount('questions')
            ->where(
                'access_code',
                strtoupper(trim($validated['access_code']))
            )
            ->first();

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid access code.',
            ], 404);
        }

        if (strtolower($exam->status) !== 'published') {
            return response()->json([
                'status' => false,
                'message' => 'This exam is not open for joining.',
            ], 403);
        }

        $studentDisplayName =
            trim($validated['student_name']) .
            ' - ' .
            trim($validated['section']);

        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'student_name' => $studentDisplayName,
            'started_at' => null,
            'submitted_at' => null,
            'score' => 0,
            'percentage' => 0,
            'progress' => 0,
            'tab_switches' => 0,
            'idle_seconds' => 0,
            'time_spent' => 0,
            'status' => 'ongoing',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Student joined lobby successfully.',
            'session' => $session,
            'exam' => $exam,
            'student' => [
                'name' => trim($validated['student_name']),
                'section' => trim($validated['section']),
            ],
        ], 201);
    }

    /**
     * Public exam status for the student lobby.
     */
    public function examStatus($id)
    {
        $exam = Exam::withCount('questions')
            ->find($id);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'course' => $exam->course,
                'duration' => $exam->duration,
                'passing' => $exam->passing,
                'status' => $exam->status,
                'questions_count' => $exam->questions_count,
            ],
        ]);
    }

    /**
     * Public student lobby list.
     */
    public function studentLobby($id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $sessions = ExamSession::where('exam_id', $id)
            ->where('status', 'ongoing')
            ->latest()
            ->get([
                'id',
                'exam_id',
                'student_name',
                'status',
                'created_at',
            ]);

        return response()->json([
            'status' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Faculty lobby list.
     */
    public function lobbyStudents($id)
    {
        $sessions = ExamSession::where('exam_id', $id)
            ->where('status', 'ongoing')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Get exam questions
     */
    public function getExamQuestions($examId)
    {
        $exam = Exam::find($examId);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        if (
            !in_array(
                strtolower($exam->status),
                ['started', 'finished'],
                true
            )
        ) {
            return response()->json([
                'status' => false,
                'message' => 'The exam has not started yet.',
            ], 403);
        }

        $questions = Question::with([
            'options',
            'matchingPairs',
        ])
            ->where('exam_id', $examId)
            ->get();

        // Do not expose correct answers to students.
        $questions->each(function ($question) {
            $question->makeHidden(['answer']);

            $question->options->each(function ($option) {
                $option->makeHidden(['is_correct']);
            });
        });

        return response()->json([
            'status' => true,
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'course' => $exam->course,
                'duration' => $exam->duration,
                'passing' => $exam->passing,
                'status' => $exam->status,
            ],
            'data' => $questions,
        ]);
    }

    /**
     * Save answer
     */
    public function saveAnswer(Request $request)
    {
        $validated = $request->validate([
            'exam_session_id' => 'required|integer|exists:exam_sessions,id',
            'question_id' => 'required|integer|exists:questions,id',
            'answer' => 'required',
        ]);

        $session = ExamSession::find($validated['exam_session_id']);

        if (!$session) {
            return response()->json([
                'status' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        if ($session->status === 'submitted') {
            return response()->json([
                'status' => false,
                'message' => 'This exam has already been submitted.',
            ], 403);
        }

        $question = Question::with([
            'options',
            'matchingPairs',
        ])->find($validated['question_id']);

        if (!$question) {
            return response()->json([
                'status' => false,
                'message' => 'Question not found.',
            ], 404);
        }

        if ($question->exam_id !== $session->exam_id) {
            return response()->json([
                'status' => false,
                'message' => 'Question does not belong to this exam.',
            ], 422);
        }

        $submittedAnswer = $validated['answer'];
        $isCorrect = false;

        if ($question->question_type === 'identification') {
            $isCorrect =
                strtolower(trim((string) $submittedAnswer)) ===
                strtolower(trim((string) $question->answer));
        }

        if (
            $question->question_type === 'multiple_choice' ||
            $question->question_type === 'true_false'
        ) {
            foreach ($question->options as $option) {
                if (
                    $option->is_correct &&
                    strtolower(trim($option->option_text)) ===
                    strtolower(trim((string) $submittedAnswer))
                ) {
                    $isCorrect = true;
                    break;
                }
            }
        }

        StudentAnswer::updateOrCreate(
            [
                'exam_session_id' => $session->id,
                'question_id' => $question->id,
            ],
            [
                'answer' => is_array($submittedAnswer)
                    ? json_encode($submittedAnswer)
                    : (string) $submittedAnswer,
                'is_correct' => $isCorrect,
            ]
        );

        $totalQuestions = Question::where(
            'exam_id',
            $session->exam_id
        )->count();

        $answeredQuestions = StudentAnswer::where(
            'exam_session_id',
            $session->id
        )->count();

        $progress = $totalQuestions > 0
            ? round(($answeredQuestions / $totalQuestions) * 100)
            : 0;

        $session->update([
            'progress' => $progress,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Answer saved.',
            'progress' => $progress,
        ]);
    }

    /**
     * Submit exam
     */
    public function submitExam($sessionId)
    {
        $session = ExamSession::with([
            'exam.questions',
            'answers.question',
        ])->find($sessionId);

        if (!$session) {
            return response()->json([
                'status' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        if ($session->status === 'submitted') {
            return response()->json([
                'status' => false,
                'message' => 'This exam has already been submitted.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            $score = 0;

            foreach ($session->answers as $answer) {
                if ($answer->is_correct && $answer->question) {
                    $score += (int) $answer->question->points;
                }
            }

            $totalPoints = $session->exam
                ? $session->exam->questions->sum('points')
                : 0;

            $percentage = $totalPoints > 0
                ? round(($score / $totalPoints) * 100, 2)
                : 0;

            $startedAt = $session->started_at ?? $session->created_at;

            $timeSpent = $startedAt
                ? $startedAt->diffInSeconds(now())
                : 0;

            $session->update([
                'submitted_at' => now(),
                'score' => $score,
                'percentage' => $percentage,
                'progress' => 100,
                'time_spent' => $timeSpent,
                'status' => 'submitted',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Exam submitted successfully.',
                'data' => [
                    'session_id' => $session->id,
                    'exam_id' => $session->exam_id,
                    'student_name' => $session->student_name,
                    'score' => $score,
                    'total_points' => $totalPoints,
                    'percentage' => $percentage,
                    'passing' => $session->exam->passing ?? 75,
                    'result_status' =>
                        $percentage >= ($session->exam->passing ?? 75)
                            ? 'Passed'
                            : 'Failed',
                    'time_spent' => $timeSpent,
                    'submitted_at' => $session->submitted_at,
                ],
            ]);
        } catch (\Throwable $error) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to submit exam.',
                'error' => $error->getMessage(),
            ], 500);
        }
    }
}
