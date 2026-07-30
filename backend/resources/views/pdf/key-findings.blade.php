<!DOCTYPE html>
<html lang="ps" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm; }
        body {
            font-family: 'bahij_nazanin', 'dejavusans', 'Times New Roman', serif;
            color: #1F4E79;
            background-color: #fff;
            margin: 0;
            line-height: 1.6;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: bottom;
            border-bottom: 2px solid #002147;
            padding-bottom: 10px;
        }
        .header-center-text {
            text-align: center;
        }
        .header-meta {
            font-size: 12px;
            color: #1F4E79;
            margin-top: 50px;
        }
        .uni-name-ps {
            font-size: 22px;
            font-weight: bold;
            color: #1F4E79;
            margin: 0;
            line-height: 1.3;
        }
        .uni-sub {
            font-size: 18px;
            color: #1F4E79;
        }
        .report-title-container {
            text-align: center;
            margin: 20px 0;
        }
        .report-title {
            background: #1F4E79;
            color: white;
            padding: 8px 25px;
            display: inline-block;
            font-size: 18px;
            border-radius: 50px;
            font-weight: bold;
        }
        .metric-subtext {
            margin-top: 8px;
            font-size: 12px;
            color: #4a5568;
        }
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            direction: ltr;
        }
        .academic-table th {
            background: #f1f5f9;
            color: #002147;
            padding: 8px 4px;
            border: 0.5px solid #cbd5e0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            text-align: center;
        }
        .academic-table td {
            padding: 6px 3px;
            border: 0.5px solid #e2e8f0;
            text-align: center;
            color: #2d3748;
        }
        .footer {
            margin-top: 40px;
        }
        .signature-block {
            float: left;
            width: 220px;
            border-top: 1px solid #002147;
            padding-top: 15px;
            font-size: 10px;
            text-align: center;
        }
        .footer-line {
            border-top: 1px solid #cbd5e0;
            margin-top: 20px;
            padding-top: 8px;
            font-size: 9px;
            color: #718096;
            text-align: center;
            clear: both;
        }
    </style>
</head>
<body>

<table class="header-table" style="width:100%;">
    <tr>
        <td style="width:30%; text-align:right;">
            <img src="{{ public_path('images/KDRU_LOGO.png') }}" style="width:85px; margin-bottom:10px;">
            <div class="header-meta"><strong>نیټه:</strong> {{ $date }}</div>
        </td>
        <td style="width:40%; text-align:center;">
            <div class="uni-name-ps">د افغانستان اسلامی امارت</div>
            <div class="uni-name-ps uni-sub">د لوړو زده کړو وزارت</div>
            <div class="uni-name-ps uni-sub">کندهار پوهنتون</div>
            <div class="uni-name-ps uni-sub" style="font-size:14px;">د علمی څیړنو معاونیت</div>
        </td>
        <td style="width:30%; text-align:left;">
            <img src="{{ public_path('images/Vice_Logo.png') }}" style="width:85px; margin-bottom:10px;">
            <div class="header-meta"><strong>REF NO:</strong> KU-RES-{{ date('Y') }}-{{ rand(100,999) }}</div>
        </td>
    </tr>
</table>

<div class="report-title-container">
    <div class="report-title">Key Research Findings</div>
    <div class="metric-subtext">
        Based on applied filters and selected columns
    </div>
</div>

<table class="academic-table">
    <thead>
        <tr>
            @foreach($columns as $col)
                <th>{{ $col }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
        @empty
        <tr><td colspan="{{ count($columns) }}" style="text-align:center;">No data found</td></tr>
        @endforelse
    </tbody>
</table>

    <!-- ===== BREAKDOWN SECTIONS ===== -->
<!-- ===== BREAKDOWN SECTIONS ===== -->

<!-- Helper to render a breakdown as a grid of small boxes -->
@php
    function renderBreakdown($items, $labelKey, $title, $recordCount)
    {
        if (empty($items)) return '';

        $html = '<div style="margin-top: 20px; padding: 12px 16px; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden;">';
        $html .= '<h4 style="font-size: 13px; font-weight: 700; color: #1F4E79; margin: 0 0 10px 0; border-left: 3px solid #1F4E79; padding-left: 10px; text-align: left;">' . $title . '</h4>';
        $html .= '<div style="overflow: hidden;">'; // clearfix

        $count = 0;
        $totalItems = count($items);
        foreach ($items as $item) {
            $label = $item[$labelKey] ?? '';
            $countVal = $item['count'] ?? 0;
            $total = $item['total'] ?? $recordCount;

            // Remove right margin on the last item of each row (every 5th item or the very last)
            $marginRight = ($count % 5 == 4 || $count == $totalItems - 1) ? '0' : '2%';

            $html .= '<div style="float: left; width: 18%; margin-right: ' . $marginRight . '; margin-bottom: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 6px; text-align: center;">';
            $html .= '<div style="font-size: 8px; text-transform: uppercase; color: #4a5568; letter-spacing: 0.3px;">' . htmlspecialchars($label) . '</div>';
            $html .= '<div style="font-size: 16px; font-weight: 700; color: #1F4E79; margin: 2px 0;">' . $countVal . '</div>';
            $html .= '<div style="font-size: 8px; color: #718096;">/ ' . $total . ' records</div>';
            $html .= '</div>';

            $count++;
        }

        $html .= '</div>'; // end clearfix
        $html .= '</div>'; // end card
        return $html;
    }
@endphp

{!! renderBreakdown($columnSummaries, 'label', 'Unique Values per Column', $totalRecords) !!}
{!! renderBreakdown($gradeBreakdown, 'grade', 'Grade Breakdown', $totalRecords) !!}
{!! renderBreakdown($educationBreakdown, 'education', 'Education Breakdown', $totalRecords) !!}
{!! renderBreakdown($publicationTypeBreakdown, 'type', 'Publication Type Breakdown', $totalRecords) !!}
{!! renderBreakdown($indexBreakdown, 'index', 'Index Breakdown', $totalRecords) !!}
{!! renderBreakdown($languageBreakdown, 'language', 'Language Breakdown', $totalRecords) !!}
{!! renderBreakdown($collaborationBreakdown, 'collaboration', 'Collaboration Breakdown', $totalRecords) !!}

<div class="footer">
    <div class="signature-block">
        د علمی څیړنو مرستیال<br>
        <p style="font-size:11px; font-weight:700;"><strong>پوهنيار دوکتور رحمت الله پښتون</strong></p>
    </div>
    <div style="clear:both;"></div>
    <div class="footer-line">
        نوټ: دغه جدول په اتوماتیک ډول د ډیټابیس څخه د علمي احصایې په موخه ترتیب سوی دی.<br>
        This is an official research metric report generated by Kandahar University Research Database.
    </div>
</div>

</body>
</html>