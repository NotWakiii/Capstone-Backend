<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    /**
     * Display all exams
     */
    public function index()
    {
        $exams = Exam::with('user')->latest()->get();

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
            'title' => 'required',
            'duration' => 'required|integer'
        ]);

        $exam = Exam::create([
            'title' => $request->title,
            'description' => $request->description,
            'duration' => $request->duration,
            'access_code' => strtoupper(Str::random(6)),
            'created_by' => auth()->id(),
            'status' => 'draft'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Exam created successfully',
            'data' => $exam
        ]);
    }

    /**
     * Show single exam
     */
    public function show(string $id)
    {
        $exam = Exam::find($id);

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

    /**
     * Update exam
     */
    public function update(Request $request, string $id)
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' => 'Exam not found'
            ], 404);
        }

        $exam->update([
            'title' => $request->title,
            'description' => $request->description,
            'duration' => $request->duration,
            'status' => $request->status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Exam updated successfully',
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
