<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CONTROLLERS
|--------------------------------------------------------------------------
*/

// Authentication
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentAuthController;

// Exams
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\TestBankController;

// Admin
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminFacultyController;
use App\Http\Controllers\AdminExamController;
use App\Http\Controllers\AdminResultController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuditLogController;

// Academic Master Data
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\StrandController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\CurriculumController;

// Student Account Management
use App\Http\Controllers\StudentManagementController;

// Faculty Classes
use App\Http\Controllers\FacultyClassController;

// Student Classes
use App\Http\Controllers\StudentClassController;

// Feedback
use App\Http\Controllers\AssessmentFeedbackController;

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| PUBLIC STUDENT EXAM ROUTES
|--------------------------------------------------------------------------
|
| These routes remain public because the existing examination flow may
| access them without a faculty authentication token.
|
*/

Route::get('/student/exams/{id}/status', [StudentExamController::class, 'examStatus']);
Route::get('/student/exams/{id}/lobby', [StudentExamController::class, 'studentLobby']);
Route::get('/exam-questions/{exam_id}', [StudentExamController::class, 'getExamQuestions']);
Route::post('/save-answer', [StudentExamController::class, 'saveAnswer']);
Route::post('/submit-exam/{session_id}', [StudentExamController::class, 'submitExam']);

/*
|--------------------------------------------------------------------------
| PUBLIC STUDENT MONITORING ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/monitor-log', [MonitoringController::class, 'logActivity']);
Route::post('/student-session-status', [MonitoringController::class, 'updateSessionStatus']);

/*
|--------------------------------------------------------------------------
| PUBLIC STUDENT RESULT ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/student-results/{session_id}', [ResultController::class, 'studentResult']);
Route::get('/student-leaderboard/{exam_id}', [ResultController::class, 'studentLeaderboard']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED GENERAL ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/user',
        function (Request $request) {
            return $request->user();
        }
    );

    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | EXAM MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::apiResource('exams', ExamController::class);
    Route::post('/exams/{id}/publish', [ExamController::class, 'publish']);
    Route::post('/exams/{id}/start', [ExamController::class, 'startExam']);
    Route::post('/exams/{id}/cancel-lobby', [ExamController::class, 'cancelLobby']);
    Route::post('/exams/{id}/end', [ExamController::class, 'endExam']);
    Route::post('/exams/{id}/restart', [ExamController::class, 'restartExam']);

    /*
    |--------------------------------------------------------------------------
    | FACULTY EXAM LOBBY / MONITORING
    |--------------------------------------------------------------------------
    */

    Route::get('/exams/{id}/lobby', [StudentExamController::class, 'lobbyStudents']);
    Route::get('/monitor-log/{session_id}', [MonitoringController::class, 'getLogs']);

    /*
    |--------------------------------------------------------------------------
    | QUESTION MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::get('/questions/{exam_id}', [QuestionController::class, 'index']);
    Route::post('/questions', [QuestionController::class, 'store']);
    Route::get('/question/{id}', [QuestionController::class, 'show']);
    Route::put('/question/{id}', [QuestionController::class, 'update']);
    Route::delete('/question/{id}', [QuestionController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | RESULTS
    |--------------------------------------------------------------------------
    */

    Route::get('/exams/{id}/results', [ResultController::class, 'examResults']);
    Route::get('/results', [ResultController::class, 'index']);
    Route::get('/results/{session_id}', [ResultController::class, 'show']);
    Route::get('/result-summary', [ResultController::class, 'summary']);

    /*
    |--------------------------------------------------------------------------
    | ITEM ANALYSIS
    |--------------------------------------------------------------------------
    */

    Route::get('/exams/{id}/item-analysis', [ResultController::class, 'itemAnalysis']);
    Route::get('/exams/{id}/item-analysis/pdf', [ResultController::class, 'exportItemAnalysisPdf']);
});

