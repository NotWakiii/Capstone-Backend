<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\MatchingPair;

class QuestionController extends Controller
{
    /**
     * Display questions by exam
     */
    public function index($exam_id)
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
     * Store question
     */
    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required',
            'question' => 'required',
            'question_type' => 'required'
        ]);

        $question = Question::create([
            'exam_id' => $request->exam_id,
            'question' => $request->question,
            'question_type' => $request->question_type,
            'answer' => $request->answer,
            'points' => $request->points ?? 1
        ]);

        /**
         * Multiple Choice / True False
         */
        if ($request->question_type == 'multiple_choice' ||
            $request->question_type == 'true_false') {

            foreach ($request->options as $option) {

                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct']
                ]);
            }
        }

        /**
         * Matching Type
         */
        if ($request->question_type == 'matching_type') {

            foreach ($request->pairs as $pair) {

                MatchingPair::create([
                    'question_id' => $question->id,
                    'left_item' => $pair['left_item'],
                    'right_item' => $pair['right_item']
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Question created successfully',
            'data' => $question
        ]);
    }

    /**
     * Show single question
     */
    public function show(string $id)
    {
        $question = Question::with([
            'options',
            'matchingPairs'
        ])->find($id);

        if (!$question) {

            return response()->json([
                'status' => false,
                'message' => 'Question not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $question
        ]);
    }

    /**
     * Update question
     */
    public function update(Request $request, string $id)
    {
        $question = Question::find($id);

        if (!$question) {

            return response()->json([
                'status' => false,
                'message' => 'Question not found'
            ], 404);
        }

        $question->update([
            'question' => $request->question,
            'question_type' => $request->question_type,
            'answer' => $request->answer,
            'points' => $request->points
        ]);

        /**
         * Delete old options
         */
        QuestionOption::where('question_id', $question->id)->delete();

        /**
         * Delete old matching pairs
         */
        MatchingPair::where('question_id', $question->id)->delete();

        /**
         * Re-insert options
         */
        if ($request->question_type == 'multiple_choice' ||
            $request->question_type == 'true_false') {

            foreach ($request->options as $option) {

                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct']
                ]);
            }
        }

        /**
         * Re-insert matching pairs
         */
        if ($request->question_type == 'matching_type') {

            foreach ($request->pairs as $pair) {

                MatchingPair::create([
                    'question_id' => $question->id,
                    'left_item' => $pair['left_item'],
                    'right_item' => $pair['right_item']
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Question updated successfully',
            'data' => $question
        ]);
    }

    /**
     * Delete question
     */
    public function destroy(string $id)
    {
        $question = Question::find($id);

        if (!$question) {

            return response()->json([
                'status' => false,
                'message' => 'Question not found'
            ], 404);
        }

        $question->delete();

        return response()->json([
            'status' => true,
            'message' => 'Question deleted successfully'
        ]);
    }
}
