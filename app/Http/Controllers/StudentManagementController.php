<?php

namespace App\Http\Controllers;

use App\Models\ClassStudent;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Strand;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentManagementController extends Controller
{
    public function index()
    {
        $students = User::where('role', 'student')
            ->with([
                'strand:id,name',
                'section:id,grade,strand_id,section',
            ])
            ->orderBy('name')
            ->get([
                'id',
                'lrn',
                'name',
                'email',
                'sex',
                'role',
                'status',
                'created_at',
                'updated_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lrn' => [
                'required',
                'string',
                'max:20',
                'unique:users,lrn',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'sex' => [
                'required',
                Rule::in([
                    'Male',
                    'Female',
                ]),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
        ]);

        $student = User::create([
            'lrn' => trim($validated['lrn']),
            'name' => trim($validated['name']),
            'sex' => $validated['sex'],
            'email' => strtolower(
                trim($validated['email'])
            ),
            'role' => 'student',
            'status' => 'active',
            'password' => Hash::make(
                Str::random(64)
            ),
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Student account created successfully.',
            'data' => $student,
        ], 201);
    }

    public function show($id)
    {
        $student = User::where(
            'role',
            'student'
        )
            ->with([
                'strand:id,name',
                'section:id,grade,strand_id,section',
            ])
            ->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student account not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $student,
        ]);
    }

    public function update(
        Request $request,
        $id
    ) {
        $student = User::where(
            'role',
            'student'
        )->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Student account not found.',
            ], 404);
        }

        $validated = $request->validate([
            'lrn' => [
                'required',
                'string',
                'max:20',
                Rule::unique(
                    'users',
                    'lrn'
                )->ignore($student->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'sex' => [
                'required',
                Rule::in([
                    'Male',
                    'Female',
                ]),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                )->ignore($student->id),
            ],
        ]);

        $student->update([
            'lrn' => trim($validated['lrn']),
            'name' => trim($validated['name']),
            'sex' => $validated['sex'],
            'email' => strtolower(
                trim($validated['email'])
            ),
        ]);

        $student->refresh();

        return response()->json([
            'success' => true,
            'message' =>
                'Student account updated successfully.',
            'data' => $student,
        ]);
    }

    public function bulkUpdateStatus(
        Request $request
    ) {
        $validated = $request->validate([
            'student_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'student_ids.*' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $students = User::where(
            'role',
            'student'
        )
            ->whereIn(
                'id',
                $validated['student_ids']
            )
            ->get();

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No student accounts were found.',
            ], 404);
        }

        User::where(
            'role',
            'student'
        )
            ->whereIn(
                'id',
                $students->pluck('id')
            )
            ->update([
                'status' =>
                    $validated['status'],
            ]);

        return response()->json([
            'success' => true,
            'message' =>
                $validated['status'] === 'active'
                    ? 'Selected students have been marked as active.'
                    : 'Selected students have been marked as inactive.',
            'updated' =>
                $students->count(),
        ]);
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file does not contain student data.',
                ], 422);
            }

            $headerRow = array_shift($rows);

            $headers = [];
            foreach ($headerRow as $column => $value) {
                $headers[$column] = strtolower(trim((string) $value));
            }

            $requiredHeaders = [
                'lrn',
                'name',
                'sex',
                'email',
                'strand',
                'grade',
                'section',
            ];

            $foundHeaders = array_values($headers);

            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $foundHeaders, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Missing required column: {$requiredHeader}.",
                    ], 422);
                }
            }

            $columnMap = [];

            foreach ($headers as $column => $header) {
                $columnMap[$header] = $column;
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $excelRowNumber = $index + 2;

                $lrn = trim((string) ($row[$columnMap['lrn']] ?? ''));
                $name = trim((string) ($row[$columnMap['name']] ?? ''));
                $sex = trim((string) ($row[$columnMap['sex']] ?? ''));
                $email = strtolower(trim((string) ($row[$columnMap['email']] ?? '')));
                $strandName = trim((string) ($row[$columnMap['strand']] ?? ''));
                $grade = trim((string) ($row[$columnMap['grade']] ?? ''));
                $sectionName = trim((string) ($row[$columnMap['section']] ?? ''));

                if (
                    $lrn === '' &&
                    $name === '' &&
                    $sex === '' &&
                    $email === '' &&
                    $strandName === '' &&
                    $grade === '' &&
                    $sectionName === ''
                ) {
                    continue;
                }

                if (
                    !$lrn ||
                    !$name ||
                    !$sex ||
                    !$email ||
                    !$strandName ||
                    !$grade ||
                    !$sectionName
                ) {
                    $skipped++;

                    $errors[] = [
                        'row' => $excelRowNumber,
                        'message' => 'One or more required fields are missing.',
                    ];

                    continue;
                }

                if (!preg_match('/^\d+$/', $lrn)) {
                    $skipped++;

                    $errors[] = [
                        'row' => $excelRowNumber,
                        'message' => 'LRN must contain numbers only.',
                    ];

                    continue;
                }

                $normalizedSex = ucfirst(strtolower($sex));

                if (!in_array($normalizedSex, ['Male', 'Female'], true)) {
                    $skipped++;

                    $errors[] = [
                        'row' => $excelRowNumber,
                        'message' => 'Sex must be Male or Female.',
                    ];

                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;

                    $errors[] = [
                        'row' => $excelRowNumber,
                        'message' => 'Invalid email address.',
                    ];

                    continue;
                }

                if (
                    User::where('lrn', $lrn)->exists() ||
                    User::where('email', $email)->exists()
                ) {
                    $skipped++;

                    $errors[] = [
                        'row' => $excelRowNumber,
                        'message' => 'Duplicate LRN or email.',
                    ];

                    continue;
                }

                $strand = Strand::whereRaw(
                    'LOWER(TRIM(name)) = ?',
                    [strtolower($strandName)]
                )->first();

                if (!$strand) {
                    $skipped++;

                    $errors[] = [
                        'row' => $excelRowNumber,
                        'message' => "Strand '{$strandName}' was not found.",
                    ];

                    continue;
                }

                $section = Section::where('strand_id', $strand->id)
                    ->whereRaw(
                        'LOWER(TRIM(grade)) = ?',
                        [strtolower($grade)]
                    )
                    ->whereRaw(
                        'LOWER(TRIM(section)) = ?',
                        [strtolower($sectionName)]
                    )
                    ->first();

                if (!$section) {
                    $skipped++;

                    $errors[] = [
                        'row' => $excelRowNumber,
                        'message' =>
                            "Section '{$grade} - {$sectionName}' was not found under {$strand->name}.",
                    ];

                    continue;
                }

                $student = User::create([
                    'lrn' => $lrn,
                    'name' => $name,
                    'sex' => $normalizedSex,
                    'email' => $email,
                    'strand_id' => $strand->id,
                    'section_id' => $section->id,
                    'role' => 'student',
                    'status' => 'active',
                    'password' => Hash::make(Str::random(64)),
                ]);

                $student->refresh();

                $this->enrollStudentToCurrentClasses($student);

                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$imported} student(s) imported successfully.",
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to import the Excel file.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function destroy($id)
    {
        $student = User::where(
            'role',
            'student'
        )->find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student account not found.',
            ], 404);
        }

        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student account deleted successfully.',
        ]);
    }
};