/*
|--------------------------------------------------------------------------
| FACULTY EXAM RESULTS + TEST BANK
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->prefix('faculty')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | EXAM RESULTS
        |--------------------------------------------------------------------------
        */

        Route::get('/exam-results', [ExamResultController::class, 'index']);
        Route::get('/exams/{examId}/results', [ExamResultController::class, 'show']);
        Route::get('/exam-sessions/{sessionId}/result', [ExamResultController::class, 'studentResult']);
        Route::get('/exam-results/{examId}', [ExamResultController::class, 'show']);

        /*
        |--------------------------------------------------------------------------
        | TEST BANK
        |--------------------------------------------------------------------------
        */

        Route::get('/test-bank', [TestBankController::class, 'index']);
        Route::post('/test-bank', [TestBankController::class, 'store']);
        Route::post('/test-bank/bulk', [TestBankController::class, 'storeBulk']);
        Route::patch('/test-bank/{id}/status', [TestBankController::class, 'updateStatus']);
        Route::get('/test-bank/{id}', [TestBankController::class, 'show']);
        Route::put('/test-bank/{id}', [TestBankController::class, 'update']);
        Route::delete('/test-bank/{id}', [TestBankController::class, 'destroy']);
    });

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | ADMIN DASHBOARD
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | FACULTY ACCOUNT MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/faculty', [AdminFacultyController::class, 'index']);
        Route::post('/faculty', [AdminFacultyController::class, 'store']);
        Route::put('/faculty/{id}', [AdminFacultyController::class, 'update']);
        Route::delete('/faculty/{id}', [AdminFacultyController::class, 'destroy']);
        Route::patch('/faculty/{id}/status', [AdminFacultyController::class, 'updateStatus']);

        /*
        |--------------------------------------------------------------------------
        | STUDENT ACCOUNT MANAGEMENT
        |--------------------------------------------------------------------------
        |
        | Admin creates and manages student accounts.
        | Student accounts are NOT assigned to a strand, section,
        | subject, or class here.
        |
        */

        Route::get('/students', [StudentManagementController::class, 'index']);
        Route::post('/students', [StudentManagementController::class, 'store']);
        Route::post('/students/import', [StudentManagementController::class, 'import']);

        /*
         * IMPORTANT:
         * Put /bulk-status BEFORE /students/{id}
         * to keep the static route clearly separated
         * from the dynamic student ID route.
         */
        Route::put('/students/bulk-status', [StudentManagementController::class, 'bulkUpdateStatus']);
        Route::get('/students/{id}', [StudentManagementController::class, 'show']);
        Route::put('/students/{id}', [StudentManagementController::class, 'update']);
        Route::delete('/students/{id}', [StudentManagementController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN EXAM MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/exams', [AdminExamController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN RESULTS
        |--------------------------------------------------------------------------
        */

        Route::get('/results', [AdminResultController::class, 'index']);
        Route::get('/results/{examId}', [AdminResultController::class, 'show']);

        /*
        |--------------------------------------------------------------------------
        | AUDIT LOGS
        |--------------------------------------------------------------------------
        */

        Route::get('/audit-logs', [AuditLogController::class, 'index']);

        /*
        |--------------------------------------------------------------------------
        | SCHOOL YEAR MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/school-years', [SchoolYearController::class, 'index']);
        Route::post('/school-years', [SchoolYearController::class, 'store']);
        Route::get('/school-years/{id}', [SchoolYearController::class, 'show']);
        Route::put('/school-years/{id}', [SchoolYearController::class, 'update']);
        Route::delete('/school-years/{id}', [SchoolYearController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | STRAND MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/strands', [StrandController::class, 'index']);
        Route::post('/strands', [StrandController::class, 'store']);
        Route::get('/strands/{id}', [StrandController::class, 'show']);
        Route::put('/strands/{id}', [StrandController::class, 'update']);
        Route::delete('/strands/{id}', [StrandController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | SECTION MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/sections', [SectionController::class, 'index']);
        Route::post('/sections', [SectionController::class, 'store']);
        Route::get('/sections/{id}', [SectionController::class, 'show']);
        Route::put('/sections/{id}', [SectionController::class, 'update']);
        Route::delete('/sections/{id}', [SectionController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | SUBJECT MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/subjects', [SubjectController::class, 'index']);
        Route::post('/subjects', [SubjectController::class, 'store']);
        Route::get('/subjects/{id}', [SubjectController::class, 'show']);
        Route::put('/subjects/{id}', [SubjectController::class, 'update']);
        Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | CURRICULUM MANAGEMENT
        |--------------------------------------------------------------------------
        */

        Route::get('/curricula', [CurriculumController::class, 'index']);
        Route::post('/curricula', [CurriculumController::class, 'store']);
        Route::get('/curricula/{id}', [CurriculumController::class, 'show']);
        Route::put('/curricula/{id}', [CurriculumController::class, 'update']);
        Route::delete('/curricula/{id}', [CurriculumController::class, 'destroy']);
    });

