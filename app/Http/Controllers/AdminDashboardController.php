<?php
namespace App\Http\Controllers;
use App\Models\Section;
use App\Models\Strand;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalFaculty = User::where('role', 'faculty')->count();
        $totalStudents = User::where('role', 'student')->count();
        $totalStrands = Strand::count();
        $totalSections = Section::count();
        $maleStudents = User::where('role', 'student')
            ->where('sex', 'Male')
            ->count();
        $femaleStudents = User::where('role', 'student')
            ->where('sex', 'Female')
            ->count();
        $strands = Strand::orderBy('name')
            ->get(['id', 'name']);
        $breakdown = User::query()
            ->join('sections', 'users.section_id', '=', 'sections.id')
            ->select(
                'users.strand_id',
                'sections.grade',
                'users.sex',
                DB::raw('COUNT(users.id) as total')
            )
            ->where('users.role', 'student')
            ->whereNotNull('users.strand_id')
            ->whereNotNull('users.section_id')
            ->groupBy(
                'users.strand_id',
                'sections.grade',
                'users.sex'
            )
            ->get();
        $studentsByStrandGrade = [];
        $studentsByStrand = [];
        foreach ($strands as $strand) {
            $strandTotal = 0;
            foreach (['Grade 11', 'Grade 12'] as $grade) {
                $male = (int) $breakdown
                    ->where('strand_id', $strand->id)
                    ->where('grade', $grade)
                    ->where('sex', 'Male')
                    ->sum('total');
                $female = (int) $breakdown
                    ->where('strand_id', $strand->id)
                    ->where('grade', $grade)
                    ->where('sex', 'Female')
                    ->sum('total');
                $total = $male + $female;
                $strandTotal += $total;
                $studentsByStrandGrade[] = [
                    'strand_id' => $strand->id,
                    'strand' => $strand->name,
                    'grade' => $grade,
                    'label' => $strand->name . ' ' . str_replace('Grade ', '', $grade),
                    'male' => $male,
                    'female' => $female,
                    'total' => $total,
                ];
            }
            $studentsByStrand[] = [
                'strand_id' => $strand->id,
                'strand' => $strand->name,
                'total' => $strandTotal,
            ];
        }
        return response()->json([
            'success' => true,
            'data' => [
                'cards' => [
                    'faculty' => $totalFaculty,
                    'students' => $totalStudents,
                    'strands' => $totalStrands,
                    'sections' => $totalSections,
                ],
                'gender_distribution' => [
                    'male' => $maleStudents,
                    'female' => $femaleStudents,
                ],
                'students_by_strand_grade' => $studentsByStrandGrade,
                'students_by_strand' => $studentsByStrand,
            ],
        ]);
    }
}
