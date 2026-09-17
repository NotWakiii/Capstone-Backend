<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolYearController extends Controller
{
    /**
     * Display all school years.
     */
    public function index()
    {
        $schoolYears = SchoolYear::orderBy('year', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schoolYears,
        ]);
    }

    /**
     * Store a new school year.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                'unique:school_years,year',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $schoolYear = DB::transaction(function () use ($validated) {

            /*
             * Only one school year can be active.
             */
            if ($validated['status'] === 'active') {
                SchoolYear::where(
                    'status',
                    'active'
                )->update([
                    'status' => 'inactive',
                ]);
            }

            return SchoolYear::create([
                'year' =>
                    $validated['year'],

                'status' =>
                    $validated['status'],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' =>
                'School year created successfully.',
            'data' => $schoolYear,
        ], 201);
    }

    /**
     * Display one school year.
     */
    public function show($id)
    {
        $schoolYear =
            SchoolYear::find($id);

        if (!$schoolYear) {
            return response()->json([
                'success' => false,
                'message' =>
                    'School year not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $schoolYear,
        ]);
    }

    /**
     * Update a school year.
     */
    public function update(
        Request $request,
        $id
    ) {
        $schoolYear =
            SchoolYear::find($id);

        if (!$schoolYear) {
            return response()->json([
                'success' => false,
                'message' =>
                    'School year not found.',
            ], 404);
        }

        $validated = $request->validate([
            'year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique(
                    'school_years',
                    'year'
                )->ignore(
                    $schoolYear->id
                ),
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $schoolYear
        ) {
            /*
             * If this school year becomes active,
             * make all other school years inactive.
             */
            if (
                $validated['status'] ===
                'active'
            ) {
                SchoolYear::where(
                    'id',
                    '!=',
                    $schoolYear->id
                )
                    ->where(
                        'status',
                        'active'
                    )
                    ->update([
                        'status' =>
                            'inactive',
                    ]);
            }

            $schoolYear->update([
                'year' =>
                    $validated['year'],

                'status' =>
                    $validated['status'],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' =>
                'School year updated successfully.',
            'data' =>
                $schoolYear->fresh(),
        ]);
    }

    /**
     * Delete a school year.
     */
    public function destroy($id)
    {
        $schoolYear =
            SchoolYear::find($id);

        if (!$schoolYear) {
            return response()->json([
                'success' => false,
                'message' =>
                    'School year not found.',
            ], 404);
        }

        $year =
            $schoolYear->year;

        $schoolYear->delete();

        return response()->json([
            'success' => true,
            'message' =>
                "School year {$year} deleted successfully.",
        ]);
    }
}
