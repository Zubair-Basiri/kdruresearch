@extends('pdf.generic-analytics')

@section('content')
    <div dir="ltr">
        <!-- Page Title -->
        <h3 style="text-align:center; margin-top:0;">Collaboration Analysis</h3>
        <p style="text-align:center; color:#6b7280; font-size:14px; margin-bottom:10px;">
            Research collaboration patterns, trends, and impact
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
                $kpis = [
                    ['label' => 'Total Publications', 'value' => $summary['total_publications']],
                    ['label' => 'Collaborative Publications', 'value' => $summary['collaborative_publications']],
                    ['label' => 'Collaboration Rate', 'value' => $summary['collaboration_rate'] . '%'],
                    ['label' => 'Total Citations', 'value' => number_format($summary['total_citations'])],
                    ['label' => 'Avg Citations (Collab)', 'value' => $summary['average_collaborative_citations']],
                    ['label' => 'Collaborating Researchers', 'value' => $summary['collaborative_researchers']],
                ];
            @endphp
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <tr>
                    @foreach($kpis as $kpi)
                        <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                            <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">{{ $kpi['label'] }}</div>
                            <div style="font-size:22px; font-weight:700; color:#111827;">{{ $kpi['value'] }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif

        <!-- ===== Collaboration Overview ===== -->
        @if(!empty($summary))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Collaboration Overview</h4>
                <table style="width:100%; border-collapse:collapse; font-size:11px; text-align:center;">
                    <tr>
                        <td style="padding:8px; border:1px solid #e2e8f0; background:#f8fafc;">
                            <div style="color:#4a5568;">Collaborative Publications</div>
                            <div style="font-size:24px; font-weight:700; color:#111827;">{{ $summary['collaborative_publications'] }}</div>
                        </td>
                        <td style="padding:8px; border:1px solid #e2e8f0; background:#f8fafc;">
                            <div style="color:#4a5568;">Non-Collaborative</div>
                            <div style="font-size:24px; font-weight:700; color:#111827;">{{ $summary['non_collaborative_publications'] }}</div>
                        </td>
                        <td style="padding:8px; border:1px solid #e2e8f0; background:#f8fafc;">
                            <div style="color:#4a5568;">Collaboration Rate</div>
                            <div style="font-size:24px; font-weight:700; color:#111827;">{{ $summary['collaboration_rate'] }}%</div>
                        </td>
                        <td style="padding:8px; border:1px solid #e2e8f0; background:#f8fafc;">
                            <div style="color:#4a5568;">Avg Citations (Collaborative)</div>
                            <div style="font-size:24px; font-weight:700; color:#111827;">{{ $summary['average_collaborative_citations'] }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        <!-- ===== Trend Chart (Bar Chart using SVG) ===== -->
        @if(!empty($trend))
            @php
                $maxTotal = max(array_column($trend, 'total')) ?: 1;
                $maxCollab = max(array_column($trend, 'collaborative')) ?: 1;
                $maxValue = max($maxTotal, $maxCollab);
                $width = 650;
                $height = 300;
                $padding = 60;
                $chartW = $width - 2 * $padding;
                $chartH = $height - 2 * $padding;
                $count = count($trend);
                $barWidth = $count > 0 ? min(40, $chartW / $count * 0.6) : 20;
                $gap = $count > 0 ? ($chartW - $barWidth * $count) / ($count + 1) : 0;
                $scaleY = function($val) use ($padding, $chartH, $maxValue) {
                    return $padding + $chartH - ($val / $maxValue) * $chartH;
                };
            @endphp
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Collaboration Trend</h4>
                <svg viewBox="0 0 {{ $width }} {{ $height }}" style="width:100%; height:auto;">
                    <!-- Grid lines -->
                    @for($i = 0; $i <= 4; $i++)
                        @php $y = $padding + $chartH - ($i/4)*$chartH; @endphp
                        <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $padding + $chartW }}" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="0.5"/>
                        <text x="{{ $padding - 10 }}" y="{{ $y + 4 }}" font-size="9" fill="#6b7280" text-anchor="end">{{ round($maxValue * (1 - $i/4)) }}</text>
                    @endfor
                    <!-- X-axis labels -->
                    @foreach($trend as $i => $row)
                        <text x="{{ $padding + $gap + $i * ($barWidth + $gap) + $barWidth/2 }}" y="{{ $padding + $chartH + 18 }}" font-size="9" fill="#6b7280" text-anchor="middle">{{ $row['year'] }}</text>
                    @endforeach
                    <!-- Bars: Total (blue) and Collaborative (green) stacked? Actually side by side -->
                    @foreach($trend as $i => $row)
                        @php
                            $x = $padding + $gap + $i * ($barWidth + $gap);
                            $totalHeight = ($row['total'] / $maxValue) * $chartH;
                            $collabHeight = ($row['collaborative'] / $maxValue) * $chartH;
                            $yTotal = $padding + $chartH - $totalHeight;
                            $yCollab = $padding + $chartH - $collabHeight;
                            $subBarWidth = $barWidth / 2 - 1;
                        @endphp
                        <!-- Total bar (blue) -->
                        <rect x="{{ $x }}" y="{{ $yTotal }}" width="{{ $subBarWidth }}" height="{{ $totalHeight }}" fill="#3b82f6" rx="2"/>
                        <!-- Collaborative bar (green) -->
                        <rect x="{{ $x + $subBarWidth + 2 }}" y="{{ $yCollab }}" width="{{ $subBarWidth }}" height="{{ $collabHeight }}" fill="#10b981" rx="2"/>
                    @endforeach
                    <!-- Axes -->
                    <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <line x1="{{ $padding }}" y1="{{ $padding + $chartH }}" x2="{{ $padding + $chartW }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <!-- Legend -->
                    <text x="{{ $padding + $chartW - 140 }}" y="{{ $padding + 20 }}" font-size="9" fill="#3b82f6">■ Total</text>
                    <text x="{{ $padding + $chartW - 70 }}" y="{{ $padding + 20 }}" font-size="9" fill="#10b981">■ Collaborative</text>
                </svg>
            </div>
        @endif

        <!-- ===== Collaboration Types (Donut approximation as table with horizontal bars) ===== -->
        @if(!empty($collab_types))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Collaboration Types</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:6px 4px; border:0.5px solid #cbd5e0; text-align:left;">Type</th>
                            <th style="padding:6px 4px; border:0.5px solid #cbd5e0; text-align:center;">Count</th>
                            <th style="padding:6px 4px; border:0.5px solid #cbd5e0; text-align:center;">Percentage</th>
                            <th style="padding:6px 4px; border:0.5px solid #cbd5e0; text-align:center;">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collab_types as $type)
                            <tr>
                                <td style="padding:4px 6px; border:0.5px solid #e2e8f0;">{{ $type['type'] }}</td>
                                <td style="padding:4px 6px; border:0.5px solid #e2e8f0; text-align:center;">{{ $type['count'] }}</td>
                                <td style="padding:4px 6px; border:0.5px solid #e2e8f0; text-align:center;">{{ $type['percentage'] }}%</td>
                                <td style="padding:4px 6px; border:0.5px solid #e2e8f0;">
                                    <div style="height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden;">
                                        <div style="width:{{ $type['percentage'] }}%; height:10px; background:#10b981; border-radius:5px;"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== University Comparison ===== -->
        @if($is_ministry && !empty($university_comparison))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">University Comparison</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">University</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Publications</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Collaborative</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Rate</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Citations</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Avg Citations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($university_comparison as $u)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $u['university_name'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $u['publications'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $u['collaborative'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $u['rate'] }}%</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $u['citations'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $u['avg_citations'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Faculty Collaboration ===== -->
        @if(!empty($faculty_collab))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Faculty Collaboration</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Faculty</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Publications</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Collaborative</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Rate</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Researchers</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Citations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faculty_collab as $f)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $f['entity_name'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $f['publications'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $f['collaborative'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $f['rate'] }}%</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $f['researchers'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $f['citations'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Researcher Collaboration ===== -->
        @if(!empty($researcher_collab))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Researcher Collaboration</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Researcher</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Publications</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Collaborative</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Rate</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Citations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($researcher_collab as $r)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $r['researcher_name'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $r['publications'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $r['collaborative'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $r['rate'] }}%</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $r['citations'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Research Area Collaboration ===== -->
        @if(!empty($research_area_collab))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Research Area Collaboration</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Research Area</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Publications</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Collaborative</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Rate</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Citations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($research_area_collab as $area)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $area['area'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['publications'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['collaborative'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['rate'] }}%</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $area['citations'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- ===== Top Collaborative Publications ===== -->
        @if(!empty($top_publications))
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Top Collaborative Publications</h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Title</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Year</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Faculty</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Researcher</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Collaboration Type</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Citations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($top_publications as $p)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $p['title'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $p['year'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $p['faculty'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $p['researcher'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $p['collaboration_type'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $p['citations'] }}</td>
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

        <!-- ===== Methodology ===== -->
        <div style="font-size:9px; color:#4a5568; margin-top:20px; padding:10px; background:#f8fafc; border-radius:8px;">
            <strong>Methodology:</strong> Collaboration metrics are derived from academic papers where the collaboration field is not empty. The collaboration rate is the percentage of publications with collaboration. Trend shows yearly totals and collaborative counts. Types include national, international, etc.
        </div>
    </div>
@endsection