<!DOCTYPE html>
<html lang="ps" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm;}
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
            border-bottom: 2px solid #0e7c7c; /* teal border */
            padding-bottom: 8px;
            vertical-align: bottom;
        }
        .header-meta {
            font-size: 14px;
            margin-top: 40px;
            color: #0e7c7c;
            font-weight: 500;
        }
        .uni-name-ps {
            font-size: 24px;
            font-weight: bold;
            color: #0e7c7c;
            margin: 0;
            line-height: 1.35;
        }
        .uni-sub { font-size: 15px; color: #2c9e9e; }
        .report-title-container {
            text-align: center;
            margin: 15px 0 15px;
        }
        .report-title {
            background: #d4f1f9; /* light azure */
            color: #0e4f4f;
            padding: 6px 24px;
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
            border-radius: 30px;
        }
        .metric-subtext {
            font-size: 11px;
            color: #0e7c7c;
            margin-top: 6px;
        }
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            direction: ltr;
        }
        .academic-table th,
        .academic-table td {
            border: 0.6px solid #bfe4e4;
            padding: 5px 2px;
            vertical-align: middle;
            text-align: center;
        }
        .academic-table th {
            background: #e2f3f7; /* light teal/azure */
            color: #0e4f4f;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .academic-table td {
            color: #1e293b;
        }
        .academic-table tbody tr:nth-child(even) td {
            background-color: #f0fbfc;
        }
        .academic-table tbody tr:nth-child(odd) td {
            background-color: #f8fefe;
        }
        .left-align-data {
            text-align: left !important;
        }
        .footer {
            margin-top: 40px;
        }
        .signature-block {
            float: left;
            width: 190px;
            border-top: 1px solid #0e7c7c;
            padding-top: 15px;
            font-size: 9px;
            text-align: center;
        }
        .footer-line {
            border-top: 1px solid #bfe4e4;
            margin-top: 20px;
            padding-top: 8px;
            font-size: 8px;
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
            <img src="{{ public_path('images/KDRU_LOGO.png') }}" style="width:100px; margin-bottom:10px;">
            <div class="header-meta"><strong>نیټه:</strong> {{ $date }}</div>
        </td>
        <td style="width:40%; text-align:center;">
            <div class="uni-name-ps">د افغانستان اسلامي امارت</div>
            <div class="uni-name-ps uni-sub">د لوړو زده کړو وزارت</div>
            <div class="uni-name-ps uni-sub">کندهار پوهنتون</div>
            <div class="uni-name-ps uni-sub" style="font-size:12px;">د علمی څیړنو معاونیت</div>
        </td>
        <td style="width:30%; text-align:left;">
            <img src="{{ public_path('images/Vice_Logo.png') }}" style="width:100px; margin-bottom:10px;">
            <div class="header-meta"><strong>REF NO:</strong> KU-RES-{{ date('Y') }}-{{ rand(100,999) }}</div>
        </td>
    </tr>
</table>

<div class="report-title-container">
    <div class="report-title">
        @if($topField === 'All') Top Researchers – Full Report
        @else Top Researchers by “{{ $topField }}” @endif
    </div>
    @if($topField !== 'All')
        <div class="metric-subtext">Showing {{ count($columns) }} selected columns | Search: “{{ $search }}”</div>
    @endif
</div>

<colgroup>
    <col style="width:auto">
    @foreach($columns as $col)
        @if($loop->index == 0) <col style="width:12%"> @endif
        @if(strlen($col) > 20) <col style="width:6%"> @endif
    @endforeach
</colgroup>
<table class="academic-table">
    <tbody>
        <tr>
            @foreach($columns as $col)
                <th>{{ $col }}</th>
            @endforeach
        </tr>
        @forelse($rows as $row)
        <tr>
            @foreach($columns as $col)
                <td class="{{ in_array($col, ['Researcher Name','Faculty','Department']) ? 'left-align-data' : '' }}">
                    {{ $row[$col] }}
                </td>
            @endforeach
        </tr>
        @empty
        <tr><td colspan="{{ count($columns) }}" style="text-align:center;">No researchers found</td></tr>
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