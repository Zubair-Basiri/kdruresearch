@extends('pdf.generic-analytics')

@section('content')
    <div dir="ltr">
        <!-- Page Title -->
        <h3 style="text-align:center; margin-top:0;">Research Forecasting</h3>
        <p style="text-align:center; color:#6b7280; font-size:14px; margin-bottom:5px;">
            Project future research output based on historical trends
        </p>

        <!-- Filter Summary -->
        @php
            $appliedFilters = array_filter($filters ?? [], function($val) { return !empty($val); });
        @endphp
        @if(!empty($appliedFilters))
            <div style="text-align:center; font-size:11px; color:#4a5568; margin-bottom:15px;">
                <strong>Applied Filters:</strong>
                @foreach($appliedFilters as $key => $value)
                    {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }} &nbsp;|&nbsp;
                @endforeach
            </div>
        @endif

        <!-- ===== KPIs ===== -->
        @if(!empty($summary))
            @php
                $kpiItems = [
                    ['label' => 'Current Output', 'value' => $summary['current_output'] ?? 0],
                    ['label' => 'Next Year Forecast', 'value' => $summary['next_year_forecast'] ?? 0],
                    [
                        'label' => 'Expected Growth',
                        'value' => (($summary['growth_rate'] ?? 0) >= 0 ? '+' : '') . ($summary['growth_rate'] ?? 0) . '%',
                        'color' => ($summary['growth_rate'] ?? 0) >= 0 ? '#16a34a' : '#dc2626'
                    ],
                    ['label' => 'Forecast Reliability', 'value' => $forecast['reliability'] ?? 'N/A'],
                ];
            @endphp
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <tr>
                    @foreach($kpiItems as $kpi)
                        <td style="width:25%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                            <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">{{ $kpi['label'] }}</div>
                            <div style="font-size:22px; font-weight:700; color:{{ $kpi['color'] ?? '#111827' }};">{{ $kpi['value'] }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif

        <!-- ===== Chart: Historical + Forecast Line ===== -->
        @if(!empty($historical) && !empty($forecast_values))
            @php
                // Combine historical and forecast data for chart
                $chartData = [];
                $maxVal = 0;
                foreach ($historical as $year => $val) {
                    $chartData[] = ['year' => $year, 'historical' => $val, 'forecast' => null];
                    if ($val > $maxVal) $maxVal = $val;
                }
                // Find next year after last historical year
                $lastHistoricalYear = max(array_keys($historical));
                $forecastStartYear = $lastHistoricalYear + 1;
                foreach ($forecast_values as $i => $f) {
                    $year = $forecastStartYear + $i;
                    $chartData[] = ['year' => $year, 'historical' => null, 'forecast' => $f['value']];
                    if ($f['value'] > $maxVal) $maxVal = $f['value'];
                }
                $maxVal = $maxVal + ($maxVal * 0.1); // add 10% padding
                $width = 700;
                $height = 250;
                $padding = 60;
                $chartW = $width - 2 * $padding;
                $chartH = $height - 2 * $padding;
                $count = count($chartData);
                $xStep = $count > 1 ? $chartW / ($count - 1) : $chartW;
                $scaleX = function($i) use ($padding, $xStep) { return $padding + $i * $xStep; };
                $scaleY = function($val) use ($padding, $chartH, $maxVal) {
                    return $padding + $chartH - ($val / $maxVal) * $chartH;
                };
                // Build points for historical (blue) and forecast (orange) lines
                $histPoints = [];
                $forePoints = [];
                $lastHistYear = $lastHistoricalYear;
                foreach ($chartData as $i => $d) {
                    $x = $scaleX($i);
                    if ($d['historical'] !== null) {
                        $y = $scaleY($d['historical']);
                        $histPoints[] = "$x,$y";
                    } else {
                        // Forecast point
                        $y = $scaleY($d['forecast']);
                        $forePoints[] = "$x,$y";
                    }
                }
                // We need separate lines: historical from first to last historical, forecast from last historical to end
                // For simplicity, we'll build two sets of points: historical (all hist points) and forecast (all forecast points)
                // But the forecast line should start from the last historical point.
                // So we'll reconstruct:
                $histLine = [];
                $foreLine = [];
                foreach ($chartData as $i => $d) {
                    if ($d['historical'] !== null) {
                        $histLine[] = $scaleX($i) . ',' . $scaleY($d['historical']);
                    }
                    // For forecast, we include the last historical point and all forecast points
                    if ($d['historical'] === null || $i == count($chartData)-1) {
                        // But we need to start from the last historical point
                        // We'll handle separately: build forecast points starting from last historical
                    }
                }
                // Simpler: build forecast line with last historical point + forecast points
                $foreLine = [];
                $lastHistIdx = null;
                foreach ($chartData as $i => $d) {
                    if ($d['historical'] !== null) {
                        $lastHistIdx = $i;
                    }
                }
                if ($lastHistIdx !== null) {
                    // Add last historical point
                    $lastHistVal = $chartData[$lastHistIdx]['historical'];
                    $foreLine[] = $scaleX($lastHistIdx) . ',' . $scaleY($lastHistVal);
                    // Add forecast points after last historical
                    for ($i = $lastHistIdx + 1; $i < $count; $i++) {
                        $foreLine[] = $scaleX($i) . ',' . $scaleY($chartData[$i]['forecast']);
                    }
                }
                // Historical line: from first to last historical
                $histLine = [];
                for ($i = 0; $i <= $lastHistIdx; $i++) {
                    $histLine[] = $scaleX($i) . ',' . $scaleY($chartData[$i]['historical']);
                }
                $histLineStr = implode(' ', $histLine);
                $foreLineStr = implode(' ', $foreLine);
            @endphp
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Publication Forecast</h4>
                <div dir="ltr">
                    <svg viewBox="0 0 {{ $width }} {{ $height }}" style="width:100%; height:auto;">
                        <!-- Grid lines -->
                        @for($i = 0; $i <= 4; $i++)
                            @php $y = $padding + $chartH - ($i/4)*$chartH; @endphp
                            <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $padding + $chartW }}" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="0.5"/>
                            <text x="{{ $padding - 10 }}" y="{{ $y + 4 }}" font-size="9" fill="#6b7280" text-anchor="end">{{ round($maxVal * (1 - $i/4)) }}</text>
                        @endfor
                        <!-- X-axis labels -->
                        @foreach($chartData as $i => $d)
                            <text x="{{ $scaleX($i) }}" y="{{ $padding + $chartH + 18 }}" font-size="9" fill="#6b7280" text-anchor="middle">{{ $d['year'] }}</text>
                        @endforeach
                        <!-- Historical line (blue) -->
                        @if(!empty($histLineStr))
                            <polyline points="{{ $histLineStr }}" fill="none" stroke="#3b82f6" stroke-width="2.5"/>
                            <!-- Markers for historical -->
                            @for($i = 0; $i <= $lastHistIdx; $i++)
                                @php
                                    $cx = $scaleX($i);
                                    $cy = $scaleY($chartData[$i]['historical']);
                                @endphp
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3" fill="#3b82f6"/>
                            @endfor
                        @endif
                        <!-- Forecast line (orange) -->
                        @if(!empty($foreLineStr))
                            <polyline points="{{ $foreLineStr }}" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-dasharray="5,5"/>
                            <!-- Markers for forecast -->
                            @for($i = $lastHistIdx + 1; $i < $count; $i++)
                                @php
                                    $cx = $scaleX($i);
                                    $cy = $scaleY($chartData[$i]['forecast']);
                                @endphp
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3" fill="#f59e0b"/>
                            @endfor
                        @endif
                        <!-- Axes -->
                        <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                        <line x1="{{ $padding }}" y1="{{ $padding + $chartH }}" x2="{{ $padding + $chartW }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                        <!-- Legend -->
                        <rect x="{{ $padding + $chartW - 120 }}" y="{{ $padding + 10 }}" width="12" height="12" fill="#3b82f6" rx="2"/>
                        <text x="{{ $padding + $chartW - 105 }}" y="{{ $padding + 20 }}" font-size="9" fill="#1e293b">Historical</text>
                        <rect x="{{ $padding + $chartW - 120 }}" y="{{ $padding + 30 }}" width="12" height="12" fill="#f59e0b" rx="2"/>
                        <text x="{{ $padding + $chartW - 105 }}" y="{{ $padding + 40 }}" font-size="9" fill="#1e293b">Forecast</text>
                    </svg>
                </div>
            </div>
        @endif

        <!-- ===== Forecast Table ===== -->
        @php
            $tableRows = [];
            foreach ($historical as $year => $val) {
                $tableRows[] = ['year' => $year, 'type' => 'Historical', 'value' => $val, 'lower' => null, 'upper' => null];
            }
            $lastHistoricalYear = max(array_keys($historical));
            $forecastStartYear = $lastHistoricalYear + 1;
            foreach ($forecast_values as $i => $f) {
                $year = $forecastStartYear + $i;
                $lower = $intervals[$i]['lower'] ?? null;
                $upper = $intervals[$i]['upper'] ?? null;
                $tableRows[] = [
                    'year' => $year,
                    'type' => 'Forecast',
                    'value' => $f['value'],
                    'lower' => $lower,
                    'upper' => $upper,
                ];
            }
        @endphp
        @if(!empty($tableRows))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Forecast Table</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Year</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Type</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Value</th>
                            @if(!empty($intervals))
                                <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Lower Bound</th>
                                <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Upper Bound</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tableRows as $row)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $row['year'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $row['type'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $row['value'] }}</td>
                                @if(!empty($intervals))
                                    <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $row['lower'] ?? '-' }}</td>
                                    <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $row['upper'] ?? '-' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Faculty Forecast ===== -->
        @if(!empty($faculties))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Faculty Forecast</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Faculty</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Current</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Next Year</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Growth</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faculties as $f)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $f['faculty'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $f['current'] ?? 0 }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $f['next_year'] ?? 0 }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">
                                    @php
                                        $growth = $f['growth'] ?? 0;
                                        $color = $growth >= 0 ? '#16a34a' : '#dc2626';
                                    @endphp
                                    <span style="color:{{ $color }}; font-weight:bold;">
                                        {{ $growth >= 0 ? '+' : '' }}{{ $growth }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Research Area Forecast ===== -->
        @if(!empty($research_areas))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Research Area Forecast</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Research Area</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Current</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Next Year</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Growth</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($research_areas as $ra)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $ra['area'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $ra['current'] ?? 0 }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $ra['next_year'] ?? 0 }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">
                                    @php
                                        $growth = $ra['growth'] ?? 0;
                                        $color = $growth >= 0 ? '#16a34a' : '#dc2626';
                                    @endphp
                                    <span style="color:{{ $color }}; font-weight:bold;">
                                        {{ $growth >= 0 ? '+' : '' }}{{ $growth }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Key Findings ===== -->
        @if(!empty($key_findings))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Key Findings</h4>
                <ul style="list-style:none; padding:0;">
                    @foreach($key_findings as $finding)
                        <li style="padding:6px 0; border-bottom:1px solid #e2e8f0; font-size:10px;">💡 {{ $finding }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ===== Methodology & Limitations ===== -->
        @if(!empty($methodology))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Methodology & Limitations</h4>
                <div style="font-size:9px; color:#4a5568;">
                    <p><strong>Method:</strong> {{ $methodology['method'] ?? 'N/A' }}</p>
                    <p><strong>Forecast Horizon:</strong> {{ $methodology['forecast_horizon'] ?? 'N/A' }}</p>
                    <p><strong>Algorithm:</strong> {{ $methodology['algorithm'] ?? 'N/A' }}</p>
                    <p><strong>Reliability:</strong> Based on R-squared and historical data length.</p>
                    <p><strong>Limitations:</strong> {{ $methodology['limitations'] ?? 'Forecasts are estimates based on historical patterns.' }}</p>
                </div>
                <div style="font-size:9px; color:#4a5568; margin-top:10px; padding:10px; background:#f8fafc; border-radius:8px;">
                    <i class="bi bi-info-circle"></i> 
                    Forecasts are estimates based on historical research activity. They are not guaranteed outcomes. 
                    Actual future results may differ because of funding, policy, staffing, collaboration, publication delays, 
                    research priorities, and other external factors.
                </div>
            </div>
        @endif
    </div>
@endsection