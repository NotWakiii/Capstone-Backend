<?php

namespace App\Http\Controllers;

use App\Models\Exam;

class AdminExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with('user')
            ->withCount([
                'questions',
                'sessions',
            ])
            ->latest()
            ->get()
            ->map(function ($exam) {

                return [
                    'id' =>
                        $exam->id,

                    'title' =>
                        $exam->title,

                    'description' =>
                        $exam->description,

                    'faculty' => [
                        'id' =>
                            $exam->user?->id,

                        'name' =>
                            $exam->user?->name
                            ?? 'Unknown Faculty',

                        'email' =>
                            $exam->user?->email,
                    ],

                    'grade' =>
                        $exam->grade,

                    'section' =>
                        $exam->section,

                    'subject' =>
                        $exam->subject,

                    'duration' =>
                        $exam->duration,

                    'passing' =>
                        $exam->passing,

                    'access_code' =>
                        $exam->access_code,

                    'status' =>
                        $exam->status,

                    'questions_count' =>
                        $exam->questions_count,

                    'students_count' =>
                        $exam->sessions_count,

                    'created_at' =>
                        $exam->created_at,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $exams,
        ]);
    }
}
