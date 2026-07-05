<!DOCTYPE html>
<html lang="ps" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        /* 
           ACADEMIC EXCELLENCE THEME 
           Palette: Oxford Navy (#002147), Slate Gray, and Off-White
        */
        @page { margin: 1cm; }
        
        body {
            font-family: 'bahij', 'Times New Roman', serif;
            color: #1a202c;
            background-color: #fff;
            margin: 0;
            line-height: 1.6;
        }

        /* Centered Institutional Header */
        /* Centered Institutional Header */
        .header-container {
            width: 100%;
            margin-bottom: 20px;
            /* Border removed from here to put it on the table cells instead */
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            /* This pushes all content to the bottom of the cell */
            vertical-align: bottom; 
            border: none;
            /* This creates the line you want the text to sit on */
            border-bottom: 2px solid #002147; 
            padding-bottom: 10px;
        }

        .header-center-text {
            text-align: center;
        }

        /* Metadata info */
        .header-meta {
            font-size: 14px;
            color: #000000;
            line-height: 1.4;
            /* Ensures space between the logo above and the text below */
            margin-top: 60px; 
        }

        .uni-name-ps {
            font-size: 24px;
            font-weight: bolder;
            color: #002147;
            margin: 0;
            line-height: 1.2;
        }

        .uni-name-en {
            font-size: 14px;
            font-weight: normal;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        /* Metadata info */
        .report-meta {
            width: 100%;
            margin-bottom: 25px;
            font-size: 12px;
            color: #000000;
            display: table;
        }

        .meta-left { display: table-cell; text-align: left; direction: ltr; }
        .meta-right { display: table-cell; text-align: right; }

        .report-title-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .report-title {
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            background: #1b365d;
            padding: 8px 25px;
            border-radius: 50px; /* Modern pill shape */
            text-transform: uppercase;
        }

        .metric-subtext {
            display: block;
            margin-top: 10px;
            font-size: 15px;
            color: #000000;
            font-style: italic;
        }

        /* The Academic Table Style */
        .academic-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            background-color: #ffffff;
            direction: ltr; /* Keeps data alignment professional */
        }

        /* Top and Bottom thick borders (Journal style) */
        .academic-table thead tr {
            border-top: 2px solid #002147;
            border-bottom: 1.5px solid #002147;
        }

        .academic-table th {
            padding: 15px 8px;
            background-color: #f1f5f9; /* Soft Slate */
            color: #002147;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }

        .academic-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        /* Highlighting the Category Column */
        .category-label {
            text-align: left !important;
            font-weight: bold;
            color: #002147 !important;
            background-color: #f8fafc;
            border-right: 1px solid #e2e8f0;
        }

        /* Emphasis on Totals/Citations */
        .emphasis-cell {
            font-weight: bold;
            background-color: #f1f5f9;
            text-align: center;
        }

        /* Footer Section */
        .footer {
            margin-top: 40px;
            text-align: center;
        }

        .footer-line {
            border-top: 1px solid #cbd5e0;
            padding-top: 10px;
            font-size: 11px;
            color: #a0aec0;
        }

        .signature-block {
            margin-top: 20px;
            float: left;
            text-align: center;
            border-top: 1px solid #002147;
            width: 200px;
            padding-top: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="header-container">
    <table class="header-table">
        <tr>
            <!-- Left Metadata (Date) -->
            <td style="width: 30%; text-align: right;">
                <img src="{{ public_path('images/KDRU_LOGO.png') }}" style="width: 100px; margin-bottom: 10px;">
                <div class="header-meta">
                    <strong>نیټه:</strong> {{ $date }}
                </div>
            </td>
            
            <!-- Centered Header Text -->
            <td style="width: 40%;" class="header-center-text">
                <div class="uni-name-ps">د افغانستان اسلامي امارت</div>
                <div class="uni-name-ps" style="font-size: 20px;">د لوړو زده کړو وزارت</div>
                <div class="uni-name-ps" style="font-size: 18px;">کندهار پوهنتون</div>
                <div class="uni-name-ps" style="font-size: 16px;">د علمی څیړنو معاونیت</div>
            </td>

            <!-- Right Metadata (Ref No) -->
            <td style="width: 30%; text-align: left;">
                <img src="{{ public_path('images/Vice_Logo.png') }}" style="width: 100px; margin-bottom: 10px;">
                <div class="header-meta">
                    <strong>REF NO:</strong> KU-RES-{{ date('Y') }}-{{ rand(100,999) }}
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="report-title-container">
    <div class="report-title">Academic Research Performance Matrix</div>
    <span class="metric-subtext">Based on specialized metric: <strong>{{ $metric }}</strong></span>
</div>

<table class="academic-table">
    <thead>
        <tr>
            <th style="width: 18%;">Category</th>
            <th>Total Pubs</th>
            <th>Jr. TA</th>
            <th>TA</th>
            <th>Sr. TA</th>
            <th>Asst. Prof</th>
            <th>Assoc. Prof</th>
            <th>Professor</th>
            <th class="emphasis-cell">Citations</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td class="category-label">{{ $row->label }}</td>
            @foreach($row->values as $index => $value)
                <td class="{{ $loop->last ? 'emphasis-cell' : '' }}" style="text-align: center;">
                    {{ number_format($value) }}
                </td>
            @endforeach
        </tr>
        @empty
        <tr>
            <td colspan="9" style="padding: 30px; text-align: center; color: #a0aec0;">
                No academic records found for the selected criteria.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <div class="signature-block">
        د علمی څیړنو مرستیال<br>
        <p style="font-size:11px; font-weight:700;"><strong>پوهنيار دوکتور رحمت الله پښتون</strong></p>
    </div>
    <div style="clear: both;"></div>
    <div class="footer-line" style="margin-top: 30px;">
        نوټ: دغه جدول په اتوماتیک ډول د ډیټابیس څخه د علمي احصایې په موخه ترتیب سوی دی.<br>
        This is an official research metric report generated by the Kandahar University Research Database.
    </div>
</div>

</body>
</html>