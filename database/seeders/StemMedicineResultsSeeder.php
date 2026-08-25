<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ClassStudent;
use App\Models\StudentAnswer;
use App\Models\SchoolClass;

class StemMedicineResultsSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | FIND STEM MEDICINE CLASS
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
            'STEM - Medicine'
        )
        ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | FIND THE EXAM
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
            'Earth and Life Science Quarterly Examination'
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

        $questions = $exam->questions;


        if ($students->isEmpty()) {

            throw new \Exception(
                'No students found in STEM Medicine.'
            );
        }


        if ($questions->count() !== 20) {

            throw new \Exception(
                'STEM Medicine exam must contain exactly 20 questions.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TARGET SCORES
        |--------------------------------------------------------------------------
        |
        | 40 realistic scores.
        |
        | Because there are 20 questions:
        |
        | 20 = 100%
        | 19 = 95%
        | 18 = 90%
        | 17 = 85%
        | 16 = 80%
        | 15 = 75%
        | 14 = 70%
        | etc.
        |
        */

        $targetScores = [

            20,
            19,
            19,
            18,
            18,

            18,
            17,
            17,
            17,
            17,

            16,
            16,
            16,
            16,
            15,

            15,
            15,
            15,
            15,
            14,

            14,
            14,
            14,
            13,
            13,

            13,
            13,
            12,
            12,
            12,

            11,
            11,
            11,
            10,
            10,

            9,
            9,
            8,
            8,
            7,
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
                | REMOVE OLD DEMO RESULTS
                |--------------------------------------------------------------------------
                |
                | This makes the seeder safer to run again.
                |
                */

                foreach (
                    $students
                    as $student
                ) {

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


                    foreach (
                        $oldSessions
                        as $oldSession
                    ) {

                        StudentAnswer::where(
                            'exam_session_id',
                            $oldSession->id
                        )->delete();


                        $oldSession->delete();
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | CREATE RESULTS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $students
                    as $index => $student
                ) {

                    /*
                     * Assign target score.
                     */
                    $targetScore =
                        $targetScores[
                            $index
                            %
                            count(
                                $targetScores
                            )
                        ];


                    /*
                     * Randomly select which
                     * questions are correct.
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
                     * Generate realistic
                     * completion time.
                     */
                    $minutesTaken =
                        rand(
                            28,
                            55
                        );


                    /*
                     * Generate previous
                     * submission date.
                     */
                    $submittedAt =
                        now()
                            ->subDays(
                                rand(
                                    2,
                                    12
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
                    | CREATE EXAM SESSION
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
                    | CREATE STUDENT ANSWERS
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $questions
                        as $question
                    ) {

                        $isCorrect =
                            in_array(
                                $question->id,
                                $correctQuestionIds
                            );


                        /*
                         * Find correct option.
                         */
                        $correctOption =
                            $question
                                ->options
                                ->firstWhere(
                                    'is_correct',
                                    true
                                );


                        /*
                         * Find incorrect options.
                         */
                        $wrongOptions =
                            $question
                                ->options
                                ->where(
                                    'is_correct',
                                    false
                                )
                                ->values();


                        /*
                         * Correct answer.
                         */
                        if (
                            $isCorrect
                            &&
                            $correctOption
                        ) {

                            $studentAnswer =
                                $correctOption
                                    ->option_text;
                        }


                        /*
                         * Wrong answer.
                         */
                        elseif (
                            $wrongOptions
                                ->isNotEmpty()
                        ) {

                            $studentAnswer =
                                $wrongOptions
                                    ->random()
                                    ->option_text;
                        }


                        /*
                         * Fallback.
                         */
                        else {

                            $studentAnswer =
                                'No Answer';

                            $isCorrect =
                                false;
                        }


                        /*
                         * Save answer.
                         */
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
                    | CALCULATE RESULT
                    |--------------------------------------------------------------------------
                    |
                    | 20 questions × 1 point.
                    |
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
                | FINISH EXAM
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
            'STEM Medicine results created successfully.'
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
