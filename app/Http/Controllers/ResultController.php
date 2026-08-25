<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use Barryvdh\DomPDF\Facade\Pdf;

class ResultController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FACULTY / ADMIN OWNERSHIP HELPERS
    |--------------------------------------------------------------------------
    */

    private function findAccessibleExam(
        $id,
        array $with = []
    ) {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        $query = Exam::query();

        if (!empty($with)) {
            $query->with($with);
        }

        $query->where('id', $id);

        /*
         * Faculty:
         * only exams they created.
         *
         * Admin:
         * all exams.
         */
        if ($user->role !== 'admin') {
            $query->where(
                'created_by',
                $user->id
            );
        }

        return $query->first();
    }


    private function findAccessibleSession(
        $sessionId,
        array $with = []
    ) {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        $query = ExamSession::query();

        if (!empty($with)) {
            $query->with($with);
        }

        $query->where(
            'id',
            $sessionId
        );

        /*
         * Faculty can only access sessions
         * belonging to their own examinations.
         */
        if ($user->role !== 'admin') {
            $query->whereHas(
                'exam',
                function ($examQuery) use ($user) {
                    $examQuery->where(
                        'created_by',
                        $user->id
                    );
                }
            );
        }

        return $query->first();
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT RESULT
    |--------------------------------------------------------------------------
    |
    | Student-facing.
    | Do NOT apply faculty ownership here.
    |
    */

    public function studentResult($sessionId)
    {
        $session = ExamSession::with([
            'exam',
            'answers.question',
        ])->find($sessionId);

        if (!$session) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Student result not found.',
            ], 404);
        }

        if (
            $session->status !==
            'submitted'
        ) {
            return response()->json([
                'status' => false,
                'message' =>
                    'The examination has not been submitted yet.',
            ], 403);
        }

        if (!$session->exam) {
            return response()->json([
                'status' => false,
                'message' =>
                    'The examination record is unavailable.',
            ], 404);
        }

        $totalPoints =
            (int) Question::where(
                'exam_id',
                $session->exam_id
            )->sum('points');

        $totalQuestions =
            Question::where(
                'exam_id',
                $session->exam_id
            )->count();

        $correctAnswers =
            $session
                ->answers
                ->where(
                    'is_correct',
                    true
                )
                ->count();

        $wrongAnswers = max(
            $totalQuestions -
            $correctAnswers,
            0
        );

        $percentage =
            (float)
            $session->percentage;

        if ($totalPoints > 0) {
            $percentage = round(
                (
                    (int) $session->score
                    /
                    $totalPoints
                ) * 100,
                2
            );
        }

        $passing =
            (float) (
                $session
                    ->exam
                    ->passing
                ?? 75
            );

        $ongoingStudents =
            ExamSession::where(
                'exam_id',
                $session->exam_id
            )
            ->where(
                'status',
                'ongoing'
            )
            ->count();

        $leaderboardAvailable =
            strtolower(
                (string)
                $session->exam->status
            ) === 'finished'
            ||
            $ongoingStudents === 0;

        $rank = null;

        if ($leaderboardAvailable) {

            $rank =
                ExamSession::where(
                    'exam_id',
                    $session->exam_id
                )
                ->where(
                    'status',
                    'submitted'
                )
                ->where(
                    function ($query) use (
                        $session
                    ) {

                        $query
                            ->where(
                                'percentage',
                                '>',
                                $session->percentage
                            )

                            ->orWhere(
                                function (
                                    $samePercentage
                                ) use ($session) {

                                    $samePercentage
                                        ->where(
                                            'percentage',
                                            $session->percentage
                                        )
                                        ->where(
                                            'score',
                                            '>',
                                            $session->score
                                        );
                                }
                            )

                            ->orWhere(
                                function (
                                    $sameScore
                                ) use ($session) {

                                    $sameScore
                                        ->where(
                                            'percentage',
                                            $session->percentage
                                        )
                                        ->where(
                                            'score',
                                            $session->score
                                        )
                                        ->where(
                                            'time_spent',
                                            '<',
                                            $session->time_spent
                                        );
                                }
                            );
                    }
                )
                ->count() + 1;
        }

        return response()->json([
            'status' => true,

            'data' => [
                'session_id' =>
                    $session->id,

                'exam_id' =>
                    $session->exam_id,

                'student_name' =>
                    $session->student_name,

                'exam_title' =>
                    $session->exam->title
                    ?? 'Examination',

                'grade' =>
                    $session->exam->grade
                    ?? null,

                'section' =>
                    $session->exam->section
                    ?? null,

                'subject' =>
                    $session->exam->subject
                    ?? null,

                'score' =>
                    (int) $session->score,

                'total_points' =>
                    $totalPoints,

                'percentage' =>
                    $percentage,

                'passing' =>
                    $passing,

                'correct_answers' =>
                    $correctAnswers,

                'wrong_answers' =>
                    $wrongAnswers,

                'total_questions' =>
                    $totalQuestions,

                'time_spent' =>
                    (int)
                    $session->time_spent,

                'rank' =>
                    $rank,

                'leaderboard_available' =>
                    $leaderboardAvailable,

                'result_status' =>
                    $percentage >= $passing
                        ? 'Passed'
                        : 'Failed',

                'submitted_at' =>
                    $session->submitted_at,
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT LEADERBOARD
    |--------------------------------------------------------------------------
    |
    | Student-facing.
    |
    */

    public function studentLeaderboard(
        $examId
    ) {
        $exam =
            Exam::with('questions')
                ->find($examId);

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Examination not found.',
            ], 404);
        }

        $ongoingStudents =
            ExamSession::where(
                'exam_id',
                $exam->id
            )
            ->where(
                'status',
                'ongoing'
            )
            ->count();

        $leaderboardAvailable =
            strtolower(
                (string)
                $exam->status
            ) === 'finished'
            ||
            $ongoingStudents === 0;

        if (!$leaderboardAvailable) {
            return response()->json([
                'status' => true,

                'leaderboard_available' =>
                    false,

                'message' =>
                    'The Top 5 ranking will be available after the examination ends.',

                'data' =>
                    [],
            ]);
        }

        $totalPoints =
            (int)
            $exam
                ->questions
                ->sum('points');

        $passing =
            (float) (
                $exam->passing
                ?? 75
            );

        $sessions =
            ExamSession::where(
                'exam_id',
                $exam->id
            )
            ->where(
                'status',
                'submitted'
            )
            ->orderByDesc(
                'percentage'
            )
            ->orderByDesc(
                'score'
            )
            ->orderBy(
                'time_spent'
            )
            ->orderBy(
                'submitted_at'
            )
            ->limit(5)
            ->get();

        $leaderboard =
            $sessions
                ->values()
                ->map(
                    function (
                        $session,
                        $index
                    ) use (
                        $totalPoints,
                        $passing
                    ) {

                        $percentage =
                            (float)
                            $session
                                ->percentage;

                        if (
                            $totalPoints > 0
                        ) {
                            $percentage =
                                round(
                                    (
                                        (int)
                                        $session
                                            ->score
                                        /
                                        $totalPoints
                                    ) * 100,
                                    2
                                );
                        }

                        return [
                            'rank' =>
                                $index + 1,

                            'session_id' =>
                                $session->id,

                            'student_name' =>
                                $session
                                    ->student_name,

                            'score' =>
                                (int)
                                $session->score,

                            'total_points' =>
                                $totalPoints,

                            'percentage' =>
                                $percentage,

                            'time_spent' =>
                                (int)
                                $session
                                    ->time_spent,

                            'result_status' =>
                                $percentage
                                >=
                                $passing
                                    ? 'Passed'
                                    : 'Failed',
                        ];
                    }
                );

        return response()->json([
            'status' => true,

            'leaderboard_available' =>
                true,

            'data' =>
                $leaderboard,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT HISTORY / ALL FACULTY RESULTS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Unauthenticated.'
            ], 401);
        }

        $query =
            ExamSession::with([
                'exam',
                'answers.question'
            ]);

        /*
         * Faculty only sees history
         * from their own examinations.
         */
        if ($user->role !== 'admin') {

            $query->whereHas(
                'exam',
                function (
                    $examQuery
                ) use ($user) {

                    $examQuery->where(
                        'created_by',
                        $user->id
                    );
                }
            );
        }

        $results =
            $query
                ->latest()
                ->get();

        return response()->json([
            'status' => true,
            'data' => $results
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | RESULTS OF A SPECIFIC EXAM
    |--------------------------------------------------------------------------
    */

    public function examResults($id)
    {
        /*
         * Ownership check first.
         */
        $exam =
            $this->findAccessibleExam(
                $id
            );

        if (!$exam) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Examination not found or access denied.',
            ], 404);
        }

        $sessions =
            ExamSession::with([
                'exam',
                'answers.question'
            ])
            ->where(
                'exam_id',
                $exam->id
            )
            ->where(
                'status',
                'submitted'
            )
            ->latest()
            ->get();

        $results =
            $sessions->map(
                function ($session) {

                    $totalQuestions =
                        Question::where(
                            'exam_id',
                            $session->exam_id
                        )
                        ->count();

                    $correct =
                        $session
                            ->answers
                            ->where(
                                'is_correct',
                                true
                            )
                            ->count();

                    $wrong =
                        max(
                            $totalQuestions
                            -
                            $correct,
                            0
                        );

                    $percentage =
                        (float)
                        $session
                            ->percentage;

                    if (
                        $percentage <= 0
                        &&
                        $totalQuestions > 0
                    ) {

                        $percentage =
                            round(
                                (
                                    $correct
                                    /
                                    $totalQuestions
                                ) * 100,
                                2
                            );
                    }

                    return [
                        'id' =>
                            $session->id,

                        'student_name' =>
                            $session
                                ->student_name,

                        'score' =>
                            $session->score,

                        'percentage' =>
                            $percentage,

                        'correct' =>
                            $correct,

                        'wrong' =>
                            $wrong,

                        'time_spent' =>
                            $session
                                ->time_spent,

                        'submitted_at' =>
                            $session
                                ->submitted_at,
                    ];
                }
            );

        return response()->json([
            'status' => true,

            'exam' => [
                'id' =>
                    $exam->id,

                'title' =>
                    $exam->title,

                'grade' =>
                    $exam->grade,

                'section' =>
                    $exam->section,

                'subject' =>
                    $exam->subject,

                'passing' =>
                    $exam->passing,

                'questions_count' =>
                    Question::where(
                        'exam_id',
                        $exam->id
                    )
                    ->count(),
            ],

            'data' =>
                $results
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VIEW SINGLE FACULTY RESULT
    |--------------------------------------------------------------------------
    */

    public function show($session_id)
    {
        $session =
            $this->findAccessibleSession(
                $session_id,
                [
                    'exam',
                    'answers.question'
                ]
            );

        if (!$session) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Result not found or access denied.'
            ], 404);
        }

        $totalQuestions =
            Question::where(
                'exam_id',
                $session->exam_id
            )
            ->count();

        $correct =
            $session
                ->answers
                ->where(
                    'is_correct',
                    true
                )
                ->count();

        $wrong =
            max(
                $totalQuestions
                -
                $correct,
                0
            );

        $percentage =
            (float)
            $session->percentage;

        if (
            $percentage <= 0
            &&
            $totalQuestions > 0
        ) {

            $percentage =
                round(
                    (
                        $correct
                        /
                        $totalQuestions
                    ) * 100,
                    2
                );
        }

        $passingScore =
            $session
                ->exam
                ->passing
            ?? 75;

        $status =
            $percentage
            >=
            $passingScore
                ? 'Passed'
                : 'Failed';

        return response()->json([
            'status' => true,

            'student_name' =>
                $session->student_name,

            'exam' =>
                $session->exam,

            'score' =>
                $session->score,

            'correct' =>
                $correct,

            'wrong' =>
                $wrong,

            'total_questions' =>
                $totalQuestions,

            'percentage' =>
                $percentage,

            'result_status' =>
                $status,

            'answers' =>
                $session->answers
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ITEM ANALYSIS API
    |--------------------------------------------------------------------------
    */

    public function itemAnalysis($id)
    {
        $data =
            $this
                ->buildItemAnalysisData(
                    $id
                );

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Examination not found or access denied.'
            ], 404);
        }

        return response()->json([
            'status' => true,

            'data' =>
                $data['items'],

            'summary' =>
                $data['summary'],

            'statistics' =>
                $data['statistics'],

            'exam' =>
                $data['exam']
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | BUILD ITEM ANALYSIS DATA
    |--------------------------------------------------------------------------
    */

    private function buildItemAnalysisData(
    $id
) {
    /*
    |--------------------------------------------------------------------------
    | FIND EXAM
    |--------------------------------------------------------------------------
    */

    $exam =
        $this->findAccessibleExam(
            $id
        );

    if (!$exam) {
        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | GET QUESTIONS
    |--------------------------------------------------------------------------
    */

    $questions =
        Question::with([
            'options',
            'answers'
        ])
        ->where(
            'exam_id',
            $exam->id
        )
        ->orderBy(
            'question_order'
        )
        ->get();


    /*
    |--------------------------------------------------------------------------
    | GET SUBMITTED EXAM SESSIONS
    |--------------------------------------------------------------------------
    */

    $sessions =
        ExamSession::where(
            'exam_id',
            $exam->id
        )
        ->where(
            'status',
            'submitted'
        )
        ->get();


    $totalStudents =
        $sessions->count();


    /*
    |--------------------------------------------------------------------------
    | PREPARE UPPER AND LOWER GROUPS
    |--------------------------------------------------------------------------
    |
    | Used for the Discrimination Index.
    |
    | Students are ranked according to their
    | examination score.
    |
    | Upper 27% = high-performing group
    | Lower 27% = low-performing group
    |
    */

    $rankedSessions =
        $sessions
            ->sortByDesc(
                'score'
            )
            ->values();


    $groupSize = 0;


    if ($totalStudents >= 2) {

        $groupSize =
            max(
                1,
                (int) round(
                    $totalStudents
                    *
                    0.27
                )
            );
    }


    $upperGroupIds =
        $groupSize > 0

            ? $rankedSessions
                ->take(
                    $groupSize
                )
                ->pluck(
                    'id'
                )
                ->values()

            : collect();


    $lowerGroupIds =
        $groupSize > 0

            ? $rankedSessions
                ->reverse()
                ->take(
                    $groupSize
                )
                ->pluck(
                    'id'
                )
                ->values()

            : collect();


    /*
    |--------------------------------------------------------------------------
    | ITEM ANALYSIS
    |--------------------------------------------------------------------------
    */

    $items =
        $questions->map(
            function (
                $question,
                $index
            ) use (
                $totalStudents,
                $upperGroupIds,
                $lowerGroupIds,
                $groupSize
            ) {

                /*
                |--------------------------------------------------------------------------
                | ANSWERS FOR CURRENT QUESTION
                |--------------------------------------------------------------------------
                */

                $answers =
                    $question
                        ->answers;


                /*
                |--------------------------------------------------------------------------
                | CORRECT RESPONSES
                |--------------------------------------------------------------------------
                */

                $correct =
                    $answers
                        ->where(
                            'is_correct',
                            true
                        )
                        ->count();


                /*
                |--------------------------------------------------------------------------
                | WRONG RESPONSES
                |--------------------------------------------------------------------------
                */

                $wrong =
                    max(
                        $totalStudents
                        -
                        $correct,
                        0
                    );


                /*
                |--------------------------------------------------------------------------
                | SUCCESS RATE / ITEM PERCENTAGE
                |--------------------------------------------------------------------------
                |
                | Formula:
                |
                | Correct Responses
                | ----------------- × 100
                | Total Examinees
                |
                */

                $successRate =
                    $totalStudents > 0

                        ? round(
                            (
                                $correct
                                /
                                $totalStudents
                            )
                            *
                            100,
                            2
                        )

                        : 0;


                /*
                |--------------------------------------------------------------------------
                | DISCRIMINATION INDEX
                |--------------------------------------------------------------------------
                |
                | Formula:
                |
                | D = (RU - RL) / N
                |
                | RU = Correct responses from upper group
                | RL = Correct responses from lower group
                | N  = Number of examinees in one group
                |
                */

                $discrimination =
                    null;


                if ($groupSize > 0) {

                    $upperCorrect =
                        $answers
                            ->whereIn(
                                'exam_session_id',
                                $upperGroupIds
                            )
                            ->where(
                                'is_correct',
                                true
                            )
                            ->count();


                    $lowerCorrect =
                        $answers
                            ->whereIn(
                                'exam_session_id',
                                $lowerGroupIds
                            )
                            ->where(
                                'is_correct',
                                true
                            )
                            ->count();


                    $discrimination =
                        round(
                            (
                                $upperCorrect
                                -
                                $lowerCorrect
                            )
                            /
                            $groupSize,
                            2
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | MASTERY CLASSIFICATION
                |--------------------------------------------------------------------------
                |
                | Agency / School Classification
                |
                | 96–100 = Mastered
                | 86–95  = Approximating Mastery
                | 66–85  = Moving Towards Mastery
                | 35–65  = Average Mastery
                | 0–34   = Low Mastery
                |
                */

                if (
                    $successRate >= 96
                ) {

                    $interpretation =
                        'Mastered';

                    $remarks =
                        'Retain or Revise';

                } elseif (
                    $successRate >= 86
                ) {

                    $interpretation =
                        'Approximating Mastery';

                    $remarks =
                        'Retain';

                } elseif (
                    $successRate >= 66
                ) {

                    $interpretation =
                        'Moving Towards Mastery';

                    $remarks =
                        'Retain';

                } elseif (
                    $successRate >= 35
                ) {

                    $interpretation =
                        'Average Mastery';

                    $remarks =
                        'Revise';

                } else {

                    $interpretation =
                        'Low Mastery';

                    $remarks =
                        'Reject';
                }


                /*
                |--------------------------------------------------------------------------
                | COMMON WRONG ANSWER
                |--------------------------------------------------------------------------
                */

                $wrongAnswers =
                    $answers
                        ->where(
                            'is_correct',
                            false
                        )
                        ->groupBy(
                            'answer'
                        )
                        ->map(
                            fn ($group) =>
                                $group->count()
                        )
                        ->sortDesc();


                $commonWrongAnswer =
                    $wrongAnswers
                        ->keys()
                        ->first()
                    ??
                    'None';


                /*
                |--------------------------------------------------------------------------
                | RETURN ITEM
                |--------------------------------------------------------------------------
                */

                return [

                    'id' =>
                        $question->id,

                    'number' =>
                        $index + 1,

                    'question' =>
                        $question
                            ->question,

                    'competency' =>
                        $question
                            ->competency
                        ?:
                        'Unassigned Competency',

                    'type' =>
                        $question
                            ->question_type,

                    'successRate' =>
                        $successRate,

                    'percentage' =>
                        $successRate,

                    'correct' =>
                        $correct,

                    'wrong' =>
                        $wrong,

                    'total' =>
                        $totalStudents,

                    'discrimination' =>
                        $discrimination,

                    'commonWrongAnswer' =>
                        $commonWrongAnswer,

                    'interpretation' =>
                        $interpretation,

                    'remarks' =>
                        $remarks,

                ];
            }
        );


    /*
    |--------------------------------------------------------------------------
    | TOTAL ITEMS
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | This follows the SCHOOL FORMULA.
    |
    | We use the NUMBER OF TEST ITEMS,
    | NOT total possible points.
    |
    */

    $totalItems =
        $questions->count();


    /*
    |--------------------------------------------------------------------------
    | MEAN
    |--------------------------------------------------------------------------
    |
    | Formula:
    |
    | Sum of Student Scores
    | ---------------------
    | Number of Examinees
    |
    */

    $mean =
        $totalStudents > 0

            ? round(
                (float)
                $sessions
                    ->avg(
                        'score'
                    ),
                2
            )

            : 0;


    /*
    |--------------------------------------------------------------------------
    | MEAN PERCENTAGE SCORE (MPS)
    |--------------------------------------------------------------------------
    |
    | SCHOOL FORMULA:
    |
    |          Mean
    | MPS = ----------- × 100
    |       Total Items
    |
    | Example:
    |
    | Mean        = 13.32
    | Total Items = 50
    |
    | MPS =
    | (13.32 / 50) × 100
    |
    | MPS = 26.64%
    |
    | IMPORTANT:
    | Do NOT substitute total possible points.
    |
    */

    $mps =
        $totalItems > 0

            ? round(
                (
                    $mean
                    /
                    $totalItems
                )
                *
                100,
                2
            )

            : 0;


    /*
    |--------------------------------------------------------------------------
    | PERFORMANCE LEVEL (PL)
    |--------------------------------------------------------------------------
    |
    | SCHOOL FORMULA:
    |
    |           MPS
    | PL = 50 + ---
    |            2
    |
    | Example:
    |
    | MPS = 26.64
    |
    | 26.64 / 2 = 13.32
    |
    | PL = 50 + 13.32
    |
    | PL = 63.32
    |
    */

    $pl =
        round(
            50
            +
            (
                $mps
                /
                2
            ),
            2
        );


    /*
    |--------------------------------------------------------------------------
    | STANDARD DEVIATION
    |--------------------------------------------------------------------------
    */

    $sd = 0;


    if (
        $totalStudents > 1
    ) {

        $scores =
            $sessions
                ->pluck(
                    'score'
                )
                ->map(
                    fn ($score) =>
                        (float)
                        $score
                );


        $scoreMean =
            $scores->avg();


        $variance =
            $scores
                ->map(
                    function (
                        $score
                    ) use (
                        $scoreMean
                    ) {

                        return pow(
                            $score
                            -
                            $scoreMean,
                            2
                        );
                    }
                )
                ->sum()
            /
            $scores->count();


        $sd =
            round(
                sqrt(
                    $variance
                ),
                4
            );
    }


    /*
    |--------------------------------------------------------------------------
    | MASTERY SUMMARY
    |--------------------------------------------------------------------------
    */

    $masteryLevels = [

        'Mastered',

        'Approximating Mastery',

        'Moving Towards Mastery',

        'Average Mastery',

        'Low Mastery'

    ];


    $summary = [];


    foreach (
        $masteryLevels
        as $level
    ) {

        $matching =
            $items->filter(
                fn ($item) =>
                    $item[
                        'interpretation'
                    ]
                    ===
                    $level
            );


        $summary[] = [

            'level' =>
                $level,

            'items' =>
                $matching
                    ->pluck(
                        'number'
                    )
                    ->implode(
                        ', '
                    )
                ?:
                '—',

            'count' =>
                $matching
                    ->count()

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | RETURN ITEM ANALYSIS DATA
    |--------------------------------------------------------------------------
    */

    return [

        'exam' =>
            $exam,

        'items' =>
            $items,

        'summary' =>
            $summary,

        'statistics' => [

            'total_items' =>
                $totalItems,

            'total_examinees' =>
                $totalStudents,

            'mean' =>
                number_format(
                    $mean,
                    2,
                    '.',
                    ''
                ),

            /*
             * SCHOOL FORMULA:
             *
             * MPS =
             * (Mean / Total Items) × 100
             */
            'mps' =>
                number_format(
                    $mps,
                    2,
                    '.',
                    ''
                ),

            'sd' =>
                number_format(
                    $sd,
                    4,
                    '.',
                    ''
                ),

            /*
             * SCHOOL FORMULA:
             *
             * PL =
             * 50 + (MPS / 2)
             */
            'pl' =>
                number_format(
                    $pl,
                    2,
                    '.',
                    ''
                ),

        ]

    ];
}

    /*
    |--------------------------------------------------------------------------
    | EXPORT ITEM ANALYSIS PDF
    |--------------------------------------------------------------------------
    */

    public function exportItemAnalysisPdf(
        $id
    ) {
        /*
         * buildItemAnalysisData()
         * already checks ownership.
         */
        $data =
            $this
                ->buildItemAnalysisData(
                    $id
                );

        if (!$data) {
            abort(
                404,
                'Examination not found or access denied.'
            );
        }

        $pdf =
            Pdf::loadView(
                'reports.item-analysis-pdf',
                $data
            );

        $pdf->setPaper(
            'A4',
            'landscape'
        );

        $safeTitle =
            preg_replace(
                '/[^A-Za-z0-9\-_ ]/',
                '',
                $data[
                    'exam'
                ]->title
            );

        if (!$safeTitle) {
            $safeTitle =
                'Exam';
        }

        return $pdf->download(
            $safeTitle
            .
            '-Item-Analysis.pdf'
        );
    }
}
