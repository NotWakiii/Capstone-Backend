<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ClassStudent;
use App\Models\StudentAnswer;
use Illuminate\Support\Facades\DB;

class StemEngineeringResultsSeeder extends Seeder
{
    public function run(): void
    {
        $exam = Exam::with([
            'questions.options'
        ])->findOrFail(25);

        $students = ClassStudent::where(
            'class_id',
            $exam->class_id
        )->get();

        $questions = $exam->questions;

        if ($students->isEmpty()) {
            throw new \Exception(
                'No students found in this class.'
            );
        }

        if ($questions->count() !== 20) {
            throw new \Exception(
                'Exam 25 must contain exactly 20 questions.'
            );
        }

        /*
         * Target scores for 40 students.
         *
         * This gives realistic variation instead
         * of completely random results.
         */
        $targetScores = [
            19, 18, 18, 17, 17,
            17, 16, 16, 16, 15,
            15, 15, 15, 14, 14,
            14, 14, 13, 13, 13,
            13, 12, 12, 12, 12,
            11, 11, 11, 10, 10,
            10, 9, 9, 9, 8,
            8, 8, 7, 7, 6
        ];

        DB::transaction(function () use (
            $exam,
            $students,
            $questions,
            $targetScores
        ) {

            /*
             * Remove existing demo attempts for
             * students belonging to this class.
             */
            foreach ($students as $student) {

                $oldSessions = ExamSession::where(
                    'exam_id',
                    $exam->id
                )
                ->where(
                    'student_name',
                    $student->student_name
                )
                ->get();

                foreach ($oldSessions as $oldSession) {

                    StudentAnswer::where(
                        'exam_session_id',
                        $oldSession->id
                    )->delete();

                    $oldSession->delete();
                }
            }

            /*
             * Create one completed attempt
             * for every student.
             */
            foreach ($students as $index => $student) {

                $targetScore =
                    $targetScores[
                        $index % count($targetScores)
                    ];

                /*
                 * Randomize which questions the
                 * student answers correctly.
                 */
                $correctQuestionIds = $questions
                    ->pluck('id')
                    ->shuffle()
                    ->take($targetScore)
                    ->toArray();

                $minutesTaken = rand(30, 55);

                $submittedAt = now()
                    ->subDays(rand(2, 10))
                    ->setTime(
                        rand(8, 15),
                        rand(0, 59),
                        rand(0, 59)
                    );

                $startedAt = $submittedAt
                    ->copy()
                    ->subMinutes($minutesTaken);

                $session = ExamSession::create([
                    'exam_id' =>
                        $exam->id,

                    'student_name' =>
                        $student->student_name,

                    'started_at' =>
                        $startedAt,

                    'submitted_at' =>
                        $submittedAt,

                    'score' =>
                        0,

                    'percentage' =>
                        0,

                    'progress' =>
                        100,

                    'status' =>
                        'submitted',
                ]);

                $actualScore = 0;

                foreach ($questions as $question) {

                    $isCorrect = in_array(
                        $question->id,
                        $correctQuestionIds
                    );

                    $correctOption = $question
                        ->options
                        ->firstWhere(
                            'is_correct',
                            true
                        );

                    $wrongOptions = $question
                        ->options
                        ->where(
                            'is_correct',
                            false
                        )
                        ->values();

                    if ($isCorrect && $correctOption) {

                        $studentAnswer =
                            $correctOption->option_text;

                    } elseif ($wrongOptions->isNotEmpty()) {

                        $studentAnswer =
                            $wrongOptions
                                ->random()
                                ->option_text;

                    } else {

                        $studentAnswer =
                            'No Answer';

                        $isCorrect = false;
                    }

                    StudentAnswer::create([
                        'exam_session_id' =>
                            $session->id,

                        'question_id' =>
                            $question->id,

                        'answer' =>
                            $studentAnswer,

                        'is_correct' =>
                            $isCorrect,
                    ]);

                    if ($isCorrect) {
                        $actualScore++;
                    }
                }

                /*
                 * 20 questions, 1 point each.
                 */
                $percentage = round(
                    ($actualScore / 20) * 100,
                    2
                );

                $session->update([
                    'score' =>
                        $actualScore,

                    'percentage' =>
                        $percentage,

                    'progress' =>
                        100,

                    'status' =>
                        'submitted',
                ]);
            }

            $exam->update([
                'status' => 'finished',
                'ended_at' => now(),
            ]);
        });

        $this->command->info(
            'Grade 11 STEM Engineering results created successfully.'
        );

        $this->command->info(
            'Submitted students: ' .
            ExamSession::where(
                'exam_id',
                25
            )
            ->where(
                'status',
                'submitted'
            )
            ->count()
        );
    }
}
