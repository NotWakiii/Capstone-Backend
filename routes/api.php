<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentExamController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ResultController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

});
Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('exams', ExamController::class);

});
Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('exams', ExamController::class);

    // QUESTION CRUD
    Route::get('/questions/{exam_id}', [QuestionController::class, 'index']);
    Route::post('/questions', [QuestionController::class, 'store']);
    Route::get('/question/{id}', [QuestionController::class, 'show']);
    Route::put('/question/{id}', [QuestionController::class, 'update']);
    Route::delete('/question/{id}', [QuestionController::class, 'destroy']);

});
Route::middleware('auth:sanctum')->group(function () {

    // STUDENT EXAM MODULE
    Route::post('/join-exam', [StudentExamController::class, 'joinExam']);

    Route::get('/exam-questions/{exam_id}',
        [StudentExamController::class, 'getExamQuestions']);

    Route::post('/save-answer',
        [StudentExamController::class, 'saveAnswer']);

    Route::post('/submit-exam/{session_id}',
        [StudentExamController::class, 'submitExam']);

    Route::get('/results', [ResultController::class, 'index']);
    Route::get('/results/{session_id}', [ResultController::class, 'show']);
    Route::get('/my-results', [ResultController::class, 'myResults']);
    Route::get('/result-summary', [ResultController::class, 'summary']);

});
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/monitor-log',
        [MonitoringController::class, 'logActivity']);

    Route::get('/monitor-log/{session_id}',
        [MonitoringController::class, 'getLogs']);

});
