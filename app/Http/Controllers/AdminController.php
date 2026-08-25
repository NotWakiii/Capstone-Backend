<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Exam;
use App\Models\ExamSession;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalFaculty = User::where(
            'role',
            'faculty'
        )->count();

        $totalExams = Exam::count();

        $totalExaminees = ExamSession::count();

        $submittedExams = ExamSession::where(
            'status',
            'submitted'
        )->count();

        return response()->json([
            'status' => true,

            'data' => [
                'total_faculty' =>
                    $totalFaculty,

                'total_exams' =>
                    $totalExams,

                'total_examinees' =>
                    $totalExaminees,

                'submitted_results' =>
                    $submittedExams,
            ],
        ]);
    }
}
