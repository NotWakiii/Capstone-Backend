<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Strand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::with('strand')
            ->orderByRaw("
                CASE
                    WHEN grade = 'Grade 11' THEN 1
                    WHEN grade = 'Grade 12' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('section')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade' => [
                'required',
                Rule::in([
                    'Grade 11',
                    'Grade 12',
                ]),
            ],
            'strand_id' => [
                'required',
                'integer',
                'exists:strands,id',
            ],
            'section' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $sectionName = trim(
            $validated['section']
        );

        $exists = Section::where(
            'grade',
            $validated['grade']
        )
            ->where(
                'strand_id',
                $validated['strand_id']
            )
            ->where(
                'section',
                $sectionName
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This grade, strand, and section combination already exists.',
                'errors' => [
                    'section' => [
                        'This section already exists under the selected grade and strand.',
                    ],
                ],
            ], 422);
        }

        $section = Section::create([
            'grade' =>
                $validated['grade'],
            'strand_id' =>
                $validated['strand_id'],
            'section' =>
                $sectionName,
        ]);

        $section->load('strand');

        return response()->json([
            'success' => true,
            'message' =>
                'Section created successfully.',
            'data' => $section,
        ], 201);
    }

    public function show($id)
    {
        $section = Section::with(
            'strand'
        )->find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Section not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $section,
        ]);
    }

    public function update(
        Request $request,
        $id
    ) {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Section not found.',
            ], 404);
        }

        $validated = $request->validate([
            'grade' => [
                'required',
                Rule::in([
                    'Grade 11',
                    'Grade 12',
                ]),
            ],
            'strand_id' => [
                'required',
                'integer',
                'exists:strands,id',
            ],
            'section' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $sectionName = trim(
            $validated['section']
        );

        $exists = Section::where(
            'grade',
            $validated['grade']
        )
            ->where(
                'strand_id',
                $validated['strand_id']
            )
            ->where(
                'section',
                $sectionName
            )
            ->where(
                'id',
                '!=',
                $section->id
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This grade, strand, and section combination already exists.',
                'errors' => [
                    'section' => [
                        'This section already exists under the selected grade and strand.',
                    ],
                ],
            ], 422);
        }

        $section->update([
            'grade' =>
                $validated['grade'],
            'strand_id' =>
                $validated['strand_id'],
            'section' =>
                $sectionName,
        ]);

        $section->load('strand');

        return response()->json([
            'success' => true,
            'message' =>
                'Section updated successfully.',
            'data' => $section,
        ]);
    }

    public function destroy($id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Section not found.',
            ], 404);
        }

        $sectionName =
            $section->section;

        $section->delete();

        return response()->json([
            'success' => true,
            'message' =>
                "Section {$sectionName} deleted successfully.",
        ]);
    }
}
