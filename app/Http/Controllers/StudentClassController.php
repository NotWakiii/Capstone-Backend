<?php

namespace App\Http\Controllers;

use App\Models\ClassStudent;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class StudentClassController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        if (!$student || $student->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $activeSchoolYear = SchoolYear::where('status', 'active')->first();

        if (!$activeSchoolYear) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $enrollments = ClassStudent::where('student_id', $student->id)
            ->whereHas('schoolClass', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->with([
                'schoolClass.subject:id,name',
                'schoolClass.strand:id,name',
                'schoolClass.sectionData:id,grade,strand_id,section',
                'schoolClass.schoolYear:id,year,status',
                'schoolClass.faculty:id,name',
            ])
            ->get();

        $classes = $enrollments
            ->map(function ($enrollment) {
                $class = $enrollment->schoolClass;

                return [
                    'id' => $class->id,
                    'grade' => $class->grade,
                    'semester' => $class->semester,
                    'subject' => $class->subject?->name,
                    'strand' => $class->strand?->name,
                    'section' => $class->sectionData?->section ?? $class->section,
                    'school_year' => $class->schoolYear?->year,
                    'faculty' => $class->faculty?->name,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $classes,
        ]);
    }
    public function join(Request $request)
    {
        $student = $request->user();

        if (!$student || $student->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($student->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your student account is inactive.',
            ], 403);
        }

        $validated = $request->validate([
            'class_code' => [
                'required',
                'string',
                'max:10',
            ],
        ]);

        $classCode = strtoupper(trim($validated['class_code']));

        $class = SchoolClass::where('class_code', $classCode)
            ->with([
                'subject:id,name',
                'strand:id,name',
                'sectionData:id,grade,strand_id,section',
                'schoolYear:id,year,status',
                'faculty:id,name',
            ])
            ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid class code.',
            ], 404);
        }

        $activeSchoolYear = SchoolYear::where('status', 'active')->first();

        if (!$activeSchoolYear) {
            return response()->json([
                'success' => false,
                'message' => 'There is no active school year.',
            ], 422);
        }

        if ((int) $class->school_year_id !== (int) $activeSchoolYear->id) {
            return response()->json([
                'success' => false,
                'message' => 'This class is not from the active school year.',
            ], 422);
        }

        $existingEnrollment = ClassStudent::where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this class.',
            ], 422);
        }

        $enrollment = ClassStudent::create([
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class joined successfully.',
            'data' => [
                'enrollment_id' => $enrollment->id,
                'class' => [
                    'id' => $class->id,
                    'grade' => $class->grade,
                    'semester' => $class->semester,
                    'subject' => $class->subject?->name,
                    'strand' => $class->strand?->name,
                    'section' => $class->sectionData?->section ?? $class->section,
                    'school_year' => $class->schoolYear?->year,
                    'faculty' => $class->faculty?->name,
                ],
            ],
        ], 201);
    }
    public function show(Request $request, $id)
    {
        $student = $request->user();

        if (!$student || $student->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $enrollment = ClassStudent::where('student_id', $student->id)
            ->where('class_id', $id)
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this class.',
            ], 403);
        }

        $class = $enrollment->schoolClass()
            ->with([
                'subject:id,name',
                'strand:id,name',
                'sectionData:id,grade,strand_id,section',
                'schoolYear:id,year,status',
                'faculty:id,name',
            ])
            ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $assessments = \App\Models\Exam::where('class_id', $class->id)
            ->whereIn('status', ['published', 'started', 'finished'])
            ->withCount('questions')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'assessment_type' => $exam->assessment_type,
                    'duration' => $exam->duration,
                    'passing' => $exam->passing,
                    'status' => $exam->status,
                    'questions_count' => $exam->questions_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'class' => [
                    'id' => $class->id,
                    'grade' => $class->grade,
                    'semester' => $class->semester,
                    'subject' => $class->subject?->name,
                    'strand' => $class->strand?->name,
                    'section' => $class->sectionData?->section ?? $class->section,
                    'school_year' => $class->schoolYear?->year,
                    'faculty' => $class->faculty?->name,
                ],
                'assessments' => $assessments,
            ],
        ]);
    }
}
