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
            'access_code' => 'required'
        ]);

        $exam = Exam::where(
            'access_code',
            $request->access_code
        )->first();

        if (!$exam) {

            return response()->json([
                'status' => false,
                'message' => 'Invalid access code'
            ], 404);
        }

        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'student_id' => auth()->id(),
            'started_at' => now(),
            'status' => 'ongoing'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Exam joined successfully',
            'session' => $session,
            'exam' => $exam
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