/*
|--------------------------------------------------------------------------
| FACULTY CLASS MANAGEMENT
|--------------------------------------------------------------------------
|
| Faculty manages classes and class enrollment.
| Faculty does NOT create or delete student accounts here.
|
*/

Route::middleware('auth:sanctum')
    ->prefix('faculty')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | CLASSES
        |--------------------------------------------------------------------------
        */

        Route::get('/classes/options', [FacultyClassController::class, 'options']);
        Route::get('/classes', [FacultyClassController::class, 'index']);
        Route::post('/classes', [FacultyClassController::class, 'store']);
        Route::get('/classes/{id}', [FacultyClassController::class, 'show']);
        Route::put('/classes/{id}', [FacultyClassController::class, 'update']);
        Route::delete('/classes/{id}', [FacultyClassController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | CLASS STUDENTS
        |--------------------------------------------------------------------------
        |
        | These routes manage class enrollment only.
        | Removing a student from a class must NOT delete
        | the student's actual account.
        |
        */
        Route::get('/classes/{id}/students', [FacultyClassController::class, 'students']);
        Route::get('/classes/{id}/available-students', [FacultyClassController::class, 'availableStudents']);
        Route::post('/classes/{id}/students', [FacultyClassController::class, 'storeStudent']);
        Route::post('/classes/{id}/students/import', [FacultyClassController::class, 'importStudents']);
        Route::delete('/classes/{id}/students/{studentId}', [FacultyClassController::class, 'destroyStudent']);

        /*
        |--------------------------------------------------------------------------
        | CLASS ASSESSMENTS
        |--------------------------------------------------------------------------
        */

        Route::get('/classes/{id}/assessments', [FacultyClassController::class, 'assessments']);
    });

/*
|--------------------------------------------------------------------------
| STUDENT AUTHENTICATION + STUDENT DASHBOARD
|--------------------------------------------------------------------------
*/

Route::prefix('student')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | STUDENT LOGIN
        |--------------------------------------------------------------------------
        */

        Route::post('/login', [StudentAuthController::class, 'login']);

        /*
        |--------------------------------------------------------------------------
        | AUTHENTICATED STUDENT ROUTES
        |--------------------------------------------------------------------------
        */

        Route::middleware('auth:sanctum')
            ->group(function () {

                /*
                |--------------------------------------------------------------------------
                | ACCOUNT
                |--------------------------------------------------------------------------
                */

                Route::get('/me', [StudentAuthController::class, 'me']);
                Route::post('/logout', [StudentAuthController::class, 'logout']);

                /*
                |--------------------------------------------------------------------------
                | STUDENT CLASSES
                |--------------------------------------------------------------------------
                */

                Route::get('/classes', [StudentClassController::class, 'index']);
                Route::post('/classes/join', [StudentClassController::class, 'join']);
                Route::get('/classes/{id}', [StudentClassController::class, 'show']);
                /*
                |--------------------------------------------------------------------------
                | STUDENT EXAM
                |--------------------------------------------------------------------------
                */

                Route::post('/exam-students', [StudentExamController::class, 'examStudents']);
                Route::post('/join-exam', [StudentExamController::class, 'joinExam']);

                /*
                |--------------------------------------------------------------------------
                | STUDENT FEEDBACK
                |--------------------------------------------------------------------------
                */

                Route::post('/feedback', [AssessmentFeedbackController::class, 'studentStore']);

                /*
                |--------------------------------------------------------------------------
                | STUDENT RESULTS
                |--------------------------------------------------------------------------
                */

                Route::get('/results/{examId}', [ResultController::class, 'studentResultByExam']);
            });
    });

/*
|--------------------------------------------------------------------------
| FACULTY FEEDBACK
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->prefix('faculty')
    ->group(function () {

        Route::get('/feedback', [AssessmentFeedbackController::class, 'facultyIndex']);
        Route::put('/feedback/{id}/read', [AssessmentFeedbackController::class, 'markRead']);
    });
