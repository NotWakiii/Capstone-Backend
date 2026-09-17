<?php

namespace App\Http\Controllers;

use App\Models\AssessmentFeedback;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentFeedbackController extends Controller
{
    public function studentStore(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        if (!$exam->class_id) {
            return response()->json([
                'success' => false,
                'message' => 'This assessment is not connected to a class.',
            ], 422);
        }

        $enrolled = $user->role === 'student'
            &&  DB::table('class_students')
                ->where('class_id', $exam->class_id)
                ->where('student_id', $user->id)
                ->exists();

        if (!$enrolled) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this class.',
            ], 403);
        }

        $session = ExamSession::where('id', $validated['exam_session_id'])
            ->where('exam_id', $exam->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid examination session.',
            ], 422);
        }

        /*
         * Current I-SPAS ExamSession is still using student_name.
         * This prevents another logged-in student from submitting
         * feedback using someone else's session.
         */
        if (
            isset($session->student_name) &&
            mb_strtolower(trim($session->student_name)) !==
            mb_strtolower(trim($user->name))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This examination session does not belong to you.',
            ], 403);
        }

        $feedback = AssessmentFeedback::create([
            'student_id' => $user->id,
            'class_id' => $exam->class_id,
            'exam_id' => $exam->id,
            'exam_session_id' => $session->id,
            'message' => trim($validated['message']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully.',
            'data' => $feedback,
        ], 201);
    }

    public function facultyIndex(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'faculty') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $classIds = SchoolClass::where('faculty_id', $user->id)
            ->pluck('id');

        $feedback = AssessmentFeedback::whereIn('class_id', $classIds)
            ->with([
                'student:id,name,lrn,email',
                'exam:id,title,assessment_type,class_id',
                'schoolClass:id,grade,section,strand_id,section_id,subject_id,school_year_id,semester',
                'schoolClass.strand:id,name',
                'schoolClass.sectionData:id,section,grade,strand_id',
                'schoolClass.subject:id,name',
                'schoolClass.schoolYear:id,year',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();

        $classIds = SchoolClass::where('faculty_id', $user->id)
            ->pluck('id');

        $feedback = AssessmentFeedback::whereIn('class_id', $classIds)
            ->findOrFail($id);

        $feedback->update([
            'is_read' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback marked as read.',
            'data' => $feedback,
        ]);
    }
}
