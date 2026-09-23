<?php

namespace App\Http\Controllers;

use App\Models\ClassStudent;
use App\Models\Curriculum;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Strand;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Exam;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class FacultyClassController extends Controller
{
    public function index(Request $request)
    {
        $faculty = $request->user();

        $classes = SchoolClass::where('faculty_id', $faculty->id)
            ->with([
                'schoolYear:id,year,status',
                'strand:id,name',
                'sectionData:id,grade,strand_id,section',
                'subject:id,name',
            ])
            ->withCount('students')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $classes,
        ]);
    }

    public function options()
    {
        $schoolYears = SchoolYear::orderByDesc('year')
            ->get([
                'id',
                'year',
                'status',
            ]);

        $strands = Strand::orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $sections = Section::with('strand:id,name')
            ->orderBy('grade')
            ->orderBy('section')
            ->get([
                'id',
                'grade',
                'strand_id',
                'section',
            ]);

        $curricula = Curriculum::with([
            'strand:id,name',
            'subjects:id,name',
        ])
            ->orderBy('grade')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'school_years' => $schoolYears,
                'strands' => $strands,
                'sections' => $sections,
                'curricula' => $curricula,
                'semesters' => [
                    '1st Semester',
                    '2nd Semester',
                ],
                'grades' => [
                    'Grade 11',
                    'Grade 12',
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $faculty = $request->user();

        $validated = $request->validate([
            'school_year_id' => [
                'required',
                'integer',
                'exists:school_years,id',
            ],
            'semester' => [
                'required',
                Rule::in([
                    '1st Semester',
                    '2nd Semester',
                ]),
            ],
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
            'section_id' => [
                'required',
                'integer',
                'exists:sections,id',
            ],
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],
        ]);

        $section = Section::findOrFail(
            $validated['section_id']
        );

        if (
            $section->grade !== $validated['grade'] ||
            (int) $section->strand_id !== (int) $validated['strand_id']
        ) {
            return response()->json([
                'success' => false,
                'message' => 'The selected section does not match the selected grade and strand.',
            ], 422);
        }

        $curriculum = Curriculum::where(
            'grade',
            $validated['grade']
        )
            ->where(
                'strand_id',
                $validated['strand_id']
            )
            ->first();

        if (!$curriculum) {
            return response()->json([
                'success' => false,
                'message' => 'No curriculum exists for the selected grade and strand.',
            ], 422);
        }

        $subjectAllowed = $curriculum
            ->subjects()
            ->where(
                'subjects.id',
                $validated['subject_id']
            )
            ->exists();

        if (!$subjectAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'The selected subject is not part of the curriculum for this grade and strand.',
            ], 422);
        }

        $duplicate = SchoolClass::where(
            'faculty_id',
            $faculty->id
        )
            ->where(
                'school_year_id',
                $validated['school_year_id']
            )
            ->where(
                'semester',
                $validated['semester']
            )
            ->where(
                'grade',
                $validated['grade']
            )
            ->where(
                'strand_id',
                $validated['strand_id']
            )
            ->where(
                'section_id',
                $validated['section_id']
            )
            ->where(
                'subject_id',
                $validated['subject_id']
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'This class already exists for the selected school year and semester.',
            ], 422);
        }

        $class = SchoolClass::create([
            'faculty_id' => $faculty->id,
            'class_code' => $this->generateUniqueClassCode(),
            'school_year_id' => $validated['school_year_id'],
            'semester' => $validated['semester'],
            'grade' => $validated['grade'],
            'strand_id' => $validated['strand_id'],
            'section_id' => $validated['section_id'],
            'subject_id' => $validated['subject_id'],
            'section' => $section->section,
        ]);

        $class->load([
            'schoolYear:id,year,status',
            'strand:id,name',
            'sectionData:id,grade,strand_id,section',
            'subject:id,name',
        ]);

        $class->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully.',
            'data' => $class,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $class = SchoolClass::where(
            'faculty_id',
            $request->user()->id
        )
            ->with([
                'schoolYear:id,year,status',
                'strand:id,name',
                'sectionData:id,grade,strand_id,section',
                'subject:id,name',
            ])
            ->withCount('students')
            ->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $class,
        ]);
    }

    public function update(Request $request, $id)
    {
        $faculty = $request->user();

        $class = SchoolClass::where(
            'faculty_id',
            $faculty->id
        )->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $validated = $request->validate([
            'school_year_id' => [
                'required',
                'integer',
                'exists:school_years,id',
            ],
            'semester' => [
                'required',
                Rule::in([
                    '1st Semester',
                    '2nd Semester',
                ]),
            ],
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
            'section_id' => [
                'required',
                'integer',
                'exists:sections,id',
            ],
            'subject_id' => [
                'required',
                'integer',
                'exists:subjects,id',
            ],
        ]);

        $section = Section::findOrFail(
            $validated['section_id']
        );

        if (
            $section->grade !== $validated['grade'] ||
            (int) $section->strand_id !== (int) $validated['strand_id']
        ) {
            return response()->json([
                'success' => false,
                'message' => 'The selected section does not match the selected grade and strand.',
            ], 422);
        }

        $curriculum = Curriculum::where(
            'grade',
            $validated['grade']
        )
            ->where(
                'strand_id',
                $validated['strand_id']
            )
            ->first();

        if (!$curriculum) {
            return response()->json([
                'success' => false,
                'message' => 'No curriculum exists for the selected grade and strand.',
            ], 422);
        }

        $subjectAllowed = $curriculum
            ->subjects()
            ->where(
                'subjects.id',
                $validated['subject_id']
            )
            ->exists();

        if (!$subjectAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'The selected subject is not part of the curriculum for this grade and strand.',
            ], 422);
        }

        $duplicate = SchoolClass::where(
            'faculty_id',
            $faculty->id
        )
            ->where(
                'school_year_id',
                $validated['school_year_id']
            )
            ->where(
                'semester',
                $validated['semester']
            )
            ->where(
                'grade',
                $validated['grade']
            )
            ->where(
                'strand_id',
                $validated['strand_id']
            )
            ->where(
                'section_id',
                $validated['section_id']
            )
            ->where(
                'subject_id',
                $validated['subject_id']
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
                'message' => 'This class already exists for the selected school year and semester.',
            ], 422);
        }

        $class->update([
            'school_year_id' => $validated['school_year_id'],
            'semester' => $validated['semester'],
            'grade' => $validated['grade'],
            'strand_id' => $validated['strand_id'],
            'section_id' => $validated['section_id'],
            'subject_id' => $validated['subject_id'],
            'section' => $section->section,
        ]);

        $class->load([
            'schoolYear:id,year,status',
            'strand:id,name',
            'sectionData:id,grade,strand_id,section',
            'subject:id,name',
        ]);

        $class->loadCount('students');

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully.',
            'data' => $class,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $class = SchoolClass::where(
            'faculty_id',
            $request->user()->id
        )->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully.',
        ]);
    }

    public function students(Request $request, $id)
    {
        $class = SchoolClass::where(
            'faculty_id',
            $request->user()->id
        )->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $students = ClassStudent::where(
            'class_id',
            $class->id
        )
            ->with([
                'student' => function ($query) {
                    $query->select([
                        'id',
                        'lrn',
                        'name',
                        'email',
                        'sex',
                        'status',
                    ]);
                }
            ])
            ->whereHas('student')
            ->get()
            ->sortBy(function ($enrollment) {
                return strtolower(
                    $enrollment->student->name ?? ''
                );
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }
    public function availableStudents(Request $request, $id)
    {
        $class = SchoolClass::where('faculty_id', $request->user()->id)->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $enrolledStudentIds = ClassStudent::where('class_id', $class->id)->pluck('student_id');

        $students = User::where('role', 'student')
            ->where('status', 'active')
            ->whereNotIn('id', $enrolledStudentIds)
            ->orderBy('name')
            ->get([
                'id',
                'lrn',
                'name',
                'email',
                'sex',
                'status',
            ]);

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }
    public function storeStudent(Request $request, $id)
    {
        $class = SchoolClass::where(
            'faculty_id',
            $request->user()->id
        )->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        $student = User::where(
            'id',
            $validated['student_id']
        )
            ->where(
                'role',
                'student'
            )
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student account not found.',
            ], 404);
        }
        if ($student->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Inactive students cannot be enrolled in a class.',
            ], 422);
        }

        $activeSchoolYear = SchoolYear::where(
            'status',
            'active'
        )->first();

        if (
            !$activeSchoolYear ||
            (int) $class->school_year_id !==
            (int) $activeSchoolYear->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Students can only be enrolled in classes from the active school year.',
            ], 422);
        }

        $duplicate = ClassStudent::where(
            'class_id',
            $class->id
        )
            ->where(
                'student_id',
                $student->id
            )
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'This student is already enrolled in this class.',
            ], 422);
        }

        $enrollment = ClassStudent::create([
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);

        $enrollment->load([
            'student:id,lrn,name,email,sex,status'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student enrolled successfully.',
            'data' => $enrollment,
        ], 201);
    }
    public function importStudents(Request $request, $id)
    {
        $class = SchoolClass::where('faculty_id', $request->user()->id)->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $activeSchoolYear = SchoolYear::where('status', 'active')->first();

        if (!$activeSchoolYear || (int) $class->school_year_id !== (int) $activeSchoolYear->id) {
            return response()->json([
                'success' => false,
                'message' => 'Students can only be enrolled in classes from the active school year.',
            ], 422);
        }

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if (!$handle) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to read the uploaded file.',
            ], 422);
        }

        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'The CSV file is empty.',
            ], 422);
        }

        $header = array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $header);

        $lrnIndex = array_search('lrn', $header, true);

        if ($lrnIndex === false) {
            fclose($handle);

            return response()->json([
                'success' => false,
                'message' => 'The CSV file must contain an LRN column.',
            ], 422);
        }

        $added = 0;
        $alreadyEnrolled = 0;
        $notFound = 0;
        $inactive = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (!isset($row[$lrnIndex])) continue;

            $lrn = trim($row[$lrnIndex]);

            if ($lrn === '') continue;

            $student = User::where('role', 'student')
                ->where('lrn', $lrn)
                ->first();

            if (!$student) {
                $notFound++;
                continue;
            }

            if ($student->status !== 'active') {
                $inactive++;
                continue;
            }

            $exists = ClassStudent::where('class_id', $class->id)
                ->where('student_id', $student->id)
                ->exists();

            if ($exists) {
                $alreadyEnrolled++;
                continue;
            }

            ClassStudent::create([
                'class_id' => $class->id,
                'student_id' => $student->id,
            ]);

            $added++;
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => "{$added} student(s) enrolled successfully.",
            'data' => [
                'added' => $added,
                'already_enrolled' => $alreadyEnrolled,
                'not_found' => $notFound,
                'inactive' => $inactive,
            ],
        ]);
    }

    public function destroyStudent(
        Request $request,
        $id,
        $studentId
    ) {
        $class = SchoolClass::where(
            'faculty_id',
            $request->user()->id
        )->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $enrollment = ClassStudent::where(
            'class_id',
            $class->id
        )->find($studentId);

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Student enrollment not found.',
            ], 404);
        }

        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student removed from class successfully.',
        ]);
    }

    public function assessments($id)
    {
        $class = SchoolClass::where(
            'faculty_id',
            auth()->id()
        )->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found.',
            ], 404);
        }

        $assessments = Exam::where(
            'class_id',
            $class->id
        )
            ->where(
                'created_by',
                auth()->id()
            )
            ->withCount('questions')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assessments,
        ]);
    }
    private function generateUniqueClassCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (
            SchoolClass::where('class_code', $code)->exists()
        );

        return $code;
    }
};

