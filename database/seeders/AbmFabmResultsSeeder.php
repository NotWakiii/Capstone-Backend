<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ClassStudent;
use App\Models\StudentAnswer;
use App\Models\SchoolClass;

class AbmFabmResultsSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | FIND GRADE 11 ABM CLASS
        |--------------------------------------------------------------------------
        */

        $schoolClass = SchoolClass::where(
            'faculty_id',
            1
        )
        ->where(
            'grade',
            'Grade 11'
        )
        ->where(
            'section',
            'ABM'
        )
        ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | FIND FABM EXAM
        |--------------------------------------------------------------------------
        */

        $exam = Exam::with([
            'questions.options'
        ])
        ->where(
            'class_id',
            $schoolClass->id
        )
        ->where(
            'title',
            'FABM 1 Quarterly Examination'
        )
        ->latest('id')
        ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | GET STUDENTS
        |--------------------------------------------------------------------------
        */

        $students = ClassStudent::where(
            'class_id',
            $schoolClass->id
        )
        ->orderBy('id')
        ->get();


        /*
        |--------------------------------------------------------------------------
        | GET QUESTIONS
        |--------------------------------------------------------------------------
        */

        $questions =
            $exam->questions;


        if ($students->isEmpty()) {
            throw new \Exception(
                'No students found in Grade 11 ABM.'
            );
        }


        if ($questions->count() !== 20) {
            throw new \Exception(
                'FABM exam must contain exactly 20 questions.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TARGET SCORES
        |--------------------------------------------------------------------------
        */

        $targetScores = [

            20, 19, 19, 18, 18,
            18, 17, 17, 17, 16,

            16, 16, 16, 15, 15,
            15, 15, 14, 14, 14,

            14, 13, 13, 13, 13,
            12, 12, 12, 11, 11,

            11, 10, 10, 10, 9,
            9, 8, 8, 7, 6,

        ];


        DB::transaction(
            function () use (
                $exam,
                $students,
                $questions,
                $targetScores
            ) {

                /*
                |--------------------------------------------------------------------------
                | REMOVE EXISTING DEMO RESULTS
                |--------------------------------------------------------------------------
                */

                foreach ($students as $student) {

                    $oldSessions =
                        ExamSession::where(
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
                        )
                        ->delete();


                        $oldSession->delete();
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE SUBMITTED RESULTS
                |--------------------------------------------------------------------------
                */

                foreach ($students as $index => $student) {

                    $targetScore =
                        $targetScores[
                            $index %
                            count($targetScores)
                        ];


                    /*
                     * Randomly determine which
                     * questions are answered correctly.
                     */
                    $correctQuestionIds =
                        $questions
                            ->pluck('id')
                            ->shuffle()
                            ->take(
                                $targetScore
                            )
                            ->toArray();


                    /*
                     * Realistic exam duration.
                     */
                    $minutesTaken =
                        rand(
                            30,
                            57
                        );


                    /*
                     * Realistic previous
                     * submission date.
                     */
                    $submittedAt =
                        now()
                            ->subDays(
                                rand(
                                    2,
                                    15
                                )
                            )
                            ->setTime(
                                rand(
                                    8,
                                    15
                                ),
                                rand(
                                    0,
                                    59
                                ),
                                rand(
                                    0,
                                    59
                                )
                            );


                    $startedAt =
                        $submittedAt
                            ->copy()
                            ->subMinutes(
                                $minutesTaken
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | CREATE SESSION
                    |--------------------------------------------------------------------------
                    */

                    $session =
                        ExamSession::create([

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


                    /*
                    |--------------------------------------------------------------------------
                    | CREATE ANSWERS
                    |--------------------------------------------------------------------------
                    */

                    foreach ($questions as $question) {

                        $isCorrect =
                            in_array(
                                $question->id,
                                $correctQuestionIds
                            );


                        $correctOption =
                            $question
                                ->options
                                ->firstWhere(
                                    'is_correct',
                                    true
                                );


                        $wrongOptions =
                            $question
                                ->options
                                ->where(
                                    'is_correct',
                                    false
                                )
                                ->values();


                        if (
                            $isCorrect
                            &&
                            $correctOption
                        ) {

                            $studentAnswer =
                                $correctOption
                                    ->option_text;

                        } elseif (
                            $wrongOptions
                                ->isNotEmpty()
                        ) {

                            $studentAnswer =
                                $wrongOptions
                                    ->random()
                                    ->option_text;

                        } else {

                            $studentAnswer =
                                'No Answer';

                            $isCorrect =
                                false;
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
                    |--------------------------------------------------------------------------
                    | CALCULATE SCORE
                    |--------------------------------------------------------------------------
                    */

                    $percentage =
                        round(
                            (
                                $actualScore
                                /
                                $questions->count()
                            )
                            *
                            100,
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE SESSION
                    |--------------------------------------------------------------------------
                    */

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


                /*
                |--------------------------------------------------------------------------
                | MARK EXAM FINISHED
                |--------------------------------------------------------------------------
                */

                $exam->update([

                    'status' =>
                        'finished',

                    'ended_at' =>
                        now(),

                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | OUTPUT
        |--------------------------------------------------------------------------
        */

        $submitted =
            ExamSession::where(
                'exam_id',
                $exam->id
            )
            ->where(
                'status',
                'submitted'
            )
            ->count();


        $this->command->info(
            'Grade 11 ABM FABM results created successfully.'
        );

        $this->command->info(
            'Exam ID: ' .
            $exam->id
        );

        $this->command->info(
            'Submitted students: ' .
            $submitted
        );
    }
}
