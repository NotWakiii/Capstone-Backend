<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        Competency Based Item Analysis
    </title>

    <style>

        @page {
            margin: 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            font-family:
                DejaVu Sans,
                sans-serif;

            font-size: 9px;

            color: #111;
        }


        /* ======================================
           HEADER
        ====================================== */

        .report-header {
            margin-bottom: 14px;

            text-align: center;
        }

        .report-header h1 {
            margin: 0 0 5px;

            font-size: 17px;
        }

        .report-header h2 {
            margin: 0;

            font-size: 11px;

            font-weight: normal;
        }


        /* ======================================
           INFORMATION
        ====================================== */

        .information-table {
            width: 100%;

            margin-bottom: 12px;

            border-collapse: collapse;
        }

        .information-table td {
            padding: 3px 5px;
        }

        .info-label {
            width: 80px;

            font-weight: bold;
        }

        .info-value {
            width: 260px;
        }

        .stat-label {
            width: 120px;

            font-weight: bold;
        }

        .stat-value {
            width: 90px;

            text-align: right;

            font-weight: bold;
        }


        /* ======================================
           MAIN TABLE
        ====================================== */

        .analysis-table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .analysis-table th,
        .analysis-table td {
            padding: 5px;

            border: 1px solid #555;

            vertical-align: middle;
        }

        .analysis-table th {
            background: #6b8e23;

            color: white;

            text-align: center;

            font-size: 8px;

            font-weight: bold;
        }

        .competency {
            width: 28%;

            text-align: center;

            line-height: 1.45;
        }

        .item-no {
            width: 7%;

            text-align: center;
        }

        .correct {
            width: 14%;

            text-align: center;
        }

        .percentage {
            width: 10%;

            text-align: center;
        }

        .interpretation {
            width: 26%;

            text-align: center;
        }

        .remarks {
            width: 15%;

            text-align: center;
        }


        /* ======================================
           SUMMARY
        ====================================== */

        .summary-title {
            margin-top: 18px;

            margin-bottom: 7px;

            text-align: center;

            font-size: 13px;

            font-weight: bold;
        }

        .summary-table {
            width: 100%;

            border-collapse: collapse;
        }

        .summary-table th,
        .summary-table td {
            padding: 6px;

            border: 1px solid #555;

            vertical-align: middle;
        }

        .summary-table th {
            background: #e5e7eb;

            text-align: center;

            font-weight: bold;
        }

        .mastery {
            width: 25%;

            text-align: center;

            font-weight: bold;
        }

        .test-items {
            width: 25%;

            text-align: center;

            line-height: 1.5;
        }

        .description {
            width: 50%;

            line-height: 1.45;
        }


        /* ======================================
           LEGEND
        ====================================== */

        .legend {
            margin-top: 12px;

            font-size: 8px;

            line-height: 1.5;
        }

        .legend-title {
            font-weight: bold;
        }

        /* ======================================
           LEGEND TABLE
        ====================================== */
        .legend-section {
        margin-top: 18px;
        margin-bottom: 18px;
        padding: 14px 16px;

        border: 1px solid #cccccc;
        border-radius: 6px;

        page-break-inside: avoid;
        }

        .legend-title {
            margin-bottom: 10px;

            color: #112244;

            font-size: 15px;
            font-weight: bold;
        }

        .legend-table {
            width: 100%;
            border-collapse: collapse;
        }

        .legend-table td {
            width: 50%;

            padding: 3px 8px;

            vertical-align: top;

            font-size: 10px;
        }

        .legend-item {
            margin-bottom: 6px;
        }

        .legend-color {
            display: inline-block;

            width: 13px;
            height: 13px;

            margin-right: 7px;

            vertical-align: middle;

            border-radius: 2px;
        }

        .legend-mastered {
            background: #244f35;
        }

        .legend-approximating {
            background: #34526d;
        }

        .legend-moving {
            background: #8b5038;
        }

        .legend-average {
            background: #984459;
        }

        .legend-low {
            background: #8b243c;
        }

        .legend-retain-revise {
            background: #6b8e23;
        }

        .legend-retain {
            background: #806846;
        }

        .legend-revise {
            background: #804833;
        }

        .legend-reject {
            background: #a52f43;
        }
        /* ======================================
           SIGNATURES
        ====================================== */

        .signatures {
            width: 100%;

            margin-top: 32px;

            border-collapse: collapse;
        }

        .signatures td {
            width: 25%;

            padding: 0 8px;

            text-align: center;

            vertical-align: bottom;

            font-size: 8px;
        }

        .signature-space {
            height: 28px;
        }

        .signature-line {
            width: 85%;

            margin: 0 auto 4px;

            border-top: 1px solid #111;
        }

    </style>

