<?php

namespace App\Http\Controllers;

use App\Models\TestBankQuestion;
use App\Models\TestBankQuestionOption;
use App\Models\SchoolClass;
use App\Models\Question;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TestBankController extends Controller
{
    /**
     * Display the logged-in faculty's Test Bank.
     */
    public function index(Request $request)
    {
        $this->authorizeFaculty($request);

        $facultyId = $request->user()->id;

        $query = TestBankQuestion::with([
            'options',
            'subject',
            'classes.subject',
        ])
            ->where('faculty_id', $facultyId)
            ->latest();

        /*
        |--------------------------------------------------------------------------
        | Filter by assigned Class
        |--------------------------------------------------------------------------
        */
        if ($request->filled('class_id')) {
            $classId = (int) $request->class_id;

            $this->authorizeFacultyOwnsClass(
                $request,
                $classId
            );

            $query->whereHas(
                'classes',
                function ($q) use ($classId) {
                    $q->where(
                        'school_classes.id',
                        $classId
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by Subject
        |--------------------------------------------------------------------------
        */
        if ($request->filled('subject_id')) {
            $query->where(
                'subject_id',
                $request->subject_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by Question Type
        |--------------------------------------------------------------------------
        */
        if ($request->filled('question_type')) {
            $query->where(
                'question_type',
                $request->question_type
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'question',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'competency',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $questions = $query->get();

        /*
        |--------------------------------------------------------------------------
        | Question Performance Analytics
        |--------------------------------------------------------------------------
        */
        $questions->each(function ($question) {
            $examQuestionIds = Question::where(
                'test_bank_question_id',
                $question->id
            )->pluck('id');

            $totalAnswers = StudentAnswer::whereIn(
                'question_id',
                $examQuestionIds
            )->count();

            $correctAnswers = StudentAnswer::whereIn(
                'question_id',
                $examQuestionIds
            )
                ->where('is_correct', true)
                ->count();

            $correctPercentage = $totalAnswers > 0
                ? round(
                    ($correctAnswers / $totalAnswers) * 100,
                    2
                )
                : 0;

            $question->statistics = [
                'total_answers' => $totalAnswers,
                'correct_answers' => $correctAnswers,
                'correct_percentage' => $correctPercentage,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $questions,
        ]);
    }

    /**
     * Add one question to Test Bank.
     */
    public function store(Request $request)
    {
        $this->authorizeFaculty($request);

        $validated = $request->validate([
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],

            'class_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'class_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:school_classes,id',
            ],

            'question' => [
                'required',
                'string',
            ],

            'question_type' => [
                'required',
                Rule::in([
                    'multiple_choice',
                    'true_false',
                    'identification',
                ]),
            ],

            'competency' => [
                'nullable',
                'string',
            ],

            'answer' => [
                'nullable',
                'string',
            ],

            'points' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'options' => [
                'nullable',
                'array',
            ],

            'options.*.option_text' => [
                'required_with:options',
                'string',
            ],

            'options.*.is_correct' => [
                'required_with:options',
                'boolean',
            ],
        ]);

        $facultyId = $request->user()->id;
        $subjectId = (int) $validated['subject_id'];

        $classIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $validated['class_ids']
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Verify all selected classes
        |--------------------------------------------------------------------------
        |
        | All classes must:
        | - belong to logged-in faculty
        | - belong to selected subject
        |
        */
        $this->authorizeFacultyClasses(
            $request,
            $classIds,
            $subjectId
        );

        $questionText = trim(
            $validated['question']
        );

        /*
        |--------------------------------------------------------------------------
        | Check if same question already exists
        |--------------------------------------------------------------------------
        |
        | Question is stored ONCE.
        | Classes are assignments through the pivot table.
        |
        */
        $existing = TestBankQuestion::where(
            'faculty_id',
            $facultyId
        )
            ->where(
                'subject_id',
                $subjectId
            )
            ->where(
                'question_type',
                $validated['question_type']
            )
            ->where(
                'question',
                $questionText
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Existing Question
        |--------------------------------------------------------------------------
        |
        | Instead of creating a duplicate question, add the selected classes
        | to its existing class assignments.
        |
        */
        if ($existing) {
            DB::transaction(function () use (
                $existing,
                $classIds
            ) {
                $existing->classes()->syncWithoutDetaching(
                    $classIds
                );
            });

            return response()->json([
                'status' => true,
                'already_exists' => true,
                'message' => 'Question already exists. Selected class assignments were added.',
                'data' => $existing
                    ->fresh()
                    ->load([
                        'options',
                        'subject',
                        'classes.subject',
                    ]),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Question
        |--------------------------------------------------------------------------
        */
        $question = DB::transaction(function () use (
            $validated,
            $facultyId,
            $subjectId,
            $classIds,
            $questionText
        ) {
            $question = TestBankQuestion::create([
                'faculty_id' => $facultyId,
                'subject_id' => $subjectId,
                'question' => $questionText,
                'question_type' => $validated['question_type'],
                'competency' => $validated['competency'] ?? null,
                'answer' => $validated['answer'] ?? null,
                'points' => $validated['points'] ?? 1,
                'is_active' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Assign question to selected classes
            |--------------------------------------------------------------------------
            */
            $question->classes()->sync(
                $classIds
            );

            $this->saveOptions(
                $question,
                $validated['question_type'],
                $validated['options'] ?? []
            );

            return $question;
        });

        return response()->json([
            'status' => true,
            'already_exists' => false,
            'message' => 'Question added to Test Bank successfully.',
            'data' => $question->load([
                'options',
                'subject',
                'classes.subject',
            ]),
        ], 201);
    }

    /**
     * Add multiple generated questions to Test Bank.
     */
    public function storeBulk(Request $request)
    {
        $this->authorizeFaculty($request);

        $validated = $request->validate([
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],

            'class_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'class_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:school_classes,id',
            ],

            'questions' => [
                'required',
                'array',
                'min:1',
            ],

            'questions.*.question' => [
                'required',
                'string',
            ],

            'questions.*.question_type' => [
                'required',
                Rule::in([
                    'multiple_choice',
                    'true_false',
                    'identification',
                ]),
            ],

            'questions.*.competency' => [
                'nullable',
                'string',
            ],

            'questions.*.answer' => [
                'nullable',
                'string',
            ],

            'questions.*.points' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'questions.*.options' => [
                'nullable',
                'array',
            ],

            'questions.*.options.*.option_text' => [
                'required_with:questions.*.options',
                'string',
            ],

            'questions.*.options.*.is_correct' => [
                'required_with:questions.*.options',
                'boolean',
            ],
        ]);

        $facultyId = $request->user()->id;
        $subjectId = (int) $validated['subject_id'];

        $classIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $validated['class_ids']
                )
            )
        );

        $this->authorizeFacultyClasses(
            $request,
            $classIds,
            $subjectId
        );

        $added = 0;
        $existingCount = 0;

        $addedQuestions = [];
        $existingQuestions = [];

        DB::transaction(function () use (
            $validated,
            $facultyId,
            $subjectId,
            $classIds,
            &$added,
            &$existingCount,
            &$addedQuestions,
            &$existingQuestions
        ) {
            foreach (
                $validated['questions']
                as $item
            ) {
                $questionText = trim(
                    $item['question']
                );

                /*
                |--------------------------------------------------------------------------
                | Check existing Test Bank question
                |--------------------------------------------------------------------------
                */
                $existing = TestBankQuestion::where(
                    'faculty_id',
                    $facultyId
                )
                    ->where(
                        'subject_id',
                        $subjectId
                    )
                    ->where(
                        'question_type',
                        $item['question_type']
                    )
                    ->where(
                        'question',
                        $questionText
                    )
                    ->first();

                if ($existing) {
                    /*
                    | Question already exists.
                    | Just add new class assignments.
                    */
                    $existing->classes()
                        ->syncWithoutDetaching(
                            $classIds
                        );

                    $existingCount++;

                    $existingQuestions[] = [
                        'question' => $questionText,
                        'test_bank_question_id' => $existing->id,
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Create New Question
                |--------------------------------------------------------------------------
                */
                $question = TestBankQuestion::create([
                    'faculty_id' => $facultyId,
                    'subject_id' => $subjectId,
                    'question' => $questionText,
                    'question_type' => $item['question_type'],
                    'competency' => $item['competency'] ?? null,
                    'answer' => $item['answer'] ?? null,
                    'points' => $item['points'] ?? 1,
                    'is_active' => true,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Assign to selected classes
                |--------------------------------------------------------------------------
                */
                $question->classes()->sync(
                    $classIds
                );

                $this->saveOptions(
                    $question,
                    $item['question_type'],
                    $item['options'] ?? []
                );

                $added++;

                $addedQuestions[] = [
                    'question' => $questionText,
                    'test_bank_question_id' => $question->id,
                ];
            }
        });

        return response()->json([
            'status' => true,

            'message' =>
                $added .
                ' question(s) added. ' .
                $existingCount .
                ' existing question(s) received the selected class assignments.',

            'added' => $added,
            'existing' => $existingCount,
            'total' => count(
                $validated['questions']
            ),

            'added_questions' =>
                $addedQuestions,

            'existing_questions' =>
                $existingQuestions,
        ]);
    }

    /**
     * Display one Test Bank question.
     */
    public function show(
        Request $request,
        string $id
    ) {
        $this->authorizeFaculty($request);

        $question = TestBankQuestion::with([
            'options',
            'subject',
            'classes.subject',
        ])
            ->where(
                'faculty_id',
                $request->user()->id
            )
            ->find($id);

        if (!$question) {
            return response()->json([
                'status' => false,
                'message' => 'Test Bank question not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $question,
        ]);
    }

    /**
     * Update a Test Bank question.
     */
    public function update(
        Request $request,
        string $id
    ) {
        $this->authorizeFaculty($request);

        $question = TestBankQuestion::where(
            'faculty_id',
            $request->user()->id
        )->find($id);

        if (!$question) {
            return response()->json([
                'status' => false,
                'message' => 'Test Bank question not found.',
            ], 404);
        }

        $validated = $request->validate([
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],

            'class_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'class_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:school_classes,id',
            ],

            'question' => [
                'required',
                'string',
            ],

            'question_type' => [
                'required',
                Rule::in([
                    'multiple_choice',
                    'true_false',
                    'identification',
                ]),
            ],

            'competency' => [
                'nullable',
                'string',
            ],

            'answer' => [
                'nullable',
                'string',
            ],

            'points' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'options' => [
                'nullable',
                'array',
            ],

            'options.*.option_text' => [
                'required_with:options',
                'string',
            ],

            'options.*.is_correct' => [
                'required_with:options',
                'boolean',
            ],
        ]);

        $subjectId = (int) $validated['subject_id'];

        $classIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $validated['class_ids']
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Verify selected classes
        |--------------------------------------------------------------------------
        */
        $this->authorizeFacultyClasses(
            $request,
            $classIds,
            $subjectId
        );

        $questionText = trim(
            $validated['question']
        );

        /*
        |--------------------------------------------------------------------------
        | Duplicate Check
        |--------------------------------------------------------------------------
        */
        $duplicate = TestBankQuestion::where(
            'faculty_id',
            $request->user()->id
        )
            ->where(
                'subject_id',
                $subjectId
            )
            ->where(
                'question_type',
                $validated['question_type']
            )
            ->where(
                'question',
                $questionText
            )
            ->where(
                'id',
                '!=',
                $question->id
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'status' => false,
                'message' => 'This question already exists in your Test Bank for this subject.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Question
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use (
            $question,
            $validated,
            $subjectId,
            $classIds,
            $questionText
        ) {
            $question->update([
                'subject_id' => $subjectId,
                'question' => $questionText,
                'question_type' => $validated['question_type'],
                'competency' => $validated['competency'] ?? null,
                'answer' => $validated['answer'] ?? null,
                'points' => $validated['points'] ?? 1,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update class assignments
            |--------------------------------------------------------------------------
            |
            | sync() adds selected classes and removes unselected classes.
            |
            */
            $question->classes()->sync(
                $classIds
            );

            /*
            |--------------------------------------------------------------------------
            | Replace Options
            |--------------------------------------------------------------------------
            */
            TestBankQuestionOption::where(
                'test_bank_question_id',
                $question->id
            )->delete();

            $this->saveOptions(
                $question,
                $validated['question_type'],
                $validated['options'] ?? []
            );
        });

        return response()->json([
            'status' => true,
            'message' => 'Test Bank question updated successfully.',
            'data' => $question
                ->fresh()
                ->load([
                    'options',
                    'subject',
                    'classes.subject',
                ]),
        ]);
    }

    /**
     * Save options for Multiple Choice and True/False.
     */
    private function saveOptions(
        TestBankQuestion $question,
        string $questionType,
        array $options
    ): void {
        if (
            !in_array(
                $questionType,
                [
                    'multiple_choice',
                    'true_false',
                ]
            )
        ) {
            return;
        }

        foreach ($options as $option) {
            TestBankQuestionOption::create([
                'test_bank_question_id' =>
                    $question->id,

                'option_text' =>
                    $option['option_text'],

                'is_correct' =>
                    $option['is_correct'],
            ]);
        }
    }

    /**
     * Verify logged-in user is Faculty.
     */
    private function authorizeFaculty(
        Request $request
    ): void {
        $user = $request->user();

        if (
            !$user ||
            $user->role !== 'faculty'
        ) {
            abort(
                403,
                'Only faculty members can access the Test Bank.'
            );
        }
    }

    /**
     * Verify faculty owns one class.
     */
    private function authorizeFacultyOwnsClass(
        Request $request,
        int $classId
    ): SchoolClass {
        $user = $request->user();

        if (
            !$user ||
            $user->role !== 'faculty'
        ) {
            abort(
                403,
                'Only faculty members can access the Test Bank.'
            );
        }

        $schoolClass = SchoolClass::where(
            'id',
            $classId
        )
            ->where(
                'faculty_id',
                $user->id
            )
            ->first();

        if (!$schoolClass) {
            abort(
                403,
                'You are not authorized to access this class.'
            );
        }

        return $schoolClass;
    }

    /**
     * Verify faculty owns ALL selected classes
     * and all classes belong to the selected subject.
     */
    private function authorizeFacultyClasses(
        Request $request,
        array $classIds,
        int $subjectId
    ): void {
        $user = $request->user();

        if (
            !$user ||
            $user->role !== 'faculty'
        ) {
            abort(
                403,
                'Only faculty members can access the Test Bank.'
            );
        }

        $validClassIds = SchoolClass::where(
            'faculty_id',
            $user->id
        )
            ->where(
                'subject_id',
                $subjectId
            )
            ->whereIn(
                'id',
                $classIds
            )
            ->pluck('id')
            ->map(
                fn ($id) => (int) $id
            )
            ->all();

        sort($validClassIds);

        $requestedClassIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $classIds
                )
            )
        );

        sort($requestedClassIds);

        if (
            $validClassIds !==
            $requestedClassIds
        ) {
            abort(
                403,
                'One or more selected classes do not belong to you or do not match the selected subject.'
            );
        }
    }

    /**
     * Enable / Disable a Test Bank question.
     */
    public function updateStatus(
        Request $request,
        $id
    ) {
        $this->authorizeFaculty($request);

        $validated = $request->validate([
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $question = TestBankQuestion::where(
            'faculty_id',
            $request->user()->id
        )->findOrFail($id);

        $question->is_active =
            $validated['is_active'];

        $question->save();

        return response()->json([
            'status' => true,

            'message' =>
                $question->is_active
                    ? 'Question enabled successfully.'
                    : 'Question disabled successfully.',

            'data' => [
                'id' => $question->id,
                'is_active' =>
                    (bool) $question->is_active,
            ],
        ]);
    }
}
