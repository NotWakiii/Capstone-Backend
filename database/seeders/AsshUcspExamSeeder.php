<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;

class AsshUcspExamSeeder extends Seeder
{
    public function run(): void
    {
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
            'ASSH'
        )
        ->firstOrFail();

        DB::transaction(function () use ($schoolClass) {

            $exam = Exam::create([
                'title' =>
                    'Understanding Culture, Society and Politics Quarterly Examination',

                'description' =>
                    'Quarterly examination for Grade 11 ASSH.',

                'class_id' =>
                    $schoolClass->id,

                'grade' =>
                    $schoolClass->grade,

                'section' =>
                    $schoolClass->section,

                'subject' =>
                    'Understanding Culture, Society and Politics',

                'duration' =>
                    60,

                'passing' =>
                    75,

                'access_code' =>
                    strtoupper(
                        Str::random(6)
                    ),

                'created_by' =>
                    1,

                'status' =>
                    'finished',

                'started_at' =>
                    now()
                        ->subDays(4)
                        ->subHour(),

                'ended_at' =>
                    now()
                        ->subDays(4),
            ]);

            $questions = [

                [
                    'question' =>
                        'What refers to the shared beliefs, values, practices, and traditions of a group of people?',

                    'competency' =>
                        'Explains the concept of culture',

                    'options' => [
                        'Culture',
                        'Economy',
                        'Politics',
                        'Technology',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which of the following is an example of material culture?',

                    'competency' =>
                        'Distinguishes material and non-material culture',

                    'options' => [
                        'Beliefs',
                        'Language',
                        'Clothing',
                        'Values',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'Which of the following is an example of non-material culture?',

                    'competency' =>
                        'Distinguishes material and non-material culture',

                    'options' => [
                        'House',
                        'Tradition',
                        'Vehicle',
                        'Tool',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'What is the process by which individuals learn the norms and values of society?',

                    'competency' =>
                        'Explains the process of socialization',

                    'options' => [
                        'Migration',
                        'Socialization',
                        'Industrialization',
                        'Urbanization',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which institution is considered the primary agent of socialization?',

                    'competency' =>
                        'Identifies the agents of socialization',

                    'options' => [
                        'Family',
                        'Government',
                        'Market',
                        'Media',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What refers to the expected behavior associated with a particular social position?',

                    'competency' =>
                        'Explains social status and social roles',

                    'options' => [
                        'Role',
                        'Culture',
                        'Norm',
                        'Symbol',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What is a social norm?',

                    'competency' =>
                        'Explains norms and social control',

                    'options' => [
                        'A form of government',
                        'A rule or expectation for behavior',
                        'A type of organization',
                        'A political ideology',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which of the following is an example of a formal norm?',

                    'competency' =>
                        'Distinguishes formal and informal norms',

                    'options' => [
                        'Greeting elders politely',
                        'Following traffic laws',
                        'Eating with friends',
                        'Wearing casual clothes',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'What refers to the unequal distribution of wealth, power, and resources in society?',

                    'competency' =>
                        'Explains social stratification',

                    'options' => [
                        'Social mobility',
                        'Social stratification',
                        'Socialization',
                        'Assimilation',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which term refers to movement from one social position to another?',

                    'competency' =>
                        'Explains social mobility',

                    'options' => [
                        'Social mobility',
                        'Social control',
                        'Cultural diffusion',
                        'Social conflict',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What refers to a group of people who share a common territory and culture?',

                    'competency' =>
                        'Explains the concept of society',

                    'options' => [
                        'Society',
                        'Institution',
                        'Organization',
                        'Community leader',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which social institution is mainly responsible for formal education?',

                    'competency' =>
                        'Identifies major social institutions',

                    'options' => [
                        'Family',
                        'School',
                        'Religion',
                        'Economy',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which institution is primarily concerned with production and distribution of goods and services?',

                    'competency' =>
                        'Identifies major social institutions',

                    'options' => [
                        'Economy',
                        'Religion',
                        'Education',
                        'Family',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What refers to the system through which power and authority are exercised in society?',

                    'competency' =>
                        'Explains political organization and authority',

                    'options' => [
                        'Culture',
                        'Politics',
                        'Kinship',
                        'Education',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'What is authority?',

                    'competency' =>
                        'Explains power and authority',

                    'options' => [
                        'The legitimate use of power',
                        'The absence of rules',
                        'A type of culture',
                        'A form of social conflict',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which term refers to the ability to influence the behavior of others?',

                    'competency' =>
                        'Explains power and authority',

                    'options' => [
                        'Power',
                        'Norm',
                        'Custom',
                        'Status',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What refers to the spread of cultural traits from one society to another?',

                    'competency' =>
                        'Explains cultural change and diffusion',

                    'options' => [
                        'Cultural diffusion',
                        'Social stratification',
                        'Political participation',
                        'Social control',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What is ethnocentrism?',

                    'competency' =>
                        'Explains ethnocentrism and cultural relativism',

                    'options' => [
                        'Judging another culture using the standards of one\'s own culture',
                        'Accepting all political ideas',
                        'Rejecting all cultural traditions',
                        'Studying population growth',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What is cultural relativism?',

                    'competency' =>
                        'Explains ethnocentrism and cultural relativism',

                    'options' => [
                        'Understanding a culture based on its own context',
                        'Comparing all cultures using one standard',
                        'Rejecting cultural differences',
                        'Ignoring cultural practices',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which concept refers to the participation of citizens in government and public affairs?',

                    'competency' =>
                        'Explains political participation',

                    'options' => [
                        'Political participation',
                        'Cultural diffusion',
                        'Social mobility',
                        'Urbanization',
                    ],

                    'answer' => 'A',
                ],

            ];

            foreach ($questions as $index => $item) {

                $question = Question::create([
                    'exam_id' =>
                        $exam->id,

                    'question' =>
                        $item['question'],

                    'question_type' =>
                        'multiple_choice',

                    'competency' =>
                        $item['competency'],

                    'answer' =>
                        $item['answer'],

                    'points' =>
                        1,

                    'time_limit' =>
                        30,

                    'question_order' =>
                        $index + 1,
                ]);

                foreach (
                    $item['options']
                    as $optionIndex => $optionText
                ) {

                    $letter =
                        chr(
                            65 + $optionIndex
                        );

                    QuestionOption::create([
                        'question_id' =>
                            $question->id,

                        'option_text' =>
                            $optionText,

                        'is_correct' =>
                            $letter === $item['answer'],
                    ]);
                }
            }

            $this->command->info(
                "Grade 11 ASSH UCSP exam created. Exam ID: {$exam->id}"
            );

            $this->command->info(
                'Questions: ' .
                $exam->questions()->count()
            );
        });
    }
}