</head>


<body>


<!-- ======================================
     HEADER
======================================= -->

<div class="report-header">

    <h1>
        COMPETENCY BASED ITEM ANALYSIS
    </h1>

    <h2>
        {{ $exam->title }}
    </h2>

</div>


<!-- ======================================
     EXAM INFORMATION
======================================= -->

<table class="information-table">

    <tr>

        <td class="info-label">
            GRADE:
        </td>

        <td class="info-value">
            {{ $exam->grade ?? '—' }}
        </td>


        <td class="stat-label">
            TOTAL ITEMS:
        </td>

        <td class="stat-value">
            {{ $statistics['total_items'] }}
        </td>

    </tr>


    <tr>

        <td class="info-label">
            SECTION:
        </td>

        <td class="info-value">
            {{ $exam->section ?? '—' }}
        </td>


        <td class="stat-label">
            MEAN:
        </td>

        <td class="stat-value">
            {{ $statistics['mean'] }}
        </td>

    </tr>


    <tr>

        <td class="info-label">
            SUBJECT:
        </td>

        <td class="info-value">
            {{ $exam->subject ?? '—' }}
        </td>


        <td class="stat-label">
            SD:
        </td>

        <td class="stat-value">
            {{ $statistics['sd'] }}
        </td>

    </tr>


    <tr>

        <td></td>
        <td></td>

        <td class="stat-label">
            MPS:
        </td>

        <td class="stat-value">
            {{ $statistics['mps'] }}%
        </td>

    </tr>


    <tr>

        <td></td>
        <td></td>

        <td class="stat-label">
            PL:
        </td>

        <td class="stat-value">

            @if($statistics['pl'] !== null)

                {{ $statistics['pl'] }}

            @else

                —

            @endif

        </td>

    </tr>


    <tr>

        <td></td>
        <td></td>

        <td class="stat-label">
            TOTAL ENROLLMENT:
        </td>

        <td class="stat-value">
            {{ $statistics['total_examinees'] }}
        </td>

    </tr>

</table>


<!-- ======================================
     ITEM ANALYSIS
======================================= -->

<table class="analysis-table">

    <thead>

        <tr>

            <th class="competency">
                COMPETENCIES
            </th>

            <th class="item-no">
                ITEM NO.
            </th>

            <th class="correct">
                NO. OF CORRECT RESPONSE
            </th>

            <th class="percentage">
                PERCENTAGE
            </th>

            <th class="interpretation">
                INTERPRETATION
            </th>

            <th class="remarks">
                REMARKS
            </th>

        </tr>

    </thead>


    <tbody>

        @php

            $groupedItems =
                collect($items)
                    ->groupBy('competency');

        @endphp


        @foreach(
            $groupedItems
            as $competency => $competencyItems
        )

            @foreach(
                $competencyItems
                as $index => $item
            )

                <tr>

                    @if($index === 0)

                        <td
                            class="competency"
                            rowspan="{{ $competencyItems->count() }}"
                        >
                            {{ $competency }}
                        </td>

                    @endif


                    <td class="item-no">
                        {{ $item['number'] }}
                    </td>


                    <td class="correct">
                        {{ $item['correct'] }}
                    </td>


                    <td class="percentage">
                        {{ $item['successRate'] }}%
                    </td>


                    <td class="interpretation">
                        {{ $item['interpretation'] }}
                    </td>


                    <td class="remarks">
                        {{ $item['remarks'] }}
                    </td>

                </tr>

            @endforeach

        @endforeach

    </tbody>

