<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                'unique:subjects,name',
            ],
        ]);

        $subject = Subject::create([
            'name' => trim($validated['name']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully.',
            'data' => $subject,
        ], 201);
    }

    public function show($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subject,
        ]);
    }

    public function update(
        Request $request,
        $id
    ) {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique(
                    'subjects',
                    'name'
                )->ignore($subject->id),
            ],
        ]);

        $subject->update([
            'name' => trim($validated['name']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully.',
            'data' => $subject,
        ]);
    }

    public function destroy($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found.',
            ], 404);
        }

        $subjectName = $subject->name;

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' =>
                "Subject {$subjectName} deleted successfully.",
        ]);
    }
}
