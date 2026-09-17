<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ExamSession;
use App\Models\SchoolClass;
use App\Models\StudentAnswer;

use App\Helpers\AuditLogger;

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
        $validated = $request->validate([
            'title' =>
                'required|string|max:255',

            'description' =>
                'nullable|string',

            // NEW: Faculty can select one or more classes.
            'class_ids' =>
                'required|array|min:1',

            'class_ids.*' =>
                'required|integer|distinct|exists:school_classes,id',

            'passing' =>
                'required|integer|min:1|max:100',

            'duration' =>
                'required|integer|min:1',

            'tab_switch_penalty_seconds' =>
                'nullable|integer|min:0',

            'fullscreen_exit_penalty_seconds' =>
                'nullable|integer|min:0',

            'copy_attempt_penalty_seconds' =>
                'nullable|integer|min:0',

            'paste_attempt_penalty_seconds' =>
                'nullable|integer|min:0',

            'idle_penalty_seconds' =>
                'nullable|integer|min:0',

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

            'questions.*.options' =>
                'nullable|array',

            'questions.*.options.*' =>
                'nullable|string|max:1000',

            'questions.*.points' =>
                'nullable|numeric|min:0',

            'questions.*.time' =>
                'nullable|integer|min:1',

            'questions.*.competency' =>
                'nullable|string|max:1000',
            'assessment_type' =>
                'required|in:quiz,examination',
        ]);


        /*
        |--------------------------------------------------------------------------
        | GET SELECTED CLASSES
        |--------------------------------------------------------------------------
        |
        | Only allow the logged-in faculty to assign the exam
        | to classes that belong to them.
        |
        */

        $schoolClasses = SchoolClass::whereIn(
            'id',
            $validated['class_ids']
        )
            ->where(
                'faculty_id',
                $request->user()->id
            )
            ->with([
                'subject',
                'strand',
                'sectionData',
                'schoolYear',
            ])
            ->get();

        $subjectIds = $schoolClasses
            ->pluck('subject_id')
            ->unique()
            ->values();

        if ($subjectIds->count() > 1) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Selected classes must belong to the same subject.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | VERIFY OWNERSHIP OF ALL SELECTED CLASSES
        |--------------------------------------------------------------------------
        */

        if (
            $schoolClasses->count()
            !== count($validated['class_ids'])
        ) {

            return response()->json([
                'status' => false,
                'message' =>
                    'One or more selected classes were not found or access was denied.'
            ], 422);
        }


        DB::beginTransaction();

        try {

            $createdExams = [];


            /*
            |--------------------------------------------------------------------------
            | CREATE ONE EXAM FOR EACH SELECTED CLASS
            |--------------------------------------------------------------------------
            */

            foreach ($schoolClasses as $schoolClass) {

                $exam = Exam::create([
                    'title' =>
                        $validated['title'],

                    'description' =>
                        $validated['description'] ?? null,

                    'class_id' =>
                        $schoolClass->id,

                    'assessment_type' =>
                        $validated['assessment_type'],

                    'grade' =>
                        $schoolClass->grade,

                    'section' =>
                        $schoolClass->sectionData?->section
                        ?? $schoolClass->section,

                    'subject' =>
                        $schoolClass->subject?->name,

                    'duration' =>
                        $validated['duration'],

                    'passing' =>
                        $validated['passing'],

                    'tab_switch_penalty_seconds' =>
                        $validated['tab_switch_penalty_seconds'] ?? null,

                    'fullscreen_exit_penalty_seconds' =>
                        $validated['fullscreen_exit_penalty_seconds'] ?? null,

                    'copy_attempt_penalty_seconds' =>
                        $validated['copy_attempt_penalty_seconds'] ?? null,

                    'paste_attempt_penalty_seconds' =>
                        $validated['paste_attempt_penalty_seconds'] ?? null,

                    'idle_penalty_seconds' =>
                        $validated['idle_penalty_seconds'] ?? null,

                    'access_code' =>
                        strtoupper(
                            Str::random(6)
                        ),

                    'created_by' =>
                        auth()->id(),

                    'status' =>
                        'draft',
                ]);


                /*
                |--------------------------------------------------------------------------
                | COPY QUESTIONS TO THIS EXAM
                |--------------------------------------------------------------------------
                */

                foreach (
                    $validated['questions']
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


                    $question = Question::create([

                        'exam_id' =>
                            $exam->id,

                        'question' =>
                            $item['question'],

                        'question_type' =>
                            $type,

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
                    |--------------------------------------------------------------------------
                    | MULTIPLE CHOICE OPTIONS
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $type === 'multiple_choice'
                        &&
                        isset($item['options'])
                    ) {

                        foreach (
                            $item['options']
                            as $optionIndex => $optionText
                        ) {

                            if (!$optionText) {
                                continue;
                            }


                            $letter = chr(
                                65 + $optionIndex
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
                                    ) === $letter,
                            ]);
                        }
                    }
                }


                /*
                * Store each generated exam so the frontend
                * can receive all created exams.
                */
                $createdExams[] =
                    $exam->load(
                        'questions.options'
                    );
            }


            DB::commit();

            foreach ($createdExams as $createdExam) {
                AuditLogger::log(
                    'CREATE_EXAM',
                    'Examination',
                    'Created examination: ' .
                    $createdExam->title .
                    ' for ' .
                    $createdExam->grade .
                    ' - ' .
                    $createdExam->section
                );
            }


            return response()->json([
                'status' => true,

                'message' =>
                    count($createdExams)
                    . ' exam(s) created successfully.',

                'data' =>
                    $createdExams

            ], 201);


        } catch (\Throwable $e) {

            DB::rollBack();


            return response()->json([
                'status' => false,

                'message' =>
                    'Failed to create exams.',

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
    $exam = $this->findAccessibleExam($id);

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Exam not found or access denied.'
        ], 404);
    }

    /*
    |--------------------------------------------------------------------------
    | RESTART / REOPEN EXAM
    |--------------------------------------------------------------------------
    |
    | Keep the existing exam details, questions, and previous submitted
    | results. Only reopen the examination and generate a new access code.
    |
    */

    $exam->update([
        'status' => 'published',
        'access_code' => strtoupper(Str::random(6)),
        'started_at' => null,
        'ended_at' => null,
    ]);

    AuditLogger::log(
        'RESTART_EXAM',
        'Examination',
        'Reopened examination: ' . $exam->title
    );

    return response()->json([
        'status' => true,
        'message' => 'Exam restarted successfully.',
        'data' => $exam->fresh()
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

        AuditLogger::log(
            'END_EXAM',
            'Examination',
            'Ended examination: ' . $exam->title
        );


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

        $validated = $request->validate([
            'title' =>
                'required|string|max:255',

            'description' =>
                'nullable|string',

            'assessment_type' =>
                'required|in:quiz,examination',

            'class_id' =>
                'required|integer|exists:school_classes,id',

            'duration' =>
                'required|integer|min:1',

            'passing' =>
                'required|integer|min:1|max:100',

            'tab_switch_penalty_seconds' =>
                'nullable|integer|min:0',

            'fullscreen_exit_penalty_seconds' =>
                'nullable|integer|min:0',

            'copy_attempt_penalty_seconds' =>
                'nullable|integer|min:0',

            'paste_attempt_penalty_seconds' =>
                'nullable|integer|min:0',

            'idle_penalty_seconds' =>
                'nullable|integer|min:0',

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

       $schoolClass = SchoolClass::where(
            'id',
            $request->class_id
        )
            ->where(
                'faculty_id',
                auth()->id()
            )
            ->with([
                'subject',
                'sectionData',
            ])
            ->first();

        if (!$schoolClass) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Selected class not found or access denied.'
            ], 422);
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

                'assessment_type' =>
                    $request->assessment_type,

                'class_id' =>
                    $schoolClass->id,

                'grade' =>
                    $schoolClass->grade,

                'section' =>
                    $schoolClass->sectionData?->section
                    ?? $schoolClass->section,

                'subject' =>
                    $schoolClass->subject?->name,

                'duration' =>
                    $request->duration,

                'passing' =>
                    $request->passing,

                'tab_switch_penalty_seconds' =>
                    $validated['tab_switch_penalty_seconds'] ?? null,

                'fullscreen_exit_penalty_seconds' =>
                    $validated['fullscreen_exit_penalty_seconds'] ?? null,

                'copy_attempt_penalty_seconds' =>
                    $validated['copy_attempt_penalty_seconds'] ?? null,

                'paste_attempt_penalty_seconds' =>
                    $validated['paste_attempt_penalty_seconds'] ?? null,

                'idle_penalty_seconds' =>
                    $validated['idle_penalty_seconds'] ?? null,
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

            AuditLogger::log(
                'UPDATE_EXAM',
                'Examination',
                'Updated examination: ' . $exam->title
            );


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
        $exam = $this->findAccessibleExam($id);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found or access denied.'
            ], 404);
        }

        if ($exam->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'Only a draft exam can be published.'
            ], 422);
        }

        if ($exam->questions()->count() === 0) {
            return response()->json([
                'status' => false,
                'message' => 'Add at least one question before publishing.'
            ], 422);
        }

        $exam->update([
            'status' => 'published'
        ]);

        AuditLogger::log(
            'PUBLISH_EXAM',
            'Examination',
            'Published examination: ' . $exam->title
        );

        return response()->json([
            'status' => true,
            'message' => 'Exam published successfully.',
            'data' => $exam->fresh()
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

        AuditLogger::log(
            'START_EXAM',
            'Examination',
            'Started examination: ' . $exam->title
        );


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


        AuditLogger::log(
            'DELETE_EXAM',
            'Examination',
            'Deleted examination: ' . $exam->title
        );

        $exam->delete();


        return response()->json([
            'status' => true,

            'message' =>
                'Exam deleted successfully'
        ]);
    }
public function cancelLobby($id)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }


    /*
    |--------------------------------------------------------------------------
    | FIND EXAM
    |--------------------------------------------------------------------------
    */

    $examQuery = Exam::where(
        'id',
        $id
    );


    if ($user->role !== 'admin') {

        $examQuery->where(
            'created_by',
            $user->id
        );

    }


    $exam =
        $examQuery->first();


    if (!$exam) {

        return response()->json([
            'status' => false,
            'message' =>
                'Examination not found or access denied.',
        ], 404);

    }


    /*
    |--------------------------------------------------------------------------
    | DO NOT CANCEL AFTER EXAM STARTS
    |--------------------------------------------------------------------------
    */

    if (
        strtolower(
            (string) $exam->status
        ) === 'started'
    ) {

        return response()->json([
            'status' => false,
            'message' =>
                'The examination has already started.',
        ], 422);

    }


    DB::beginTransaction();


    try {

        /*
        |--------------------------------------------------------------------------
        | GET WAITING LOBBY SESSIONS
        |--------------------------------------------------------------------------
        */

        $sessionIds =
            ExamSession::where(
                'exam_id',
                $exam->id
            )
            ->where(
                'status',
                'ongoing'
            )
            ->whereNull(
                'started_at'
            )
            ->pluck('id');


        /*
        |--------------------------------------------------------------------------
        | DELETE SESSION DATA
        |--------------------------------------------------------------------------
        */

        if (
            $sessionIds->isNotEmpty()
        ) {

            StudentAnswer::whereIn(
                'exam_session_id',
                $sessionIds
            )->delete();


            DB::table(
                'monitor_logs'
            )
            ->whereIn(
                'exam_session_id',
                $sessionIds
            )
            ->delete();


            ExamSession::whereIn(
                'id',
                $sessionIds
            )->delete();

        }


        DB::commit();

        AuditLogger::log(
            'CANCEL_LOBBY',
            'Examination',
            'Cancelled lobby for examination: ' . $exam->title
        );


        return response()->json([
            'status' => true,
            'message' =>
                'Examination lobby cancelled successfully.',
        ]);


    } catch (\Throwable $error) {

        DB::rollBack();


        return response()->json([
            'status' => false,
            'message' =>
                'Failed to cancel examination lobby.',
            'error' =>
                $error->getMessage(),
        ], 500);

    }
}
}