</table>


<!-- ======================================
     SUMMARY
======================================= -->

<div class="summary-title">
    SUMMARY
</div>


<table class="summary-table">

    <thead>

        <tr>

            <th>
                MASTERY LEVEL
            </th>

            <th>
                TEST ITEM
            </th>

            <th>
                REMARKS
            </th>

        </tr>

    </thead>


    <tbody>

        @foreach($summary as $row)

            <tr>

                <td class="mastery">
                    {{ $row['level'] }}
                </td>


                <td class="test-items">
                    {{ $row['items'] }}
                </td>


                <td class="description">

                    @if(
                        $row['level']
                        === 'Mastered'
                    )

                        Students have demonstrated
                        a thorough understanding of
                        the competency and can
                        consistently apply the
                        required knowledge and skills
                        with little or no assistance.

                    @elseif(
                        $row['level']
                        === 'Approximating Mastery'
                    )

                        Students have achieved a high
                        level of understanding of the
                        competency, with only minor
                        misconceptions or errors that
                        can be addressed through brief
                        reinforcement.

                    @elseif(
                        $row['level']
                        === 'Moving Towards Mastery'
                    )

                        Students show a satisfactory
                        understanding of the competency
                        but still require additional
                        practice and reinforcement to
                        attain full mastery.

                    @elseif(
                        $row['level']
                        === 'Average Mastery'
                    )

                        Students have only a partial
                        understanding of the competency.
                        Significant gaps in knowledge
                        and skills are evident,
                        requiring re-teaching and
                        targeted interventions.

                    @else

                        Students have not yet developed
                        the essential knowledge and
                        skills related to the
                        competency. Intensive
                        remediation and focused
                        instructional support are
                        needed.

                    @endif

                </td>

            </tr>

        @endforeach

    </tbody>

</table>


<!-- ======================================
     LEGEND
======================================= -->

<div class="legend">

    <span class="legend-title">
        Legend:
    </span>

</div>
{{-- ==========================================
     LEGEND
========================================== --}}

<div class="legend-section">
    <table class="legend-table">

        <tr>

            <td>

                <div class="legend-item">
                    <span class="legend-color legend-mastered"></span>
                    Mastered
                </div>

                <div class="legend-item">
                    <span class="legend-color legend-approximating"></span>
                    Approximating Mastery
                </div>

                <div class="legend-item">
                    <span class="legend-color legend-moving"></span>
                    Moving Towards Mastery
                </div>

                <div class="legend-item">
                    <span class="legend-color legend-average"></span>
                    Average Mastery
                </div>

                <div class="legend-item">
                    <span class="legend-color legend-low"></span>
                    Low Mastery
                </div>

            </td>

            <td>

                <div class="legend-item">
                    <span class="legend-color legend-retain-revise"></span>
                    Retain or Revise
                </div>

                <div class="legend-item">
                    <span class="legend-color legend-retain"></span>
                    Retain
                </div>

                <div class="legend-item">
                    <span class="legend-color legend-revise"></span>
                    Revise
                </div>

                <div class="legend-item">
                    <span class="legend-color legend-reject"></span>
                    Reject
                </div>

            </td>

        </tr>

    </table>

</div>

<!-- ======================================
     SIGNATURES
======================================= -->

<table class="signatures">

    <tr>

        <td>

            Prepared by:

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            Teacher

        </td>


        <td>

            Checked by:

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            Academic Coordinator

        </td>


        <td>

            Reviewed by:

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            Assistant Principal

        </td>


        <td>

            Attested by:

            <div class="signature-space"></div>

            <div class="signature-line"></div>

            School Principal

        </td>

    </tr>

</table>


</body>

</html>
