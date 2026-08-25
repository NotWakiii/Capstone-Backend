<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ExamSession;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helper: Find Exam Accessible By Current User
    |--------------------------------------------------------------------------
    |
    | Faculty:
    |   Can only access exams they created.
    |
    | Admin:
    |   Can access all exams.
    |
    */

private function findAccessibleExam(
    $id,
    array $with = []
) {
    $user = auth()->user();

    if (!$user) {
        return null;
    }

    $query = Exam::query();

    if (!empty($with)) {
        $query->with($with);
    }

    $query->where('id', $id);

    if ($user->role !== 'admin') {
        $query->where(
            'created_by',
            $user->id
        );
    }

    return $query->first();
}


    /*
    |--------------------------------------------------------------------------
    | Display Exams
    |--------------------------------------------------------------------------
    |
    | Faculty → own exams only
    | Admin   → all exams
    |
    */

public function index()
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated.'
        ], 401);
    }

    $query = Exam::with([
        'questions.options'
    ]);

    if ($user->role !== 'admin') {
        $query->where(
            'created_by',
            $user->id
        );
    }

    $exams = $query
        ->latest()
        ->get();

    return response()->json([
        'status' => true,
        'data' => $exams
    ]);
}


    /*
    |--------------------------------------------------------------------------
    | Store Exam
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'title' =>
                'required|string|max:255',

            'description' =>
                'nullable|string',

            'grade' =>
                'required|string|max:50',

            'section' =>
                'required|string|max:100',

            'subject' =>
                'required|string|max:255',

            'passing' =>
                'required|integer|min:1|max:100',

            'duration' =>
                'required|integer|min:1',

            'questions' =>
                'required|array|min:1',

            'questions.*.question' =>
                'required|string',

            'questions.*.type' =>
                'required|string',

            'questions.*.competency' =>
                'nullable|string|max:1000',

            'questions.*.answer' =>
                'nullable',

            'questions.*.points' =>
                'nullable|numeric|min:0',

            'questions.*.time' =>
                'nullable|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            /*
             * Create exam.
             */
            $exam = Exam::create([
                'title' =>
                    $request->title,

                'description' =>
                    $request->description,

                'grade' =>
                    $request->grade,

                'section' =>
                    $request->section,

                'subject' =>
                    $request->subject,

                'duration' =>
                    $request->duration,

                'passing' =>
                    $request->passing,

                'access_code' =>
                    strtoupper(
                        Str::random(6)
                    ),

                /*
                 * THIS IS THE OWNER.
                 */
                'created_by' =>
                    auth()->id(),

                'status' =>
                    'draft'
            ]);


            /*
             * Create questions.
             */
            foreach (
                $request->questions
                as $index => $item
            ) {

                $type = match (
                    $item['type']
                ) {

                    'Multiple Choice' =>
                        'multiple_choice',

                    'True or False' =>
                        'true_false',

                    'Identification' =>
                        'identification',

                    'Essay' =>
                        'essay',

                    default =>
                        'multiple_choice'
                };


                $question =
                    Question::create([

                        'exam_id' =>
                            $exam->id,

                        'question' =>
                            $item['question'],

                        'question_type' =>
                            $type,

                        /*
                         * Competency used
                         * by Item Analysis.
                         */
                        'competency' =>
                            $item['competency']
                            ?? null,

                        'answer' =>
                            $item['answer']
                            ?? null,

                        'points' =>
                            $item['points']
                            ?? 1,

                        'time_limit' =>
                            $item['time']
                            ?? 30,

                        'question_order' =>
                            $index + 1,
                    ]);


                /*
                 * Multiple Choice Options.
                 */
                if (
                    $type ===
                        'multiple_choice'
                    &&
                    isset(
                        $item['options']
                    )
                ) {

                    foreach (
                        $item['options']
                        as $optionIndex =>
                            $optionText
                    ) {

                        if (
                            !$optionText
                        ) {
                            continue;
                        }

                        $letter =
                            chr(
                                65 +
                                $optionIndex
                            );


                        QuestionOption::create([

                            'question_id' =>
                                $question->id,

                            'option_text' =>
                                $optionText,

                            'is_correct' =>
                                (
                                    $item['answer']
                                    ?? ''
                                )
                                === $letter,
                        ]);
                    }
                }
            }


            DB::commit();


            return response()->json([
                'status' => true,

                'message' =>
                    'Exam created successfully',

                'data' =>
                    $exam->load(
                        'questions.options'
                    )
            ], 201);


        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,

                'message' =>
                    'Failed to create exam',

                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Show Single Exam
    |--------------------------------------------------------------------------
    */

    public function show(string $id)
    {
        $exam =
            $this->findAccessibleExam(
                $id,
                [
                    'questions.options'
                ]
            );

        if (!$exam) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Exam not found or access denied.'
            ], 404);
        }


        return response()->json([
            'status' => true,
            'data' => $exam
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Restart Exam
    |--------------------------------------------------------------------------
    */

    public function restartExam($id)
    {
        $exam =
            $this->findAccessibleExam(
                $id
            );

        if (!$exam) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Exam not found or access denied.'
            ], 404);
        }


        $exam->update([
            'status' =>
                'published',

            'access_code' =>
                strtoupper(
                    Str::random(6)
                ),

            'started_at' =>
                null,

            'ended_at' =>
                null
        ]);


        return response()->json([
            'status' => true,

            'message' =>
                'Exam restarted successfully',

            'data' =>
                $exam
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | End Examination
    |--------------------------------------------------------------------------
    */

    public function endExam($id)
    {
        $exam =
            $this->findAccessibleExam(
                $id,
                [
                    'questions'
                ]
            );

        if (!$exam) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Examination not found or access denied.'
            ], 404);
        }


        /*
         * Calculate total points.
         */
        $totalPoints =
            (int)
            $exam
                ->questions
                ->sum('points');


        /*
         * Get all ongoing sessions.
         */
        $sessions =
            ExamSession::with([
                'answers.question'
            ])
            ->where(
                'exam_id',
                $exam->id
            )
            ->where(
                'status',
                'ongoing'
            )
            ->get();


        /*
         * Auto-submit unfinished students.
         */
        foreach (
            $sessions
            as $session
        ) {

            $score = 0;


            foreach (
                $session->answers
                as $answer
            ) {

                if (
                    $answer->is_correct
                    &&
                    $answer->question
                ) {

                    $score +=
                        (int)
                        $answer
                            ->question
                            ->points;
                }
            }


            $percentage =
                $totalPoints > 0

                    ? round(
                        (
                            $score
                            /
                            $totalPoints
                        )
                        *
                        100,
                        2
                    )

                    : 0;


            $session->update([
                'submitted_at' =>
                    now(),

                'score' =>
                    $score,

                'percentage' =>
                    $percentage,

                'progress' =>
                    100,

                'status' =>
                    'submitted',
            ]);
        }


        /*
         * Mark exam finished.
         */
        $exam->update([
            'status' =>
                'finished',

            'ended_at' =>
                now()
        ]);


        return response()->json([
            'status' => true,

            'message' =>
                'Examination ended successfully.',

            'submitted_students' =>
                $sessions->count(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Exam
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        string $id
    ) {

        $request->validate([

            'title' =>
                'required|string|max:255',

            'description' =>
                'nullable|string',

            'grade' =>
                'required|string|max:50',

            'section' =>
                'required|string|max:100',

            'subject' =>
                'required|string|max:255',

            'duration' =>
                'required|integer|min:1',

            'passing' =>
                'required|integer|min:1|max:100',

            'questions' =>
                'required|array|min:1',

            'questions.*.question' =>
                'required|string',

            'questions.*.type' =>
                'required|string',

            'questions.*.competency' =>
                'nullable|string|max:1000',
        ]);


        /*
         * Ownership check.
         */
        $exam =
            $this->findAccessibleExam(
                $id
            );


        if (!$exam) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Exam not found or access denied.'
            ], 404);
        }


        DB::beginTransaction();


        try {

            /*
             * Update exam details.
             */
            $exam->update([

                'title' =>
                    $request->title,

                'description' =>
                    $request->description,

                'grade' =>
                    $request->grade,

                'section' =>
                    $request->section,

                'subject' =>
                    $request->subject,

                'duration' =>
                    $request->duration,

                'passing' =>
                    $request->passing,
            ]);


            /*
             * Delete existing questions.
             *
             * Your current Edit Exam workflow
             * recreates them.
             */
            $exam
                ->questions()
                ->delete();


            /*
             * Recreate questions.
             */
            foreach (
                $request->questions
                as $index => $item
            ) {

                $type = match (
                    $item['type']
                ) {

                    'Multiple Choice' =>
                        'multiple_choice',

                    'True or False' =>
                        'true_false',

                    'Identification' =>
                        'identification',

                    'Essay' =>
                        'essay',

                    default =>
                        'multiple_choice'
                };


                $question =
                    Question::create([

                        'exam_id' =>
                            $exam->id,

                        'question' =>
                            $item['question'],

                        'question_type' =>
                            $type,

                        /*
                         * Keep competency
                         * during editing.
                         */
                        'competency' =>
                            $item['competency']
                            ?? null,

                        'answer' =>
                            $item['answer']
                            ?? null,

                        'points' =>
                            $item['points']
                            ?? 1,

                        'time_limit' =>
                            $item['time']
                            ?? 30,

                        'question_order' =>
                            $index + 1,
                    ]);


                /*
                 * Recreate MC options.
                 */
                if (
                    $type ===
                        'multiple_choice'
                    &&
                    isset(
                        $item['options']
                    )
                ) {

                    foreach (
                        $item['options']
                        as $optionIndex =>
                            $optionText
                    ) {

                        if (!$optionText) {
                            continue;
                        }


                        $letter =
                            chr(
                                65 +
                                $optionIndex
                            );


                        QuestionOption::create([

                            'question_id' =>
                                $question->id,

                            'option_text' =>
                                $optionText,

                            'is_correct' =>
                                (
                                    $item['answer']
                                    ?? ''
                                )
                                ===
                                $letter,
                        ]);
                    }
                }
            }


            DB::commit();


            return response()->json([
                'status' => true,

                'message' =>
                    'Exam updated successfully',

                'data' =>
                    $exam->load(
                        'questions.options'
                    )
            ]);


        } catch (\Exception $e) {

            DB::rollBack();


            return response()->json([
                'status' => false,

                'message' =>
                    'Failed to update exam',

                'error' =>
                    $e->getMessage()
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Publish Exam
    |--------------------------------------------------------------------------
    */

    public function publish($id)
    {
        $exam =
            $this->findAccessibleExam(
                $id
            );


        if (!$exam) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Exam not found or access denied.'
            ], 404);
        }


        $exam->update([
            'status' =>
                'published'
        ]);


        return response()->json([
            'status' => true,

            'message' =>
                'Exam published successfully',

            'data' =>
                $exam
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Start Exam
    |--------------------------------------------------------------------------
    */

    public function startExam($id)
    {
        $exam =
            $this->findAccessibleExam(
                $id
            );


        if (!$exam) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Exam not found or access denied.'
            ], 404);
        }


        if (
            $exam->status
            !== 'published'
        ) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Only a published exam can be started.'
            ], 422);
        }


        $exam->update([
            'status' =>
                'started',

            'started_at' =>
                now(),
        ]);


        /*
         * Existing student sessions
         * can also receive the official
         * start timestamp.
         */
        ExamSession::where(
            'exam_id',
            $exam->id
        )
        ->where(
            'status',
            'ongoing'
        )
        ->update([
            'started_at' =>
                now(),
        ]);


        return response()->json([
            'status' => true,

            'message' =>
                'Exam started successfully.',

            'data' =>
                $exam->fresh(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Exam
    |--------------------------------------------------------------------------
    */

    public function destroy(string $id)
    {
        $exam =
            $this->findAccessibleExam(
                $id
            );


        if (!$exam) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Exam not found or access denied.'
            ], 404);
        }


        $exam->delete();


        return response()->json([
            'status' => true,

            'message' =>
                'Exam deleted successfully'
        ]);
    }
}
