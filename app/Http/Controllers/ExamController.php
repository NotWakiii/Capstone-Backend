<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use Illuminate\Support\Str;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;

use App\Models\ExamSession;

class ExamController extends Controller
{
    /**
     * Display all exams
     */
    public function index()
    {
       $exams = Exam::with(['user', 'questions.options'])->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $exams
        ]);
    }

    /**
     * Store exam
     */
  public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'course' => 'required|string|max:255',
        'passing' => 'required|integer|min:1|max:100',
        'duration' => 'required|integer|min:1',
        'questions' => 'required|array|min:1',
    ]);

    DB::beginTransaction();

    try {
        $exam = Exam::create([
            'title' => $request->title,
            'description' => $request->description,
            'course' => $request->course,
            'duration' => $request->duration,
            'passing' => $request->passing,
            'access_code' => strtoupper(Str::random(6)),
            'created_by' => auth()->id(),
            'status' => 'draft'
        ]);

        foreach ($request->questions as $index => $item) {
            $type = match ($item['type']) {
                'Multiple Choice' => 'multiple_choice',
                'True or False' => 'true_false',
                'Identification' => 'identification',
                'Essay' => 'essay',
                default => 'multiple_choice'
            };

            $question = Question::create([
                'exam_id' => $exam->id,
                'question' => $item['question'],
                'question_type' => $type,
                'answer' => $item['answer'] ?? null,
                'points' => $item['points'] ?? 1,
                'time_limit' => $item['time'] ?? 30,
                'question_order' => $index + 1,
            ]);

            if ($type === 'multiple_choice' && isset($item['options'])) {
                foreach ($item['options'] as $optionIndex => $optionText) {
                    if (!$optionText) continue;

                    $letter = chr(65 + $optionIndex);

                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $optionText,
                        'is_correct' => ($item['answer'] ?? '') === $letter,
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Exam created successfully',
            'data' => $exam->load('questions')
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'Failed to create exam',
            'error' => $e->getMessage()
        ], 500);
    }
}

   /**
 * Show single exam
 */
public function show(string $id)
{
    $exam = Exam::with([
        'questions.options'
    ])->find($id);

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Exam not found'
        ], 404);
    }

    return response()->json([
        'status' => true,
        'data' => $exam
    ]);
}

public function restartExam($id)
{
    $exam = Exam::find($id);

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Exam not found'
        ], 404);
    }

   

    $exam->update([
        'status' => 'published',
        'access_code' => strtoupper(Str::random(6)),
        'started_at' => null,
        'ended_at' => null
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Exam restarted successfully',
        'data' => $exam
    ]);
}

public function endExam($id)
{
    $exam = Exam::find($id);

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Exam not found'
        ], 404);
    }

    $exam->update([
        'status' => 'finished',
        'ended_at' => now()
    ]);

    ExamSession::where('exam_id', $id)
        ->where('status', 'ongoing')
        ->update([
            'status' => 'submitted',
            'submitted_at' => now()
        ]);

    return response()->json([
        'status' => true,
        'message' => 'Exam ended successfully',
        'data' => $exam
    ]);
}

    /**
     * Update exam
     */
   public function update(Request $request, string $id)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'course' => 'required|string|max:255',
        'duration' => 'required|integer|min:1',
        'passing' => 'required|integer|min:1|max:100',
        'questions' => 'required|array|min:1',
    ]);

    $exam = Exam::find($id);

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Exam not found'
        ], 404);
    }

    DB::beginTransaction();

    try {
        $exam->update([
            'title' => $request->title,
            'description' => $request->description,
            'course' => $request->course,
            'duration' => $request->duration,
            'passing' => $request->passing,
        ]);

        $exam->questions()->delete();

        foreach ($request->questions as $index => $item) {
            $type = match ($item['type']) {
                'Multiple Choice' => 'multiple_choice',
                'True or False' => 'true_false',
                'Identification' => 'identification',
                'Essay' => 'essay',
                default => 'multiple_choice'
            };

            $question = Question::create([
                'exam_id' => $exam->id,
                'question' => $item['question'],
                'question_type' => $type,
                'answer' => $item['answer'] ?? null,
                'points' => $item['points'] ?? 1,
                'time_limit' => $item['time'] ?? 30,
                'question_order' => $index + 1,
            ]);

            if ($type === 'multiple_choice' && isset($item['options'])) {
                foreach ($item['options'] as $optionIndex => $optionText) {
                    if (!$optionText) continue;

                    $letter = chr(65 + $optionIndex);

                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $optionText,
                        'is_correct' => ($item['answer'] ?? '') === $letter,
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Exam updated successfully',
            'data' => $exam->load('questions.options')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'Failed to update exam',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function publish($id)
{
    $exam = Exam::find($id);

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Exam not found'
        ], 404);
    }

    $exam->update([
        'status' => 'published'
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Exam published successfully',
        'data' => $exam
    ]);
}

public function startExam($id)
{
    $exam = Exam::find($id);

    if (!$exam) {
        return response()->json([
            'status' => false,
            'message' => 'Exam not found'
        ], 404);
    }

    $exam->update([
        'status' => 'started',
        'started_at' => now()
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Exam started successfully',
        'data' => $exam
    ]);
}

    /**
     * Delete exam
     */
    public function destroy(string $id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found'
            ], 404);
        }

        $exam->delete();

        return response()->json([
            'status' => true,
            'message' => 'Exam deleted successfully'
        ]);
    }
}

