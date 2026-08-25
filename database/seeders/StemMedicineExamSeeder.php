<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;

class StemMedicineExamSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Find Grade 11 STEM Medicine.
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

        DB::transaction(function () use ($schoolClass) {

            /*
             * Create exam.
             */
            $exam = Exam::create([
                'title' =>
                    'Earth and Life Science Quarterly Examination',

                'description' =>
                    'Quarterly examination for Grade 11 STEM Medicine.',

                'class_id' =>
                    $schoolClass->id,

                'grade' =>
                    $schoolClass->grade,

                'section' =>
                    $schoolClass->section,

                'subject' =>
                    'Earth and Life Science',

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
                        ->subDays(5)
                        ->subHour(),

                'ended_at' =>
                    now()
                        ->subDays(5),
            ]);

            /*
             * 20 Questions.
             */
            $questions = [

                [
                    'question' =>
                        'What is the basic unit of life?',

                    'competency' =>
                        'Explains the fundamental concepts of life',

                    'options' => [
                        'Tissue',
                        'Cell',
                        'Organ',
                        'Organism',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which organelle is known as the powerhouse of the cell?',

                    'competency' =>
                        'Identifies the structures and functions of cells',

                    'options' => [
                        'Nucleus',
                        'Ribosome',
                        'Mitochondrion',
                        'Golgi apparatus',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'Which structure contains the genetic material of a eukaryotic cell?',

                    'competency' =>
                        'Identifies the structures and functions of cells',

                    'options' => [
                        'Nucleus',
                        'Cell wall',
                        'Cytoplasm',
                        'Vacuole',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What process allows plants to convert light energy into chemical energy?',

                    'competency' =>
                        'Explains biological processes essential to life',

                    'options' => [
                        'Respiration',
                        'Digestion',
                        'Photosynthesis',
                        'Fermentation',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'Which gas is primarily used by plants during photosynthesis?',

                    'competency' =>
                        'Explains biological processes essential to life',

                    'options' => [
                        'Oxygen',
                        'Carbon dioxide',
                        'Nitrogen',
                        'Hydrogen',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'What molecule carries hereditary information in living organisms?',

                    'competency' =>
                        'Explains the role of genetic material',

                    'options' => [
                        'ATP',
                        'DNA',
                        'Glucose',
                        'Protein',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which body system transports oxygen and nutrients throughout the human body?',

                    'competency' =>
                        'Explains interactions among organ systems',

                    'options' => [
                        'Digestive system',
                        'Circulatory system',
                        'Nervous system',
                        'Skeletal system',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which organ is primarily responsible for pumping blood throughout the body?',

                    'competency' =>
                        'Explains interactions among organ systems',

                    'options' => [
                        'Brain',
                        'Liver',
                        'Heart',
                        'Kidney',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'Which organs are primarily responsible for gas exchange in humans?',

                    'competency' =>
                        'Explains interactions among organ systems',

                    'options' => [
                        'Kidneys',
                        'Lungs',
                        'Stomach',
                        'Pancreas',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which blood cells are primarily responsible for carrying oxygen?',

                    'competency' =>
                        'Explains the functions of blood components',

                    'options' => [
                        'Platelets',
                        'White blood cells',
                        'Red blood cells',
                        'Plasma cells',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'Which layer of Earth is the outermost solid layer?',

                    'competency' =>
                        'Describes the structure of the Earth',

                    'options' => [
                        'Core',
                        'Mantle',
                        'Crust',
                        'Outer core',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'What theory explains the movement of large sections of Earth\'s lithosphere?',

                    'competency' =>
                        'Explains processes occurring within the Earth',

                    'options' => [
                        'Cell theory',
                        'Plate tectonics',
                        'Evolution',
                        'Relativity',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'What type of rock forms when magma or lava cools and solidifies?',

                    'competency' =>
                        'Classifies rocks based on their formation',

                    'options' => [
                        'Sedimentary',
                        'Metamorphic',
                        'Igneous',
                        'Organic',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'What type of rock forms from compacted and cemented sediments?',

                    'competency' =>
                        'Classifies rocks based on their formation',

                    'options' => [
                        'Igneous',
                        'Sedimentary',
                        'Metamorphic',
                        'Magma',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which process breaks rocks into smaller pieces without transporting them?',

                    'competency' =>
                        'Explains geological processes on Earth',

                    'options' => [
                        'Weathering',
                        'Deposition',
                        'Eruption',
                        'Condensation',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which natural hazard results from the sudden movement of Earth\'s crust?',

                    'competency' =>
                        'Explains geological hazards',

                    'options' => [
                        'Drought',
                        'Earthquake',
                        'Typhoon',
                        'Flood',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which instrument is commonly used to detect and record seismic waves?',

                    'competency' =>
                        'Explains geological hazards',

                    'options' => [
                        'Thermometer',
                        'Barometer',
                        'Seismograph',
                        'Anemometer',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'What is biodiversity?',

                    'competency' =>
                        'Explains the importance of biodiversity',

                    'options' => [
                        'The movement of tectonic plates',
                        'The variety of living organisms in an area',
                        'The amount of rainfall in an ecosystem',
                        'The process of rock formation',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which relationship benefits both organisms involved?',

                    'competency' =>
                        'Describes interactions among organisms',

                    'options' => [
                        'Predation',
                        'Parasitism',
                        'Mutualism',
                        'Competition',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'Which level of biological organization includes living organisms and their physical environment?',

                    'competency' =>
                        'Explains levels of ecological organization',

                    'options' => [
                        'Population',
                        'Community',
                        'Ecosystem',
                        'Species',
                    ],

                    'answer' => 'C',
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
                "STEM Medicine exam created. Exam ID: {$exam->id}"
            );

            $this->command->info(
                'Questions: ' .
                $exam->questions()->count()
            );
        });
    }
}
