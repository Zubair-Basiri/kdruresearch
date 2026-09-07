@extends('pdf.generic-analytics')

@section('content')
    <div dir="ltr">
        <!-- Page Title -->
        <h3 style="text-align:center; margin-top:0;">Research Area Analytics</h3>
        <p style="text-align:center; color:#6b7280; font-size:14px; margin-bottom:10px;">
            Analyze research areas, output, impact, and trends
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
        @if(!empty($kpis))
            @php
                $kpiItems = [
                    ['label' => 'Total Research Areas', 'value' => $kpis['total_areas'] ?? 0],
                    ['label' => 'Publications', 'value' => $kpis['total_publications'] ?? 0],
                    ['label' => 'Researchers', 'value' => $kpis['total_researchers'] ?? 0],
                    ['label' => 'Citations', 'value' => number_format($kpis['total_citations'] ?? 0)],
                    ['label' => 'Avg Citations', 'value' => $kpis['avg_citations'] ?? 0],
                    ['label' => 'Q1 Publications', 'value' => $kpis['q1_publications'] ?? 0],
                    ['label' => 'Cited %', 'value' => ($kpis['cited_percent'] ?? 0) . '%'],
                ];
            @endphp
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <tr>
                    @foreach($kpiItems as $kpi)
                        <td style="width:14.28%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                            <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">{{ $kpi['label'] }}</div>
                            <div style="font-size:22px; font-weight:700; color:#111827;">{{ $kpi['value'] }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif

        <!-- ===== Research Area Overview Table ===== -->
        @if(!empty($area_overview))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Research Area Overview</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0; text-align:left;">Research Area</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Publications</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Researchers</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Citations</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Avg Citations</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Q1</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Q1 %</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Share %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($area_overview as $area)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $area['area'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['publications'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['researchers'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['citations'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['avg_citations'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['q1'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['q1_percent'] }}%</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['share_percent'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Distribution (Pie approximation as horizontal bar) ===== -->
        @if(!empty($distribution))
            @php
                $maxDist = max(array_column($distribution, 'publications')) ?: 1;
            @endphp
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Research Area Distribution</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:6px 4px; border:0.5px solid #cbd5e0; text-align:left;">Area</th>
                            <th style="padding:6px 4px; border:0.5px solid #cbd5e0; text-align:center;">Publications</th>
                            <th style="padding:6px 4px; border:0.5px solid #cbd5e0; text-align:center;">%</th>
                            <th style="padding:6px 4px; border:0.5px solid #cbd5e0; text-align:center;">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($distribution as $item)
                            <tr>
                                <td style="padding:4px 6px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $item['area'] }}</td>
                                <td style="padding:4px 6px; border:0.5px solid #e2e8f0; text-align:center;">{{ $item['publications'] }}</td>
                                <td style="padding:4px 6px; border:0.5px solid #e2e8f0; text-align:center;">{{ $item['percentage'] }}%</td>
                                <td style="padding:4px 6px; border:0.5px solid #e2e8f0;">
                                    <div style="height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden;">
                                        <div style="width:{{ ($item['publications'] / $maxDist) * 100 }}%; height:10px; background:#3b82f6; border-radius:5px;"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Top Areas by Publications (Horizontal bar chart) ===== -->
        @if(!empty($top_by_publications))
            @php
                $maxPub = max(array_column($top_by_publications, 'value')) ?: 1;
            @endphp
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Top Research Areas by Publications</h4>
                @foreach($top_by_publications as $item)
                    @php
                        $width = ($item['value'] / $maxPub) * 100;
                    @endphp
                    <div style="display:flex; align-items:center; margin-bottom:4px; font-size:9px;">
                        <span style="width:180px; text-align:right; padding-right:8px; font-weight:bold;">{{ $item['area'] }}</span>
                        <div style="flex:1; height:14px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
                            <div style="width:{{ $width }}%; height:14px; background:#8b5cf6; border-radius:4px;"></div>
                        </div>
                        <span style="width:40px; text-align:right; padding-left:8px;">{{ $item['value'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- ===== Top Areas by Citations (Horizontal bar chart) ===== -->
        @if(!empty($top_by_citations))
            @php
                $maxCit = max(array_column($top_by_citations, 'value')) ?: 1;
            @endphp
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Top Research Areas by Citations</h4>
                @foreach($top_by_citations as $item)
                    @php
                        $width = ($item['value'] / $maxCit) * 100;
                    @endphp
                    <div style="display:flex; align-items:center; margin-bottom:4px; font-size:9px;">
                        <span style="width:180px; text-align:right; padding-right:8px; font-weight:bold;">{{ $item['area'] }}</span>
                        <div style="flex:1; height:14px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
                            <div style="width:{{ $width }}%; height:14px; background:#f59e0b; border-radius:4px;"></div>
                        </div>
                        <span style="width:40px; text-align:right; padding-left:8px;">{{ $item['value'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- ===== Trend (Line chart simulated) ===== -->
        @if(!empty($trend))
            @php
                // Determine which areas to show (top 5)
                $allAreas = array_keys($trend[0] ?? []);
                $areaKeys = array_filter($allAreas, fn($k) => $k !== 'year');
                $areaCounts = [];
                foreach ($areaKeys as $area) {
                    $sum = array_sum(array_column($trend, $area));
                    $areaCounts[$area] = $sum;
                }
                arsort($areaCounts);
                $topAreas = array_slice(array_keys($areaCounts), 0, 5);
                $maxVal = 1;
                foreach ($trend as $row) {
                    foreach ($topAreas as $area) {
                        $val = $row[$area] ?? 0;
                        if ($val > $maxVal) $maxVal = $val;
                    }
                }
                $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
            @endphp
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Research Area Trend</h4>
                <div dir="ltr">
                    <svg viewBox="0 0 650 250" style="width:100%; height:auto;">
                        @php
                            $padding = 50;
                            $chartW = 650 - 2 * $padding;
                            $chartH = 250 - 2 * $padding;
                            $count = count($trend);
                            $xStep = $count > 1 ? $chartW / ($count - 1) : $chartW;
                            $scaleX = function($i) use ($padding, $xStep) { return $padding + $i * $xStep; };
                            $scaleY = function($val) use ($padding, $chartH, $maxVal) {
                                return $padding + $chartH - ($val / $maxVal) * $chartH;
                            };
                        @endphp
                        <!-- Grid lines -->
                        @for($i = 0; $i <= 4; $i++)
                            @php $y = $padding + $chartH - ($i/4)*$chartH; @endphp
                            <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $padding + $chartW }}" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="0.5"/>
                            <text x="{{ $padding - 10 }}" y="{{ $y + 4 }}" font-size="8" fill="#6b7280" text-anchor="end">{{ round($maxVal * (1 - $i/4)) }}</text>
                        @endfor
                        <!-- X-axis labels -->
                        @foreach($trend as $i => $row)
                            <text x="{{ $scaleX($i) }}" y="{{ $padding + $chartH + 18 }}" font-size="8" fill="#6b7280" text-anchor="middle">{{ $row['year'] }}</text>
                        @endforeach
                        <!-- Lines for each top area -->
                        @foreach($topAreas as $idx => $area)
                            @php
                                $points = [];
                                foreach ($trend as $i => $row) {
                                    $val = $row[$area] ?? 0;
                                    $points[] = $scaleX($i) . ',' . $scaleY($val);
                                }
                                $pointsStr = implode(' ', $points);
                                $color = $colors[$idx % count($colors)];
                            @endphp
                            <polyline points="{{ $pointsStr }}" fill="none" stroke="{{ $color }}" stroke-width="2"/>
                            <!-- Markers -->
                            @foreach($trend as $i => $row)
                                @php
                                    $val = $row[$area] ?? 0;
                                    $cx = $scaleX($i);
                                    $cy = $scaleY($val);
                                @endphp
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="2.5" fill="{{ $color }}"/>
                            @endforeach
                            <!-- Legend -->
                            <text x="{{ $padding + $chartW - 10 }}" y="{{ $padding + 15 + $idx * 15 }}" font-size="8" fill="{{ $color }}" text-anchor="end">{{ $area }}</text>
                        @endforeach
                        <!-- Axes -->
                        <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                        <line x1="{{ $padding }}" y1="{{ $padding + $chartH }}" x2="{{ $padding + $chartW }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    </svg>
                </div>
            </div>
        @endif

        <!-- ===== Faculty Breakdown (Heatmap as Table) ===== -->
        @if(!empty($faculty_breakdown))
            @php
                // Gather all areas from all faculties
                $allAreas = [];
                foreach ($faculty_breakdown as $fac => $areas) {
                    foreach (array_keys($areas) as $area) {
                        $allAreas[$area] = true;
                    }
                }
                $allAreas = array_keys($allAreas);
                sort($allAreas);
                $faculties = array_keys($faculty_breakdown);
                sort($faculties);
            @endphp
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Research Areas by Faculty (Heatmap)</h4>
                <table style="width:100%; border-collapse:collapse; font-size:8px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:4px 2px; border:0.5px solid #cbd5e0; text-align:left;">Faculty</th>
                            @foreach($allAreas as $area)
                                <th style="padding:4px 2px; border:0.5px solid #cbd5e0;">{{ $area }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faculties as $fac)
                            <tr>
                                <td style="padding:4px 2px; border:0.5px solid #e2e8f0; text-align:left; padding-left:4px;">{{ $fac }}</td>
                                @foreach($allAreas as $area)
                                    <td style="padding:4px 2px; border:0.5px solid #e2e8f0;">
                                        {{ $faculty_breakdown[$fac][$area] ?? 0 }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Insights ===== -->
        @if(!empty($insights))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Key Findings</h4>
                <ul style="list-style:none; padding:0;">
                    @foreach($insights as $insight)
                        <li style="padding:6px 0; border-bottom:1px solid #e2e8f0; font-size:10px;">💡 {{ $insight }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ===== Methodology ===== -->
        <div style="font-size:9px; color:#4a5568; margin-top:20px; padding:10px; background:#f8fafc; border-radius:8px;">
            <strong>Methodology:</strong> Research areas are extracted from lecturers' specialized_area fields (JSON). Publications are assigned to each area they cover. Metrics include total publications, citations, researcher counts, Q1 percentage, and share of total publications.
        </div>
    </div>
@endsection