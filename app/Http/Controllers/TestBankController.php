<?php

namespace App\Http\Controllers;

use App\Models\TestBankQuestion;
use App\Models\TestBankQuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\SchoolClass;

class TestBankController extends Controller
{
    /**
     * Display the logged-in faculty's Test Bank.
     */
    public function index(Request $request)
    {
        $this->authorizeFaculty($request);
        $facultyId = $request->user()->id;

        $query = TestBankQuestion::with(['options', 'subject'])
            ->where('faculty_id', $facultyId)
            ->latest();

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('question_type')) {
            $query->where('question_type', $request->question_type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('competency', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'status' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * Add one question to Test Bank.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question' => ['required', 'string'],
            'question_type' => [
                'required',
                Rule::in([
                    'multiple_choice',
                    'true_false',
                    'identification'
                ])
            ],
            'competency' => ['nullable', 'string'],
            'answer' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:1'],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
        ]);
        $this->authorizeFacultySubject(
            $request,
            (int) $validated['subject_id']
        );

        $facultyId = $request->user()->id;

        $facultyId = $request->user()->id;
        $questionText = trim($validated['question']);

        $existing = TestBankQuestion::where('faculty_id', $facultyId)
            ->where('subject_id', $validated['subject_id'])
            ->where('question_type', $validated['question_type'])
            ->where('question', $questionText)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => true,
                'already_exists' => true,
                'message' => 'Question is already in the Test Bank.',
                'data' => $existing->load('options')
            ]);
        }

        $question = DB::transaction(function () use (
            $validated,
            $facultyId,
            $questionText
        ) {
            $question = TestBankQuestion::create([
                'faculty_id' => $facultyId,
                'subject_id' => $validated['subject_id'],
                'question' => $questionText,
                'question_type' => $validated['question_type'],
                'competency' => $validated['competency'] ?? null,
                'answer' => $validated['answer'] ?? null,
                'points' => $validated['points'] ?? 1,
            ]);

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
            'data' => $question->load(['options', 'subject'])
        ], 201);
    }

    /**
     * Add multiple generated questions to Test Bank.
     */
    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.question_type' => [
                'required',
                Rule::in([
                    'multiple_choice',
                    'true_false',
                    'identification'
                ])
            ],
            'questions.*.competency' => ['nullable', 'string'],
            'questions.*.answer' => ['nullable', 'string'],
            'questions.*.points' => ['nullable', 'integer', 'min:1'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.option_text' => ['required_with:questions.*.options', 'string'],
            'questions.*.options.*.is_correct' => ['required_with:questions.*.options', 'boolean'],
        ]);
        $this->authorizeFacultySubject(
            $request,
            (int) $validated['subject_id']
        );

        $facultyId = $request->user()->id;

        $facultyId = $request->user()->id;
        $subjectId = $validated['subject_id'];
        $added = 0;
        $skipped = 0;
        $addedQuestions = [];
        $skippedQuestions = [];

        DB::transaction(function () use (
            $validated,
            $facultyId,
            $subjectId,
            &$added,
            &$skipped,
            &$addedQuestions,
            &$skippedQuestions
        ) {
            foreach ($validated['questions'] as $item) {
                $questionText = trim($item['question']);

                $existing = TestBankQuestion::where('faculty_id', $facultyId)
                    ->where('subject_id', $subjectId)
                    ->where('question_type', $item['question_type'])
                    ->where('question', $questionText)
                    ->first();

                if ($existing) {
                    $skipped++;

                    $skippedQuestions[] = [
                        'question' => $questionText,
                        'test_bank_question_id' => $existing->id
                    ];

                    continue;
                }

                $question = TestBankQuestion::create([
                    'faculty_id' => $facultyId,
                    'subject_id' => $subjectId,
                    'question' => $questionText,
                    'question_type' => $item['question_type'],
                    'competency' => $item['competency'] ?? null,
                    'answer' => $item['answer'] ?? null,
                    'points' => $item['points'] ?? 1,
                ]);

                $this->saveOptions(
                    $question,
                    $item['question_type'],
                    $item['options'] ?? []
                );

                $added++;

                $addedQuestions[] = [
                    'question' => $questionText,
                    'test_bank_question_id' => $question->id
                ];
            }
        });

        return response()->json([
            'status' => true,
            'message' => $added . ' question(s) added to Test Bank. ' .
                $skipped . ' existing question(s) skipped.',
            'added' => $added,
            'skipped' => $skipped,
            'total' => count($validated['questions']),
            'added_questions' => $addedQuestions,
            'skipped_questions' => $skippedQuestions
        ]);
    }

    /**
     * Display one Test Bank question.
     */
    public function show(Request $request, string $id)
    {
        $question = TestBankQuestion::with(['options', 'subject'])
            ->where('faculty_id', $request->user()->id)
            ->find($id);

        if (!$question) {
            return response()->json([
                'status' => false,
                'message' => 'Test Bank question not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $question
        ]);
        $this->authorizeFaculty($request);
    }

    /**
     * Update a Test Bank question.
     */
    public function update(Request $request, string $id)
    {
        $question = TestBankQuestion::where(
            'faculty_id',
            $request->user()->id
        )->find($id);

        if (!$question) {
            return response()->json([
                'status' => false,
                'message' => 'Test Bank question not found.'
            ], 404);
        }

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question' => ['required', 'string'],
            'question_type' => [
                'required',
                Rule::in([
                    'multiple_choice',
                    'true_false',
                    'identification'
                ])
            ],
            'competency' => ['nullable', 'string'],
            'answer' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:1'],
            'options' => ['nullable', 'array'],
            'options.*.option_text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
        ]);
        $this->authorizeFacultySubject(
            $request,
            (int) $validated['subject_id']
        );

        $questionText = trim($validated['question']);

        $duplicate = TestBankQuestion::where(
                'faculty_id',
                $request->user()->id
            )
            ->where('subject_id', $validated['subject_id'])
            ->where('question_type', $validated['question_type'])
            ->where('question', $questionText)
            ->where('id', '!=', $question->id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'status' => false,
                'message' => 'This question already exists in the Test Bank.'
            ], 422);
        }

        DB::transaction(function () use (
            $question,
            $validated,
            $questionText
        ) {
            $question->update([
                'subject_id' => $validated['subject_id'],
                'question' => $questionText,
                'question_type' => $validated['question_type'],
                'competency' => $validated['competency'] ?? null,
                'answer' => $validated['answer'] ?? null,
                'points' => $validated['points'] ?? 1,
            ]);

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
            'data' => $question->fresh()->load(['options', 'subject'])
        ]);
    }

    /**
     * Delete a Test Bank question.
     */
    public function destroy(Request $request, string $id)
    {
        $question = TestBankQuestion::where(
            'faculty_id',
            $request->user()->id
        )->find($id);

        if (!$question) {
            return response()->json([
                'status' => false,
                'message' => 'Test Bank question not found.'
            ], 404);
        }

        $question->delete();

        return response()->json([
            'status' => true,
            'message' => 'Question deleted from Test Bank successfully.'
        ]);
        $this->authorizeFaculty($request);
    }

    /**
     * Save options for Multiple Choice and True/False.
     */
    private function saveOptions(
        TestBankQuestion $question,
        string $questionType,
        array $options
    ): void {
        if (!in_array(
            $questionType,
            ['multiple_choice', 'true_false']
        )) {
            return;
        }

        foreach ($options as $option) {
            TestBankQuestionOption::create([
                'test_bank_question_id' => $question->id,
                'option_text' => $option['option_text'],
                'is_correct' => $option['is_correct'],
            ]);
        }
    }
    private function authorizeFaculty(Request $request): void
    {
        $user = $request->user();

        if (!$user || $user->role !== 'faculty') {
            abort(403, 'Only faculty members can access the Test Bank.');
        }
    }
    private function authorizeFacultySubject(Request $request, int $subjectId): void
    {
        $user = $request->user();

        if (!$user || $user->role !== 'faculty') {
            abort(403, 'Only faculty members can access the Test Bank.');
        }

        $teachesSubject = SchoolClass::where('faculty_id', $user->id)
            ->where('subject_id', $subjectId)
            ->exists();

        if (!$teachesSubject) {
            abort(403, 'You are not authorized to use this subject in your Test Bank.');
        }
    }
}
