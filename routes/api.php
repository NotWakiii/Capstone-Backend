<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ResultController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();

        
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('exams', ExamController::class);

    Route::post('/exams/{id}/publish', [ExamController::class, 'publish']);

    Route::get('/questions/{exam_id}', [QuestionController::class, 'index']);
    Route::post('/questions', [QuestionController::class, 'store']);
    Route::get('/question/{id}', [QuestionController::class, 'show']);
    Route::put('/question/{id}', [QuestionController::class, 'update']);
    Route::delete('/question/{id}', [QuestionController::class, 'destroy']);

    Route::get('/exams/{id}/lobby', [StudentExamController::class, 'lobbyStudents']);
    Route::post('/exams/{id}/end', [ExamController::class, 'endExam']);
    Route::get('/exams/{id}/results', [ResultController::class, 'examResults']);
    Route::get('/exams/{id}/item-analysis', [ResultController::class, 'itemAnalysis']);

    Route::post('/exams/{id}/restart', [ExamController::class, 'restartExam']);
    

    Route::post('/join-exam', [StudentExamController::class, 'joinExam']);
    Route::get('/exam-questions/{exam_id}', [StudentExamController::class, 'getExamQuestions']);
    Route::post('/save-answer', [StudentExamController::class, 'saveAnswer']);
    Route::post('/submit-exam/{session_id}', [StudentExamController::class, 'submitExam']);
 

    Route::post('/exams/{id}/start', [ExamController::class, 'startExam']);

    Route::get('/results', [ResultController::class, 'index']);
    Route::get('/results/{session_id}', [ResultController::class, 'show']);
   
    Route::get('/result-summary', [ResultController::class, 'summary']);

    Route::post('/monitor-log', [MonitoringController::class, 'logActivity']);
    Route::get('/monitor-log/{session_id}', [MonitoringController::class, 'getLogs']);

});