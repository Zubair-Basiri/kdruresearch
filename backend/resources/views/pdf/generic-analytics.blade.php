<!DOCTYPE html>
<html lang="ps" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm; }
        body {
            font-family: 'bahij_nazanin', 'dejavusans', 'Times New Roman', serif;
            color: #1F4E79;
            background: #fff;
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
        .header-meta {
            font-size: 12px;
            color: #1F4E79;
            margin-top: 30px;
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
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            background: #1F4E79;
            color: white;
            padding: 8px 25px;
            display: inline-block;
            border-radius: 50px;
        }
        .filter-summary {
            font-size: 11px;
            color: #4a5568;
            margin-bottom: 5px;
            text-align: center;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        .kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }
        .kpi-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #4a5568;
        }
        .kpi-value {
            font-size: 18px;
            font-weight: 700;
            color: #1F4E79;
        }
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            direction: ltr;
            margin-bottom: 15px;
        }
        .academic-table th {
            background: #f1f5f9;
            color: #002147;
            padding: 8px 4px;
            border: 0.5px solid #cbd5e0;
            font-weight: bold;
            text-align: center;
        }
        .academic-table td {
            padding: 6px 3px;
            border: 0.5px solid #e2e8f0;
            text-align: center;
            color: #2d3748;
        }
        .findings-list {
            list-style: none;
            padding: 0;
        }
        .findings-list li {
            padding: 4px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .methodology {
            font-size: 9px;
            color: #4a5568;
            margin-top: 20px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #cbd5e0;
            padding-top: 8px;
            font-size: 8px;
            color: #718096;
            text-align: center;
        }
        .signature-block {
            float: left;
            width: 220px;
            border-top: 1px solid #002147;
            padding-top: 15px;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Header -->
<table class="header-table">
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

<!-- Title -->
<div style="text-align:center; margin: 5px 0;">
    <div class="report-title">{{ $title ?? 'Analytics Report' }}</div>
</div>

<!-- Filters -->
@if(!empty($filters))
<div class="filter-summary">
    <strong>Filters applied:</strong>
    @foreach($filters as $key => $value)
        @if($value)
            {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }} &nbsp;|&nbsp;
        @endif
    @endforeach
</div>
@endif

<!-- ====== CONTENT (overridden by each page) ====== -->
@yield('content')

<!-- Footer -->
<div class="footer">
    <div class="signature-block">
        د علمی څیړنو مرستیال<br>
        <p style="font-size:11px; font-weight:700;"><strong>پوهنيار دوکتور رحمت الله پښتون</strong></p>
    </div>
    <div style="clear:both;"></div>
    <div style="margin-top: 20px; border-top:1px solid #cbd5e0; padding-top:8px;">
        نوټ: دغه راپور په اتوماتیک ډول د ډیټابیس څخه ترتیب سوی دی.<br>
        This is an official analytics report generated by Kandahar University Research Database.
    </div>
</div>

</body>
</html>