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
                'message' => 'Examination not found.',
            ], 404);
        }

        $ongoingStudents = ExamSession::where(
            'exam_id',
            $exam->id
        )
            ->where('status', 'ongoing')
            ->count();

        $submittedStudents = ExamSession::where(
            'exam_id',
            $exam->id
        )
            ->where('status', 'submitted')
            ->count();

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'course' => $exam->course,
                'duration' => (int) $exam->duration,
                'passing' => (float) ($exam->passing ?? 75),
                'status' => $exam->status,
                'questions_count' => $exam->questions_count,
                'ongoing_students' => $ongoingStudents,
                'submitted_students' => $submittedStudents,
                'leaderboard_available' =>
                    strtolower((string) $exam->status) === 'finished'
                    || $ongoingStudents === 0,
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
                ['started'],
                true
            )
        ) {
            return response()->json([
                'status' => false,
                'message' => 'The examination is not currently active.',
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
        'exam_session_id' => [
            'required',
            'integer',
            'exists:exam_sessions,id',
        ],

        'question_id' => [
            'required',
            'integer',
            'exists:questions,id',
        ],

        'answer' => [
            'required',
        ],
    ]);

    $session = ExamSession::findOrFail(
        $validated['exam_session_id']
    );

    if ($session->status === 'submitted') {
        return response()->json([
            'status' => false,
            'message' => 'This examination has already been submitted.',
        ], 403);
    }

    $exam = Exam::find($session->exam_id);

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Examination not found.',
        ], 404);
    }

    if (strtolower((string) $exam->status) !== 'started') {
        return response()->json([
            'status' => false,
            'message' => 'The examination has already ended.',
        ], 403);
    }

    $question = Question::with([
        'options',
        'matchingPairs',
    ])->findOrFail(
        $validated['question_id']
    );

    if ((int) $question->exam_id !== (int) $session->exam_id) {
        return response()->json([
            'status' => false,
            'message' => 'This question does not belong to the examination.',
        ], 422);
    }

    $submittedAnswer = is_array($validated['answer'])
        ? $validated['answer']
        : trim((string) $validated['answer']);

    $questionType = strtolower(
        trim((string) $question->question_type)
    );

    $isCorrect = false;

    /*
    |--------------------------------------------------------------------------
    | IDENTIFICATION
    |--------------------------------------------------------------------------
    */

    if ($questionType === 'identification') {
        $studentAnswer = strtolower(
            trim((string) $submittedAnswer)
        );

        $correctAnswer = strtolower(
            trim((string) $question->answer)
        );

        $isCorrect =
            $studentAnswer === $correctAnswer;
    }

    /*
    |--------------------------------------------------------------------------
    | MULTIPLE CHOICE
    |--------------------------------------------------------------------------
    */

    if ($questionType === 'multiple_choice') {
        $studentAnswer = strtolower(
            trim((string) $submittedAnswer)
        );

        foreach ($question->options as $option) {
            $optionText = strtolower(
                trim((string) $option->option_text)
            );

            $optionId = (string) $option->id;

            $matchesText =
                $studentAnswer === $optionText;

            $matchesId =
                (string) $submittedAnswer === $optionId;

            if (
                (bool) $option->is_correct &&
                ($matchesText || $matchesId)
            ) {
                $isCorrect = true;
                break;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TRUE OR FALSE
    |--------------------------------------------------------------------------
    */

    if ($questionType === 'true_false') {
        $studentAnswer = strtolower(
            trim((string) $submittedAnswer)
        );

        $correctAnswer = strtolower(
            trim((string) $question->answer)
        );

        $isCorrect =
            $studentAnswer === $correctAnswer;
    }

    /*
    |--------------------------------------------------------------------------
    | ESSAY
    |--------------------------------------------------------------------------
    |
    | Essays normally require manual checking, so they are saved as
    | incorrect for now until the professor grades them.
    |
    */

    if ($questionType === 'essay') {
        $isCorrect = false;
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE OR UPDATE THE ANSWER
    |--------------------------------------------------------------------------
    */

    $savedAnswer = StudentAnswer::updateOrCreate(
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

    /*
    |--------------------------------------------------------------------------
    | UPDATE STUDENT PROGRESS
    |--------------------------------------------------------------------------
    */

    $totalQuestions = Question::where(
        'exam_id',
        $session->exam_id
    )->count();

    $answeredQuestions = StudentAnswer::where(
        'exam_session_id',
        $session->id
    )->count();

    $progress = $totalQuestions > 0
        ? round(
            ($answeredQuestions / $totalQuestions) * 100
        )
        : 0;

    $session->update([
        'progress' => $progress,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Answer saved successfully.',
        'data' => [
            'answer_id' => $savedAnswer->id,
            'is_correct' => $isCorrect,
            'progress' => $progress,
        ],
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
            $totalPoints = $session->exam
                ? (int) $session->exam->questions->sum('points')
                : 0;

            return response()->json([
                'status' => true,
                'message' => 'This examination was already submitted.',
                'data' => [
                    'session_id' => $session->id,
                    'exam_id' => $session->exam_id,
                    'student_name' => $session->student_name,
                    'score' => (int) $session->score,
                    'total_points' => $totalPoints,
                    'percentage' => (float) $session->percentage,
                    'passing' => $session->exam->passing ?? 75,
                    'result_status' =>
                        (float) $session->percentage >=
                        (float) ($session->exam->passing ?? 75)
                            ? 'Passed'
                            : 'Failed',
                    'time_spent' => (int) $session->time_spent,
                    'submitted_at' => $session->submitted_at,
                ],
            ]);
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