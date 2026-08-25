<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\SchoolClass;

class AbmFabmExamSeeder extends Seeder
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


        DB::transaction(function () use ($schoolClass) {

            /*
            |--------------------------------------------------------------------------
            | CREATE EXAM
            |--------------------------------------------------------------------------
            */

            $exam = Exam::create([

                'title' =>
                    'FABM 1 Quarterly Examination',

                'description' =>
                    'Quarterly examination for Grade 11 ABM.',

                'class_id' =>
                    $schoolClass->id,

                'grade' =>
                    $schoolClass->grade,

                'section' =>
                    $schoolClass->section,

                'subject' =>
                    'Fundamentals of Accountancy, Business and Management 1',

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
                        ->subDays(3)
                        ->subHour(),

                'ended_at' =>
                    now()
                        ->subDays(3),
            ]);


            /*
            |--------------------------------------------------------------------------
            | 20 QUESTIONS
            |--------------------------------------------------------------------------
            */

            $questions = [

                [
                    'question' =>
                        'What is accounting primarily concerned with?',

                    'competency' =>
                        'Explains the nature and purpose of accounting',

                    'options' => [
                        'Recording and communicating financial information',
                        'Manufacturing products',
                        'Hiring employees',
                        'Advertising products',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which of the following is considered an asset?',

                    'competency' =>
                        'Identifies the elements of accounting',

                    'options' => [
                        'Accounts Payable',
                        'Owner\'s Capital',
                        'Cash',
                        'Service Revenue',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'Which of the following is a liability?',

                    'competency' =>
                        'Identifies the elements of accounting',

                    'options' => [
                        'Cash',
                        'Equipment',
                        'Accounts Receivable',
                        'Accounts Payable',
                    ],

                    'answer' => 'D',
                ],

                [
                    'question' =>
                        'What does owner\'s equity represent?',

                    'competency' =>
                        'Explains the basic elements of accounting',

                    'options' => [
                        'The owner\'s claim on the assets of the business',
                        'The amount owed to suppliers',
                        'The total sales of the business',
                        'The cash collected from customers',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which equation represents the basic accounting equation?',

                    'competency' =>
                        'Applies the basic accounting equation',

                    'options' => [
                        'Assets = Liabilities + Owner\'s Equity',
                        'Assets = Revenue - Expenses',
                        'Liabilities = Assets + Equity',
                        'Revenue = Assets + Expenses',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'If a business has assets of ₱100,000 and liabilities of ₱40,000, what is the owner\'s equity?',

                    'competency' =>
                        'Applies the basic accounting equation',

                    'options' => [
                        '₱40,000',
                        '₱60,000',
                        '₱100,000',
                        '₱140,000',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'What is a business transaction?',

                    'competency' =>
                        'Analyzes common business transactions',

                    'options' => [
                        'An economic event that affects the financial position of a business',
                        'A personal activity of an employee',
                        'A marketing slogan',
                        'A business meeting with no financial effect',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'When the owner invests cash in the business, which accounts increase?',

                    'competency' =>
                        'Analyzes the effects of business transactions',

                    'options' => [
                        'Cash and Owner\'s Capital',
                        'Cash and Accounts Payable',
                        'Expenses and Liabilities',
                        'Revenue and Accounts Receivable',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What happens when a business purchases equipment for cash?',

                    'competency' =>
                        'Analyzes the effects of business transactions',

                    'options' => [
                        'Equipment increases and Cash decreases',
                        'Equipment decreases and Cash increases',
                        'Liabilities increase and Cash increases',
                        'Owner\'s Equity decreases and Cash increases',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which account is used for money owed by customers to the business?',

                    'competency' =>
                        'Identifies common accounts used in business',

                    'options' => [
                        'Accounts Payable',
                        'Accounts Receivable',
                        'Owner\'s Capital',
                        'Service Revenue',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which account represents amounts the business owes to suppliers?',

                    'competency' =>
                        'Identifies common accounts used in business',

                    'options' => [
                        'Accounts Receivable',
                        'Cash',
                        'Accounts Payable',
                        'Supplies',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'What is revenue?',

                    'competency' =>
                        'Distinguishes revenues from expenses',

                    'options' => [
                        'Income earned from business activities',
                        'Money owed to creditors',
                        'Resources owned by the business',
                        'Withdrawals made by the owner',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which of the following is an example of an expense?',

                    'competency' =>
                        'Distinguishes revenues from expenses',

                    'options' => [
                        'Service Revenue',
                        'Owner\'s Capital',
                        'Rent Expense',
                        'Accounts Receivable',
                    ],

                    'answer' => 'C',
                ],

                [
                    'question' =>
                        'What is the purpose of a journal in accounting?',

                    'competency' =>
                        'Explains the accounting cycle',

                    'options' => [
                        'To record transactions chronologically',
                        'To advertise business products',
                        'To calculate employee attendance',
                        'To store physical inventory',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What is a ledger?',

                    'competency' =>
                        'Explains the accounting cycle',

                    'options' => [
                        'A collection of accounts used by a business',
                        'A list of customers only',
                        'A document used for advertising',
                        'A list of employees',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What does a debit normally do to an asset account?',

                    'competency' =>
                        'Applies debit and credit rules',

                    'options' => [
                        'Increases it',
                        'Decreases it',
                        'Closes it',
                        'Deletes it',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'What does a credit normally do to a liability account?',

                    'competency' =>
                        'Applies debit and credit rules',

                    'options' => [
                        'Decreases it',
                        'Increases it',
                        'Removes it',
                        'Has no effect',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'What is the purpose of a trial balance?',

                    'competency' =>
                        'Explains the preparation and purpose of a trial balance',

                    'options' => [
                        'To test whether total debits equal total credits',
                        'To determine employee salaries',
                        'To calculate product prices',
                        'To record customer orders',
                    ],

                    'answer' => 'A',
                ],

                [
                    'question' =>
                        'Which financial statement reports assets, liabilities, and owner\'s equity?',

                    'competency' =>
                        'Identifies basic financial statements',

                    'options' => [
                        'Income Statement',
                        'Statement of Financial Position',
                        'Statement of Changes in Equity',
                        'Cash Receipt',
                    ],

                    'answer' => 'B',
                ],

                [
                    'question' =>
                        'Which financial statement reports revenues and expenses for a period?',

                    'competency' =>
                        'Identifies basic financial statements',

                    'options' => [
                        'Income Statement',
                        'Statement of Financial Position',
                        'Bank Statement',
                        'Purchase Order',
                    ],

                    'answer' => 'A',
                ],
            ];


            /*
            |--------------------------------------------------------------------------
            | SAVE QUESTIONS
            |--------------------------------------------------------------------------
            */

            foreach ($questions as $index => $item) {

                $question = Question::create([

                    'exam_id' =>
                        $exam->id,

                    'question' =>
                        $item['question'],

                    'question_type' =>
                        'multiple_choice',

                    /*
                     * IMPORTANT:
                     * Competency is explicitly saved.
                     */
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


                /*
                |--------------------------------------------------------------------------
                | SAVE OPTIONS
                |--------------------------------------------------------------------------
                */

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


            /*
            |--------------------------------------------------------------------------
            | OUTPUT
            |--------------------------------------------------------------------------
            */

            $this->command->info(
                'Grade 11 ABM FABM exam created successfully.'
            );

            $this->command->info(
                'Exam ID: ' .
                $exam->id
            );

            $this->command->info(
                'Questions: ' .
                $exam->questions()->count()
            );

            $this->command->info(
                'Questions with competencies: ' .
                $exam
                    ->questions()
                    ->whereNotNull('competency')
                    ->count()
            );
        });
    }
}
