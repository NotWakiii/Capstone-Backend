<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ExamResultController;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\TestBankController;

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::post(
    '/register',
    [AuthController::class, 'register']
);

Route::post(
    '/login',
    [AuthController::class, 'login']
);

/*
|--------------------------------------------------------------------------
| PUBLIC STUDENT ROUTES
|--------------------------------------------------------------------------
|
| These routes must stay outside auth:sanctum because students join using
| an examination code and do not have a faculty authentication token.
|
*/

Route::get(
    '/student/exams/{id}/status',
    [StudentExamController::class, 'examStatus']
);
Route::get(
    '/student/exams/{id}/lobby',
    [StudentExamController::class, 'studentLobby']
);

Route::get(
    '/exam-questions/{exam_id}',
    [StudentExamController::class, 'getExamQuestions']
);

Route::post(
    '/save-answer',
    [StudentExamController::class, 'saveAnswer']
);

Route::post(
    '/submit-exam/{session_id}',
    [StudentExamController::class, 'submitExam']
);

/*
|--------------------------------------------------------------------------
| PUBLIC STUDENT MONITORING ROUTES
|--------------------------------------------------------------------------
*/

Route::post(
    '/monitor-log',
    [MonitoringController::class, 'logActivity']
);

Route::post(
    '/student-session-status',
    [MonitoringController::class, 'updateSessionStatus']
);

/*
|--------------------------------------------------------------------------
| PUBLIC STUDENT RESULT ROUTES
|--------------------------------------------------------------------------
*/

Route::get(
    '/student-results/{session_id}',
    [ResultController::class, 'studentResult']
);

Route::get(
    '/student-leaderboard/{exam_id}',
    [ResultController::class, 'studentLeaderboard']
);

