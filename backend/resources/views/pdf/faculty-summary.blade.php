<!DOCTYPE html>
<html lang="ps" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { 
            margin: 1cm; 
        }
        body {
            font-family: 'bahij_nazanin', 'dejavusans', 'Times New Roman', serif;
            color: #1e293b;
            background: #fff;
            margin: 0;
            line-height: 1.45;
        }
        /* Header Table */
        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-table td {
            border-bottom: 1px solid #2d5a27; /* dark green for border */
            padding-bottom: 6px;
            vertical-align: bottom;
        }
        .header-meta {
            font-size: 15px;
            margin-top: 20px;
            color: #000000;
            font-weight: 500;
        }
        .uni-name-ps {
            font-size: 24px;
            font-weight: bolder;
            color: #2d5a27;
            margin: 0;
            line-height: 1.35;
            letter-spacing: -0.3px;
        }
        /* Title area */
        .report-title-container {
            text-align: center;
            margin: 15px 0 15px;
            width: 50%;
            margin-left: auto;
            margin-right: auto;
        }
        .report-title {
            background: #d9f0c5; /* light lime background */
            color: #1e4620;
            padding: 8px 28px;
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            border-radius: 50px; /* Modern pill shape */
            text-transform: uppercase;
        }
        .metric-subtext {
            font-size: 15px;
            color: #000000;
            margin-top: 8px;
            font-style: normal;
        }
        /* Main Table – green & lemon accents */
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            background: #fff;
            direction: ltr;
        }
        .academic-table th,
        .academic-table td {
            border: 0.8px solid #cfe3c0;
            padding: 7px 3px;
            vertical-align: middle;
        }
        .academic-table th {
            background: #e8f5e1; /* very light green */
            color: #1e4620;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        .academic-table td {
            color: #1e293b;
        }
        .category-label {
            text-align: left;
            font-weight: 700;
            background: #fefce8; /* lemon tint */
            border-left: 3px solid #c7e9b0;
            color: #2d5a27;
            font-size: 10px;
        }
        /* Alternating row colors: light green / lemon */
        .academic-table tbody tr:nth-child(even) td {
            background-color: #fefce8; /* lemon */
        }
        .academic-table tbody tr:nth-child(odd) td {
            background-color: #f4f9f0; /* light green */
        }
        /* Override category label row colors to keep consistent */
        .academic-table tbody tr:nth-child(even) .category-label {
            background-color: #fefce8;
        }
        .academic-table tbody tr:nth-child(odd) .category-label {
            background-color: #f4f9f0;
        }
        /* Last column (Total Researchers) extra highlight */
        .academic-table td:last-child {
            background: #eef5e8 !important;
            font-weight: 600;
            border-left: 1px solid #cfe3c0;
        }
        /* Footer */
        .footer {
            margin-top: 40px;
        }
        .signature-block {
            float: left;
            width: 200px;
            border-top: 1px solid #2d5a27;
            padding-top: 12px;
            font-size: 12px;
            text-align: center;
            color: #2d5a27;
        }
        .footer-line {
            border-top: 1px solid #cfe3c0;
            margin-top: 25px;
            padding-top: 10px;
            font-size: 11px;
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
        <td style="width:40%; text-align:center; vertical-align:bottom;">
            <div class="uni-name-ps">د افغانستان اسلامي امارت</div>
            <div style="font-size: 20px; color: #4a7c3b; font-weight: bold;">د لوړو زده کړو وزارت</div>
            <div style="font-size: 18px; color: #4a7c3b; font-weight: bold;">کندهار پوهنتون</div>
            <div style="font-size: 16px; color: #4a7c3b; font-weight: bold;">د علمی څیړنو معاونیت</div>
        </td>
        <td style="width:30%; text-align:left;">
            <img src="{{ public_path('images/Vice_Logo.png') }}" style="width: 100px; margin-bottom: 10px;">
            <div class="header-meta"><strong>REF NO:</strong> KU-RES-{{ date('Y') }}-{{ rand(100,999) }}</div>
        </td>
    </tr>
</table>

<div class="report-title-container">
    <div class="report-title">Faculty Research Performance Summary</div>
    <div class="metric-subtext">Based on specialized metric: <strong>{{ $metricTitle }}</strong></div>
</div>

<colgroup>
    <col style="width:15%">
    @foreach($faculties as $fac)
        <col style="width:{{ 85 / count($faculties) }}%">
    @endforeach
</colgroup>
<table class="academic-table">
    <tbody>
        <tr>
            <th style="background:#e8f5e1; border:0.8px solid #cfe3c0;">Category / Faculty</th>
            @foreach($faculties as $fac)
                <th style="background:#e8f5e1; border:0.8px solid #cfe3c0;">{{ $fac }}</th>
            @endforeach
        </tr>
        @forelse($rows as $row)
        <tr>
            <td class="category-label">{{ $row['label'] }}</td>
            @foreach($row['values'] as $value)
                <td style="text-align: center;">{{ number_format($value) }}</td>
            @endforeach
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($faculties)+1 }}" style="padding: 30px; text-align: center; color: #a0aec0;">No data available</td>
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