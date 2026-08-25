<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\ClassStudent;
use Illuminate\Http\Request;

class ClassManagementController extends Controller
{
    /**
     * Get all classes owned by the logged-in faculty.
     */
    public function index(Request $request)
    {
        $faculty = $request->user();

        $classes = SchoolClass::where(
            'faculty_id',
            $faculty->id
        )
        ->with([
            'students' => function ($query) {
                $query->orderBy('student_name');
            }
        ])
        ->orderBy('grade')
        ->orderBy('section')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }


    /**
     * Create a class.
     */
    public function store(Request $request)
    {
        $faculty = $request->user();

        $validated = $request->validate([
            'grade' => [
                'required',
                'string',
                'max:100'
            ],

            'section' => [
                'required',
                'string',
                'max:100'
            ],
        ]);

        $existingClass = SchoolClass::where(
            'faculty_id',
            $faculty->id
        )
        ->where(
            'grade',
            $validated['grade']
        )
        ->where(
            'section',
            $validated['section']
        )
        ->first();

        if ($existingClass) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This class already exists.'
            ], 422);
        }

        $class = SchoolClass::create([
            'faculty_id' =>
                $faculty->id,

            'grade' =>
                trim(
                    $validated['grade']
                ),

            'section' =>
                trim(
                    $validated['section']
                ),
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Class created successfully.',
            'data' => $class
        ], 201);
    }


    /**
     * Update a class.
     */
    public function update(
        Request $request,
        $id
    ) {
        $faculty = $request->user();

        $class = SchoolClass::where(
            'id',
            $id
        )
        ->where(
            'faculty_id',
            $faculty->id
        )
        ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Class not found.'
            ], 404);
        }

        $validated = $request->validate([
            'grade' => [
                'required',
                'string',
                'max:100'
            ],

            'section' => [
                'required',
                'string',
                'max:100'
            ],
        ]);

        $duplicate = SchoolClass::where(
            'faculty_id',
            $faculty->id
        )
        ->where(
            'grade',
            $validated['grade']
        )
        ->where(
            'section',
            $validated['section']
        )
        ->where(
            'id',
            '!=',
            $class->id
        )
        ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' =>
                    'This class already exists.'
            ], 422);
        }

        $class->update([
            'grade' =>
                trim(
                    $validated['grade']
                ),

            'section' =>
                trim(
                    $validated['section']
                ),
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Class updated successfully.',
            'data' => $class
        ]);
    }


    /**
     * Delete a class.
     */
    public function destroy(
        Request $request,
        $id
    ) {
        $faculty = $request->user();

        $class = SchoolClass::where(
            'id',
            $id
        )
        ->where(
            'faculty_id',
            $faculty->id
        )
        ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Class not found.'
            ], 404);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Class deleted successfully.'
        ]);
    }


    /**
     * Add one student to a class.
     */
    public function addStudent(
        Request $request,
        $id
    ) {
        $faculty = $request->user();

        $class = SchoolClass::where(
            'id',
            $id
        )
        ->where(
            'faculty_id',
            $faculty->id
        )
        ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Class not found.'
            ], 404);
        }

        $validated = $request->validate([
            'student_name' => [
                'required',
                'string',
                'max:150'
            ],
        ]);

        $studentName =
            trim(
                $validated['student_name']
            );

        $existingStudent =
            ClassStudent::where(
                'class_id',
                $class->id
            )
            ->where(
                'student_name',
                $studentName
            )
            ->first();

        if ($existingStudent) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student already exists in this class.'
            ], 422);
        }

        $student =
            ClassStudent::create([
                'class_id' =>
                    $class->id,

                'student_name' =>
                    $studentName
            ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Student added successfully.',
            'data' => $student
        ], 201);
    }


    /**
     * Update student name.
     */
    public function updateStudent(
        Request $request,
        $classId,
        $studentId
    ) {
        $faculty = $request->user();

        $class = SchoolClass::where(
            'id',
            $classId
        )
        ->where(
            'faculty_id',
            $faculty->id
        )
        ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Class not found.'
            ], 404);
        }

        $student =
            ClassStudent::where(
                'id',
                $studentId
            )
            ->where(
                'class_id',
                $class->id
            )
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student not found.'
            ], 404);
        }

        $validated = $request->validate([
            'student_name' => [
                'required',
                'string',
                'max:150'
            ],
        ]);

        $studentName =
            trim(
                $validated['student_name']
            );

        $duplicate =
            ClassStudent::where(
                'class_id',
                $class->id
            )
            ->where(
                'student_name',
                $studentName
            )
            ->where(
                'id',
                '!=',
                $student->id
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student already exists in this class.'
            ], 422);
        }

        $student->update([
            'student_name' =>
                $studentName
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Student updated successfully.',
            'data' => $student
        ]);
    }


    /**
     * Remove student from class.
     */
    public function removeStudent(
        Request $request,
        $classId,
        $studentId
    ) {
        $faculty = $request->user();

        $class = SchoolClass::where(
            'id',
            $classId
        )
        ->where(
            'faculty_id',
            $faculty->id
        )
        ->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Class not found.'
            ], 404);
        }

        $student =
            ClassStudent::where(
                'id',
                $studentId
            )
            ->where(
                'class_id',
                $class->id
            )
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student not found.'
            ], 404);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Student removed successfully.'
        ]);
    }
}
