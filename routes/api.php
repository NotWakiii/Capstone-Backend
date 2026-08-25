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

Route::post(
    '/join-exam',
    [StudentExamController::class, 'joinExam']
);
Route::post(
    '/exam-students',
    [
        StudentExamController::class,
        'examStudents'
    ]
);

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

});

//ADMIN ROUTES
use App\Http\Controllers\AdminFacultyController;
use App\Http\Controllers\AdminExamController;
use App\Http\Controllers\AdminResultController;

Route::middleware([
    'auth:sanctum',
    'admin'
])->prefix('admin')->group(function () {

    Route::get(
        '/dashboard',
        [AdminController::class, 'dashboard']
    );

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
    Route::patch(
        '/faculty/{id}/status',
        [AdminFacultyController::class, 'updateStatus']
    );

});
//class management routes
use App\Http\Controllers\ClassManagementController;
Route::middleware('auth:sanctum')
    ->prefix('faculty')
    ->group(function () {

        Route::get(
            '/classes',
            [ClassManagementController::class, 'index']
        );

        Route::post(
            '/classes',
            [ClassManagementController::class, 'store']
        );

        Route::put(
            '/classes/{id}',
            [ClassManagementController::class, 'update']
        );

        Route::delete(
            '/classes/{id}',
            [ClassManagementController::class, 'destroy']
        );


        // STUDENTS

        Route::post(
            '/classes/{id}/students',
            [ClassManagementController::class, 'addStudent']
        );

        Route::put(
            '/classes/{classId}/students/{studentId}',
            [ClassManagementController::class, 'updateStudent']
        );

        Route::delete(
            '/classes/{classId}/students/{studentId}',
            [ClassManagementController::class, 'removeStudent']
        );

    });
