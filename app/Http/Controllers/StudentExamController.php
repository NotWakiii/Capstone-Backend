<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
    $request->validate([
        'student_name' => 'required|string|max:255',
        'access_code' => 'required|string'
    ]);

    $exam = Exam::where('access_code', strtoupper($request->access_code))
        ->first();

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid access code'
        ], 404);
    }

    if ($exam->status !== 'published') {
        return response()->json([
            'status' => false,
            'message' => 'This exam is not open for joining.'
        ], 403);
    }

    $session = ExamSession::create([
        'exam_id' => $exam->id,
        'student_name' => $request->student_name,
        'started_at' => null,
        'status' => 'ongoing'
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Student joined lobby successfully',
        'session' => $session,
        'exam' => $exam
    ]);

}

public function lobbyStudents($id)
{
    $sessions = ExamSession::where('exam_id', $id)
        ->where('status', 'ongoing')
        ->latest()
        ->get();

    return response()->json([
        'status' => true,
        'data' => $sessions
    ]);
}

    /**
     * Get exam questions
     */
    public function getExamQuestions($exam_id)
    {
        $questions = Question::with([
            'options',
            'matchingPairs'
        ])
        ->where('exam_id', $exam_id)
        ->get();

        return response()->json([
            'status' => true,
            'data' => $questions
        ]);
    }

    /**
     * Save answer
     */
    public function saveAnswer(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required',
            'question_id' => 'required',
            'answer' => 'required'
        ]);

        $question = Question::with([
            'options',
            'matchingPairs'
        ])->find($request->question_id);

        $isCorrect = false;

        /**
         * IDENTIFICATION
         */
        if ($question->question_type == 'identification') {

            if (
                strtolower(trim($request->answer)) ==
                strtolower(trim($question->answer))
            ) {
                $isCorrect = true;
            }
        }

        /**
         * MULTIPLE CHOICE / TRUE FALSE
         */
        if (
            $question->question_type == 'multiple_choice' ||
            $question->question_type == 'true_false'
        ) {

            foreach ($question->options as $option) {

                if (
                    $option->is_correct &&
                    $option->option_text == $request->answer
                ) {
                    $isCorrect = true;
                }
            }
        }

        StudentAnswer::updateOrCreate(
            [
                'exam_session_id' => $request->exam_session_id,
                'question_id' => $request->question_id
            ],
            [
                'answer' => $request->answer,
                'is_correct' => $isCorrect
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Answer saved',
            'correct' => $isCorrect
        ]);
    }

    /**
     * Submit exam
     */
    public function submitExam($session_id)
    {
        $session = ExamSession::with([
            'answers.question'
        ])->find($session_id);

        if (!$session) {

            return response()->json([
                'status' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $score = 0;

        foreach ($session->answers as $answer) {

            if ($answer->is_correct) {

                $score += $answer->question->points;
            }
        }

        $session->update([
            'submitted_at' => now(),
            'score' => $score,
            'status' => 'submitted'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Exam submitted successfully',
            'score' => $score
        ]);
    }
}
