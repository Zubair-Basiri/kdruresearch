<!DOCTYPE html>
<html lang="ps" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm; }
        body {
            font-family: 'bahij_nazanin', 'dejavusans', 'Times New Roman', serif;
            color: #1e293b;
            background: #fff;
            margin: 0;
            line-height: 1.45;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-table td {
            border-bottom: 1px solid #6b21a5; /* purple border */
            padding-bottom: 10px;
            vertical-align: bottom;
        }
        .header-meta {
            font-size: 15px;
            margin-top: 40px;
            color: #000000;
            font-weight: 500;
        }
        .uni-name-ps {
            font-size: 24px;
            font-weight: bolder;
            color: #6b21a5;
            margin-top: 0;
            line-height: 1.35;
        }
        .report-title-container {
            text-align: center;
            margin: 20px 0 20px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .report-title {
            background: #ede9fe; /* light purple */
            color: #4c1d95;
            padding: 8px 28px;
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
            border-radius: 4px;
            border-radius: 80px; /* Modern pill shape */
            text-transform: uppercase;
        }
        .metric-subtext {
            font-size: 15px;
            color: #6b21a5;
            margin-top: 8px;
        }
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            direction: ltr;
        }
        .academic-table th,
        .academic-table td {
            border: 0.8px solid #c7d2fe; /* light blue border */
            padding: 6px 3px;
            vertical-align: middle;
        }
        .academic-table th {
            background: #e0e7ff; /* light blue */
            color: #3730a3;
            font-weight: bold;
        }
        .category-label {
            text-align: left !important;
            font-weight: bold;
            background: #f5f3ff; /* very light purple */
            border-left: 3px solid #a78bfa; /* purple accent */
        }
        .academic-table tbody tr:nth-child(even) td {
            background-color: #f5f3ff;
        }
        .academic-table tbody tr:nth-child(odd) td {
            background-color: #eff6ff;
        }
        .total-cell, .avg-cell, .citation-cell {
            font-weight: bold;
            background: #ede9fe !important;
        }
        .footer {
            margin-top: 40px;
        }
        .signature-block {
            float: left;
            width: 200px;
            border-top: 1px solid #6b21a5;
            padding-top: 15px;
            font-size: 10px;
            text-align: center;
        }
        .footer-line {
            border-top: 1px solid #c7d2fe;
            margin-top: 10px;
            padding-top: 10px;
            font-size: 10px;
            color: #6c7a91;
            text-align: center;
            clear: both;
        }
    </style>
</head>
<body>

<table class="header-table" style="width:100%;">
    <tr>
        <td style="width:30%; text-align:right;">
            <img src="{{ public_path('images/KDRU_LOGO.png') }}" style="width: 100px; margin-bottom: 10px;">
            <div class="header-meta"><strong>نیټه:</strong> {{ $date }}</div>
        </td>
        <td style="width:40%; text-align:center;">
            <div class="uni-name-ps">د افغانستان اسلامي امارت</div>
            <div style="color: #8b5cf6; font-size: 20px; font-weight: bold;">د لوړو زده کړو وزارت</div>
            <div style="color: #8b5cf6; font-size: 18px; font-weight: bold;">کندهار پوهنتون</div>
            <div style="color: #8b5cf6; font-size: 16px; font-weight: bold;">د علمی څیړنو معاونیت</div>
        </td>
        <td style="width:30%; text-align:left;">
            <img src="{{ public_path('images/Vice_Logo.png') }}" style="width: 100px; margin-bottom: 10px;">
            <div class="header-meta"><strong>REF NO:</strong> KU-RES-{{ date('Y') }}-{{ rand(100,999) }}</div>
        </td>
    </tr>
</table>

<div class="report-title-container">
    <div class="report-title">Year Based Performance Summary</div>
    <div class="metric-subtext">Based on specialized metric: <strong>{{ $metricTitle }}</strong></div>
</div>

<colgroup>
    <col style="width:15%">
    @foreach($years as $year)
        <col style="width:{{ 70 / count($years) }}%">
    @endforeach
    @if(!$isCategory)
        <col style="width:8%"><col style="width:8%">
    @else
        <col style="width:8%">
    @endif
</colgroup>
<table class="academic-table">
    <thead>
        <tr>
            <th>Category</th>
            @foreach($years as $year)
                <th>{{ $year }}</th>
            @endforeach
            @if(!$isCategory)
                <th>Total</th>
                <th>Total Citations</th>
            @else
                <th>Average (%)</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td class="category-label">{{ $row['label'] }}</td>
            @foreach($years as $year)
                <td style="text-align: center;">{{ number_format($row['values'][$year] ?? 0) }}</td>
            @endforeach
            @if(!$isCategory)
                <td class="total-cell" style="text-align: center;">{{ number_format($row['total'] ?? 0) }}</td>
                <td class="citation-cell" style="text-align: center;">{{ number_format($row['totalCitation'] ?? 0) }}</td>
            @else
                <td class="avg-cell" style="text-align: center;">{{ number_format($row['average'] ?? 0, 1) }}%</td>
            @endif
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($years) + ($isCategory ? 2 : 3) }}" style="text-align:center;">No data available</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <div class="signature-block">
        د علمی څیړنو مرستیال<br>
        <p style="font-size:11px; font-weight:700;"><strong>پوهنيار دوکتور رحمت الله پښتون</strong></p>
    </div>
    <div class="footer-line">
        نوټ: دغه جدول په اتوماتیک ډول د ډیټابیس څخه د علمي احصایې په موخه ترتیب سوی دی.<br>
        This is an official research metric report generated by Kandahar University Research Database.
    </div>
</div>

</body>
</html>