/*
|--------------------------------------------------------------------------
| PROTECTED FACULTY ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED USER
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/user',
        function (Request $request) {
            return $request->user();
        }
    );

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    );

    /*
    |--------------------------------------------------------------------------
    | EXAM MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'exams',
        ExamController::class
    );

    Route::post(
        '/exams/{id}/publish',
        [ExamController::class, 'publish']
    );

    Route::post(
        '/exams/{id}/start',
        [ExamController::class, 'startExam']
    );
    Route::post(
        '/exams/{id}/cancel-lobby',
        [ExamController::class, 'cancelLobby']
    );

    Route::post(
        '/exams/{id}/end',
        [ExamController::class, 'endExam']
    );

    Route::post(
        '/exams/{id}/restart',
        [ExamController::class, 'restartExam']
    );

    /*
    |--------------------------------------------------------------------------
    | FACULTY LOBBY AND MONITORING
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/exams/{id}/lobby',
        [StudentExamController::class, 'lobbyStudents']
    );

    Route::get(
        '/monitor-log/{session_id}',
        [MonitoringController::class, 'getLogs']
    );

    /*
    |--------------------------------------------------------------------------
    | QUESTION MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/questions/{exam_id}',
        [QuestionController::class, 'index']
    );

    Route::post(
        '/questions',
        [QuestionController::class, 'store']
    );

    Route::get(
        '/question/{id}',
        [QuestionController::class, 'show']
    );

    Route::put(
        '/question/{id}',
        [QuestionController::class, 'update']
    );

    Route::delete(
        '/question/{id}',
        [QuestionController::class, 'destroy']
    );

    /*
    |--------------------------------------------------------------------------
    | FACULTY RESULTS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/exams/{id}/results',
        [ResultController::class, 'examResults']
    );

    Route::get(
        '/results',
        [ResultController::class, 'index']
    );

    Route::get(
        '/results/{session_id}',
        [ResultController::class, 'show']
    );

    Route::get(
        '/result-summary',
        [ResultController::class, 'summary']
    );

    /*
    |--------------------------------------------------------------------------
    | ITEM ANALYSIS
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/exams/{id}/item-analysis',
        [ResultController::class, 'itemAnalysis']
    );
    Route::get(
    '/exams/{id}/item-analysis/pdf',
    [ResultController::class, 'exportItemAnalysisPdf']
);
});

//Exam Result Controller
Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/faculty/exam-results',
        [ExamResultController::class, 'index']
    );

    Route::get(
        '/faculty/exams/{examId}/results',
        [ExamResultController::class, 'show']
    );

    Route::get(
        '/faculty/exam-sessions/{sessionId}/result',
        [ExamResultController::class, 'studentResult']
    );

    Route::get(
        '/faculty/exam-results/{examId}',
        [ExamResultController::class, 'show']
    );
    Route::get('/faculty/test-bank', [TestBankController::class, 'index']);
    Route::post('/faculty/test-bank', [TestBankController::class, 'store']);
    Route::post('/faculty/test-bank/bulk', [TestBankController::class, 'storeBulk']);
    Route::get('/faculty/test-bank/{id}', [TestBankController::class, 'show']);
    Route::put('/faculty/test-bank/{id}', [TestBankController::class, 'update']);
    Route::delete('/faculty/test-bank/{id}', [TestBankController::class, 'destroy']);

});

//ADMIN ROUTES
use App\Http\Controllers\AdminFacultyController;
use App\Http\Controllers\AdminExamController;
use App\Http\Controllers\AdminResultController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\StrandController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\StudentManagementController;
use App\Http\Controllers\AdminDashboardController;


Route::middleware([
    'auth:sanctum',
    'admin'
])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    Route::get(
        '/faculty',
        [AdminFacultyController::class, 'index']
    );

    Route::post(
        '/faculty',
        [AdminFacultyController::class, 'store']
    );

    Route::put(
        '/faculty/{id}',
        [AdminFacultyController::class, 'update']
    );

    Route::delete(
        '/faculty/{id}',
        [AdminFacultyController::class, 'destroy']
    );

    Route::patch(
        '/faculty/{id}/status',
        [AdminFacultyController::class, 'updateStatus']
    );

    Route::get(
        '/exams',
        [AdminExamController::class, 'index']
    );

    Route::get(
        '/results',
        [AdminResultController::class, 'index']
    );

    Route::get(
        '/results/{examId}',
        [AdminResultController::class, 'show']
    );

    Route::get(
        '/audit-logs',
        [AuditLogController::class, 'index']
    );

    /*
    |--------------------------------------------------------------------------
    | SCHOOL YEAR MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/school-years',
        [SchoolYearController::class, 'index']
    );

    Route::post(
        '/school-years',
        [SchoolYearController::class, 'store']
    );

    Route::get(
        '/school-years/{id}',
        [SchoolYearController::class, 'show']
    );

    Route::put(
        '/school-years/{id}',
        [SchoolYearController::class, 'update']
    );

    Route::delete(
        '/school-years/{id}',
        [SchoolYearController::class, 'destroy']
    );
    /*
    |--------------------------------------------------------------------------
    | STRAND MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/strands',
        [StrandController::class, 'index']
    );

    Route::post(
        '/strands',
        [StrandController::class, 'store']
    );

    Route::get(
        '/strands/{id}',
        [StrandController::class, 'show']
    );

    Route::put(
        '/strands/{id}',
        [StrandController::class, 'update']
    );

    Route::delete(
        '/strands/{id}',
        [StrandController::class, 'destroy']
    );
    /*
    |--------------------------------------------------------------------------
    | SECTION MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/sections',
        [SectionController::class, 'index']
    );

    Route::post(
        '/sections',
        [SectionController::class, 'store']
    );

    Route::get(
        '/sections/{id}',
        [SectionController::class, 'show']
    );

    Route::put(
        '/sections/{id}',
        [SectionController::class, 'update']
    );

    Route::delete(
        '/sections/{id}',
        [SectionController::class, 'destroy']
    );
    /*
    |--------------------------------------------------------------------------
    | SUBJECT MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/subjects',
        [SubjectController::class, 'index']
    );

    Route::post(
        '/subjects',
        [SubjectController::class, 'store']
    );

    Route::get(
        '/subjects/{id}',
        [SubjectController::class, 'show']
    );

    Route::put(
        '/subjects/{id}',
        [SubjectController::class, 'update']
    );

    Route::delete(
        '/subjects/{id}',
        [SubjectController::class, 'destroy']
    );
    Route::get('/curricula', [
        CurriculumController::class,
        'index'
    ]);

    Route::post('/curricula', [
        CurriculumController::class,
        'store'
    ]);

    Route::get('/curricula/{id}', [
        CurriculumController::class,
        'show'
    ]);

    Route::put('/curricula/{id}', [
        CurriculumController::class,
        'update'
    ]);

    Route::delete('/curricula/{id}', [
        CurriculumController::class,
        'destroy'
    ]);
    //STUDENT MANAGEMENT
    Route::get('/students', [StudentManagementController::class, 'index']);
    Route::post('/students', [StudentManagementController::class, 'store']);
    Route::post('/students/import', [StudentManagementController::class, 'import']);
    Route::get('/students/{id}', [StudentManagementController::class, 'show']);
    Route::put('/students/{id}', [StudentManagementController::class, 'update']);
    Route::delete('/students/{id}', [StudentManagementController::class, 'destroy']);

});
use App\Http\Controllers\FacultyClassController;
Route::middleware('auth:sanctum')
    ->prefix('faculty')
    ->group(function () {

        Route::get('/classes/options', [
            FacultyClassController::class,
            'options'
        ]);

        Route::get('/classes', [
            FacultyClassController::class,
            'index'
        ]);

        Route::post('/classes', [
            FacultyClassController::class,
            'store'
        ]);

        Route::get('/classes/{id}', [
            FacultyClassController::class,
            'show'
        ]);

        Route::put('/classes/{id}', [
            FacultyClassController::class,
            'update'
        ]);

        Route::delete('/classes/{id}', [
            FacultyClassController::class,
            'destroy'
        ]);
        Route::get('/classes/{id}/students', [FacultyClassController::class, 'students']);
        Route::post('/classes/{id}/students', [FacultyClassController::class, 'storeStudent']);
        Route::delete('/classes/{id}/students/{studentId}', [FacultyClassController::class, 'destroyStudent']);
        Route::put('/students/bulk-status', [StudentManagementController::class, 'bulkUpdateStatus']);
        Route::get(
            '/classes/{id}/assessments',
            [FacultyClassController::class, 'assessments']
        );
    });
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\AssessmentFeedbackController;

Route::prefix('student')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [StudentAuthController::class, 'me']);
        Route::post('/logout', [StudentAuthController::class, 'logout']);

        Route::get('/classes', [StudentClassController::class, 'index']);
        Route::get('/classes/{id}', [StudentClassController::class, 'show']);

        Route::post('/exam-students', [
            StudentExamController::class,
            'examStudents'
        ]);

        Route::post('/join-exam', [
            StudentExamController::class,
            'joinExam'
        ]);

        Route::post('/feedback', [
            AssessmentFeedbackController::class,
            'studentStore'
        ]);
        Route::get('/results/{examId}', [ResultController::class, 'studentResultByExam']);
    });
});

Route::middleware('auth:sanctum')
    ->prefix('faculty')
    ->group(function () {
        Route::get('/feedback', [AssessmentFeedbackController::class, 'facultyIndex']);
        Route::put('/feedback/{id}/read', [AssessmentFeedbackController::class, 'markRead']);
    });
