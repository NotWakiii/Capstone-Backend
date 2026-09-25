<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Strand;
use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Summary cards
        $totalFaculty = User::where('role', 'faculty')->count();
        $totalStudents = User::where('role', 'student')->count();
        $totalClasses = SchoolClass::count();
        $totalSections = Section::count();

        // Student gender distribution
        $maleStudents = User::where('role', 'student')
            ->where('sex', 'Male')
            ->count();

        $femaleStudents = User::where('role', 'student')
            ->where('sex', 'Female')
            ->count();

        // User distribution
        $userDistribution = [
            'faculty' => $totalFaculty,
            'students' => $totalStudents,
        ];

        // Classes by strand
        $classesByStrand = Strand::query()
            ->leftJoin('school_classes', 'strands.id', '=', 'school_classes.strand_id')
            ->select(
                'strands.id',
                'strands.name',
                DB::raw('COUNT(school_classes.id) as total')
            )
            ->groupBy('strands.id', 'strands.name')
            ->orderBy('strands.name')
            ->get()
            ->map(function ($strand) {
                return [
                    'strand_id' => $strand->id,
                    'strand' => $strand->name,
                    'total' => (int) $strand->total,
                ];
            })
            ->values();

        // Classes by grade level
        $classesByGrade = SchoolClass::query()
            ->join('sections', 'school_classes.section_id', '=', 'sections.id')
            ->select(
                'sections.grade',
                DB::raw('COUNT(school_classes.id) as total')
            )
            ->whereIn('sections.grade', ['Grade 11', 'Grade 12'])
            ->groupBy('sections.grade')
            ->pluck('total', 'grade');

        $gradeDistribution = [
            [
                'grade' => 'Grade 11',
                'total' => (int) ($classesByGrade['Grade 11'] ?? 0),
            ],
            [
                'grade' => 'Grade 12',
                'total' => (int) ($classesByGrade['Grade 12'] ?? 0),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'cards' => [
                    'faculty' => $totalFaculty,
                    'students' => $totalStudents,
                    'classes' => $totalClasses,
                    'sections' => $totalSections,
                ],

                'user_distribution' => $userDistribution,

                'gender_distribution' => [
                    'male' => $maleStudents,
                    'female' => $femaleStudents,
                ],

                'classes_by_strand' => $classesByStrand,

                'classes_by_grade' => $gradeDistribution,
            ],
        ]);
    }
}
