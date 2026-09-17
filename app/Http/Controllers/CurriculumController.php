<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CurriculumController extends Controller
{
    public function index()
    {
        $curricula = Curriculum::with([
            'strand',
            'subjects',
        ])
            ->orderByRaw("
                CASE
                    WHEN grade = 'Grade 11' THEN 1
                    WHEN grade = 'Grade 12' THEN 2
                    ELSE 3
                END
            ")
            ->get();

        return response()->json([
            'success' => true,
            'data' => $curricula,
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
            'subject_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'subject_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:subjects,id',
            ],
        ]);

        $exists = Curriculum::where(
            'grade',
            $validated['grade']
        )
            ->where(
                'strand_id',
                $validated['strand_id']
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' =>
                    'A curriculum already exists for this grade level and strand.',
            ], 422);
        }

        $curriculum = DB::transaction(
            function () use ($validated) {
                $curriculum =
                    Curriculum::create([
                        'grade' =>
                            $validated['grade'],
                        'strand_id' =>
                            $validated['strand_id'],
                    ]);

                $curriculum->subjects()->sync(
                    $validated['subject_ids']
                );

                return $curriculum;
            }
        );

        $curriculum->load([
            'strand',
            'subjects',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Curriculum created successfully.',
            'data' => $curriculum,
        ], 201);
    }

    public function show($id)
    {
        $curriculum = Curriculum::with([
            'strand',
            'subjects',
        ])->find($id);

        if (!$curriculum) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Curriculum not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $curriculum,
        ]);
    }

    public function update(
        Request $request,
        $id
    ) {
        $curriculum =
            Curriculum::find($id);

        if (!$curriculum) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Curriculum not found.',
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
            'subject_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'subject_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:subjects,id',
            ],
        ]);

        $exists = Curriculum::where(
            'grade',
            $validated['grade']
        )
            ->where(
                'strand_id',
                $validated['strand_id']
            )
            ->where(
                'id',
                '!=',
                $curriculum->id
            )
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' =>
                    'A curriculum already exists for this grade level and strand.',
            ], 422);
        }

        DB::transaction(
            function () use (
                $curriculum,
                $validated
            ) {
                $curriculum->update([
                    'grade' =>
                        $validated['grade'],
                    'strand_id' =>
                        $validated['strand_id'],
                ]);

                $curriculum->subjects()->sync(
                    $validated['subject_ids']
                );
            }
        );

        $curriculum->load([
            'strand',
            'subjects',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Curriculum updated successfully.',
            'data' => $curriculum,
        ]);
    }

    public function destroy($id)
    {
        $curriculum =
            Curriculum::find($id);

        if (!$curriculum) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Curriculum not found.',
            ], 404);
        }

        $curriculum->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Curriculum deleted successfully.',
        ]);
    }
}
