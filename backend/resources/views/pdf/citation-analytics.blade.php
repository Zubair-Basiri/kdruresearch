@extends('pdf.generic-analytics')

@section('content')
    <div dir="ltr">
        <!-- Report Title -->
        <h3 style="text-align:center; margin-top:0;">Citation Analytics</h3>
        <p style="text-align:center; color:#6b7280; font-size:14px; margin-bottom:10px;">Research Impact &amp; Citation Performance</p>

        <!-- ===== KPI CARDS ===== -->
        <table class="kpi-grid" style="width:100%; border-collapse:collapse; margin-bottom:15px;">
            <tr>
                <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                    <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Total Citations</div>
                    <div style="font-size:22px; font-weight:700; color:#111827;">{{ number_format($summary['total_citations']) }}</div>
                </td>
                <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                    <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Avg per Paper</div>
                    <div style="font-size:22px; font-weight:700; color:#111827;">{{ $summary['average_citations'] }}</div>
                </td>
                <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                    <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Cited Publications</div>
                    <div style="font-size:22px; font-weight:700; color:#111827;">{{ $summary['cited_publications'] }}</div>
                </td>
                <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                    <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Uncited Publications</div>
                    <div style="font-size:22px; font-weight:700; color:#111827;">{{ $summary['uncited_publications'] }}</div>
                </td>
                <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                    <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">H‑index</div>
                    <div style="font-size:22px; font-weight:700; color:#111827;">{{ $summary['h_index'] }}</div>
                </td>
                <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                    <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Cited %</div>
                    <div style="font-size:22px; font-weight:700; color:#111827;">{{ $summary['cited_percentage'] }}%</div>
                </td>
            </tr>
        </table>

        <!-- ===== CHART 1: CITATION TREND (Area) ===== -->
        <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:10px; background:#fff;">
            <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Citation Trend</h4>
            @if(!empty($trend))
                @php
                    $maxTrend = max(array_column($trend, 'citations')) ?: 1;
                    $count = count($trend);
                    $width = 650;
                    $height = 200;
                    $padding = 50;
                    $chartW = $width - 2 * $padding;
                    $chartH = $height - 2 * $padding;
                    $xStep = $count > 1 ? $chartW / ($count - 1) : $chartW;
                    $scaleX = function($i) use ($padding, $xStep) { return $padding + $i * $xStep; };
                    $scaleY = function($val) use ($padding, $chartH, $maxTrend) {
                        return $padding + $chartH - ($val / $maxTrend) * $chartH;
                    };
                    $points = [];
                    $linePoints = [];
                    foreach ($trend as $i => $row) {
                        $x = $scaleX($i);
                        $y = $scaleY($row['citations']);
                        $points[] = "$x,$y";
                        $linePoints[] = "$x,$y";
                    }
                    $lastX = $scaleX($count - 1);
                    $firstX = $scaleX(0);
                    $baseY = $padding + $chartH;
                    $polygonPoints = implode(' ', $points) . " $lastX,$baseY $firstX,$baseY";
                    $linePointsStr = implode(' ', $linePoints);
                @endphp
                <svg viewBox="0 0 {{ $width }} {{ $height }}" style="width:100%; height:auto;">
                    @for($i = 0; $i <= 4; $i++)
                        @php $y = $padding + $chartH - ($i/4)*$chartH; @endphp
                        <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $padding + $chartW }}" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="0.5"/>
                        <text x="{{ $padding - 8 }}" y="{{ $y + 4 }}" font-size="9" fill="#6b7280" text-anchor="end">{{ round($maxTrend * (1 - $i/4)) }}</text>
                    @endfor
                    @foreach($trend as $i => $row)
                        <text x="{{ $scaleX($i) }}" y="{{ $padding + $chartH + 18 }}" font-size="9" fill="#6b7280" text-anchor="middle">{{ $row['year'] }}</text>
                    @endforeach
                    <polygon points="{{ $polygonPoints }}" fill="url(#trendGradient)" opacity="0.7"/>
                    <polyline points="{{ $linePointsStr }}" fill="none" stroke="#3b82f6" stroke-width="2"/>
                    @foreach($trend as $i => $row)
                        <circle cx="{{ $scaleX($i) }}" cy="{{ $scaleY($row['citations']) }}" r="2.5" fill="#3b82f6"/>
                    @endforeach
                    <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <line x1="{{ $padding }}" y1="{{ $padding + $chartH }}" x2="{{ $padding + $chartW }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <defs>
                        <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.5"/>
                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.05"/>
                        </linearGradient>
                    </defs>
                </svg>
            @else
                <p>No yearly data available.</p>
            @endif
        </div>

        <!-- ===== CHART 2: CITATION DISTRIBUTION (Vertical Bar) ===== -->
        <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
            <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Citation Distribution</h4>
            @if(!empty($citation_distribution))
                @php
                    $maxDist = max(array_column($citation_distribution, 'count')) ?: 1;
                    $bars = $citation_distribution;
                    $count = count($bars);
                    $width = 650;
                    $height = 200;
                    $padding = 50;
                    $chartW = $width - 2 * $padding;
                    $chartH = $height - 2 * $padding;
                    $barWidth = $count > 0 ? min(50, $chartW / $count * 0.6) : 15;
                    $gap = $count > 0 ? ($chartW - $barWidth * $count) / ($count + 1) : 0;
                    $scaleY = function($val) use ($padding, $chartH, $maxDist) {
                        return $padding + $chartH - ($val / $maxDist) * $chartH;
                    };
                @endphp
                <svg viewBox="0 0 {{ $width }} {{ $height }}" style="width:100%; height:auto;">
                    @for($i = 0; $i <= 4; $i++)
                        @php $y = $padding + $chartH - ($i/4)*$chartH; @endphp
                        <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $padding + $chartW }}" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="0.5"/>
                        <text x="{{ $padding - 8 }}" y="{{ $y + 4 }}" font-size="9" fill="#6b7280" text-anchor="end">{{ round($maxDist * (1 - $i/4)) }}</text>
                    @endfor
                    @foreach($bars as $i => $bar)
                        @php
                            $x = $padding + $gap + $i * ($barWidth + $gap);
                            $heightPx = ($bar['count'] / $maxDist) * $chartH;
                            $y = $padding + $chartH - $heightPx;
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $heightPx }}" fill="#10b981" rx="2"/>
                        <text x="{{ $x + $barWidth/2 }}" y="{{ $padding + $chartH + 16 }}" font-size="8" fill="#4a5568" text-anchor="middle" transform="rotate(-15, {{ $x + $barWidth/2 }}, {{ $padding + $chartH + 16 }})">{{ $bar['range'] }}</text>
                        <text x="{{ $x + $barWidth/2 }}" y="{{ $y - 4 }}" font-size="9" fill="#1e293b" text-anchor="middle">{{ $bar['count'] }}</text>
                    @endforeach
                    <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <line x1="{{ $padding }}" y1="{{ $padding + $chartH }}" x2="{{ $padding + $chartW }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <text x="{{ $width/2 }}" y="{{ $height - 4 }}" font-size="10" fill="#4a5568" text-anchor="middle">Citation Range</text>
                    <text x="{{ 14 }}" y="{{ $height/2 }}" font-size="10" fill="#4a5568" text-anchor="middle" transform="rotate(-90, 14, {{ $height/2 }})">Publications</text>
                </svg>
            @else
                <p>No distribution data.</p>
            @endif
        </div>

        <!-- ===== CHART 3: CITATIONS BY FACULTY (Horizontal Bar) ===== -->
        <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
            <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Citations by Faculty</h4>
            @if(!empty($faculty_collab))
                @php
                    $maxFaculty = max(array_column($faculty_collab, 'citations')) ?: 1;
                    $bars = $faculty_collab;
                    usort($bars, fn($a,$b) => $b['citations'] <=> $a['citations']);
                    $count = count($bars);
                    $width = 650;
                    $height = max(220, $count * 22 + 60);
                    $paddingLeft = 120;
                    $paddingRight = 40;
                    $paddingTop = 30;
                    $paddingBottom = 30;
                    $chartW = $width - $paddingLeft - $paddingRight;
                    $chartH = $height - $paddingTop - $paddingBottom;
                    $barHeight = min(18, $chartH / $count * 0.6);
                    $gap = $chartH / $count;
                    $scaleX = function($val) use ($paddingLeft, $chartW, $maxFaculty) {
                        return $paddingLeft + ($val / $maxFaculty) * $chartW;
                    };
                @endphp
                <svg viewBox="0 0 {{ $width }} {{ $height }}" style="width:100%; height:auto;">
                    @for($i = 0; $i <= 4; $i++)
                        @php $x = $paddingLeft + ($i/4)*$chartW; @endphp
                        <line x1="{{ $x }}" y1="{{ $paddingTop }}" x2="{{ $x }}" y2="{{ $paddingTop + $chartH }}" stroke="#e2e8f0" stroke-width="0.5"/>
                        <text x="{{ $x }}" y="{{ $paddingTop + $chartH + 16 }}" font-size="9" fill="#6b7280" text-anchor="middle">{{ round($maxFaculty * ($i/4)) }}</text>
                    @endfor
                    @foreach($bars as $i => $bar)
                        @php
                            $y = $paddingTop + $i * $gap + ($gap - $barHeight) / 2;
                            $x = $scaleX($bar['citations']);
                            $barWidth = $x - $paddingLeft;
                        @endphp
                        <rect x="{{ $paddingLeft }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}" fill="#8b5cf6" rx="2"/>
                        <text x="{{ $paddingLeft - 6 }}" y="{{ $y + $barHeight/2 + 3 }}" font-size="9" fill="#1e293b" text-anchor="end">{{ $bar['entity_name'] }}</text>
                        <text x="{{ $x + 6 }}" y="{{ $y + $barHeight/2 + 3 }}" font-size="9" fill="#1e293b" text-anchor="start">{{ $bar['citations'] }}</text>
                    @endforeach
                    <line x1="{{ $paddingLeft }}" y1="{{ $paddingTop }}" x2="{{ $paddingLeft }}" y2="{{ $paddingTop + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <line x1="{{ $paddingLeft }}" y1="{{ $paddingTop + $chartH }}" x2="{{ $paddingLeft + $chartW }}" y2="{{ $paddingTop + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <text x="{{ $width/2 }}" y="{{ $height - 4 }}" font-size="10" fill="#4a5568" text-anchor="middle">Citations</text>
                    <text x="{{ 14 }}" y="{{ $height/2 }}" font-size="10" fill="#4a5568" text-anchor="middle" transform="rotate(-90, 14, {{ $height/2 }})">Faculty</text>
                </svg>
            @else
                <p>No faculty data.</p>
            @endif
        </div>

        <!-- ===== CHART 4: CITATION IMPACT SCATTER ===== -->
        <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
            <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Citation Impact (Publications vs Citations)</h4>
            @if(!empty($faculty_collab))
                @php
                    $points = $faculty_collab;
                    $maxPubs = max(array_column($points, 'publications')) ?: 1;
                    $maxCits = max(array_column($points, 'citations')) ?: 1;
                    $width = 650;
                    $height = 280;
                    $padding = 55;
                    $chartW = $width - 2 * $padding;
                    $chartH = $height - 2 * $padding;
                    $scaleX = function($pub) use ($padding, $chartW, $maxPubs) {
                        return $padding + ($pub / $maxPubs) * $chartW;
                    };
                    $scaleY = function($cit) use ($padding, $chartH, $maxCits) {
                        return $padding + $chartH - ($cit / $maxCits) * $chartH;
                    };
                @endphp
                <svg viewBox="0 0 {{ $width }} {{ $height }}" style="width:100%; height:auto;">
                    @for($i = 0; $i <= 4; $i++)
                        @php
                            $x = $padding + ($i/4)*$chartW;
                            $y = $padding + $chartH - ($i/4)*$chartH;
                        @endphp
                        <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $padding + $chartW }}" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="0.5"/>
                        <line x1="{{ $x }}" y1="{{ $padding }}" x2="{{ $x }}" y2="{{ $padding + $chartH }}" stroke="#e2e8f0" stroke-width="0.5"/>
                        <text x="{{ $padding - 8 }}" y="{{ $y + 4 }}" font-size="9" fill="#6b7280" text-anchor="end">{{ round($maxCits * (1 - $i/4)) }}</text>
                        <text x="{{ $x }}" y="{{ $padding + $chartH + 16 }}" font-size="9" fill="#6b7280" text-anchor="middle">{{ round($maxPubs * ($i/4)) }}</text>
                    @endfor
                    @foreach($points as $p)
                        @php
                            $cx = $scaleX($p['publications']);
                            $cy = $scaleY($p['citations']);
                            $radius = 5 + ($p['citations'] / $maxCits) * 10;
                            $ratio = $p['publications'] > 0 ? $p['citations'] / $p['publications'] : 0;
                            $color = $ratio > 10 ? '#dc2626' : ($ratio > 5 ? '#f59e0b' : '#3b82f6');
                        @endphp
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $radius }}" fill="{{ $color }}" opacity="0.7"/>
                        <text x="{{ $cx }}" y="{{ $cy - $radius - 3 }}" font-size="7" fill="#1e293b" text-anchor="middle">{{ $p['entity_name'] }}</text>
                    @endforeach
                    <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <line x1="{{ $padding }}" y1="{{ $padding + $chartH }}" x2="{{ $padding + $chartW }}" y2="{{ $padding + $chartH }}" stroke="#cbd5e0" stroke-width="1"/>
                    <text x="{{ $width/2 }}" y="{{ $height - 4 }}" font-size="10" fill="#4a5568" text-anchor="middle">Publications</text>
                    <text x="{{ 14 }}" y="{{ $height/2 }}" font-size="10" fill="#4a5568" text-anchor="middle" transform="rotate(-90, 14, {{ $height/2 }})">Citations</text>
                    <text x="{{ $padding + $chartW - 110 }}" y="{{ $padding + 15 }}" font-size="8" fill="#4a5568">● Size = citations</text>
                    <text x="{{ $padding + $chartW - 110 }}" y="{{ $padding + 28 }}" font-size="8" fill="#4a5568">● Color = impact</text>
                </svg>
            @else
                <p>No impact data.</p>
            @endif
        </div>

        <!-- ===== TOP RESEARCHERS TABLE ===== -->
        <div style="margin-bottom:20px;">
            <h4 style="color:#1F4E79; font-weight:600; font-size:16px;">Top Researchers by Citations</h4>
            <table class="academic-table" style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th>Rank</th>
                        <th>Researcher</th>
                        <th>Faculty</th>
                        <th>Department</th>
                        <th>Publications</th>
                        <th>Citations</th>
                        <th>Avg</th>
                        <th>H‑index</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($researcher_collab as $r)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td style="text-align:left; padding-left:6px;">{{ $r['researcher_name'] }}</td>
                            <td>{{ $r['faculty'] }}</td>
                            <td>{{ $r['department'] ?? '-' }}</td>
                            <td>{{ $r['publications'] }}</td>
                            <td>{{ $r['citations'] }}</td>
                            <td>{{ number_format($r['avg_citations'] ?? 0, 2) }}</td>
                            <td>{{ $r['h_index'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- ===== MOST CITED PUBLICATIONS ===== -->
        <div style="margin-bottom:20px;">
            <h4 style="color:#1F4E79; font-weight:600; font-size:16px;">Most Cited Publications</h4>
            @if(!empty($top_publications))
                <table class="academic-table" style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th>Rank</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Year</th>
                            <th>Faculty</th>
                            <th>Citations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($top_publications as $p)
                            <tr>
                                <td>{{ $p['rank'] }}</td>
                                <td style="text-align:left; padding-left:6px;">{{ $p['title'] }}</td>
                                <td>{{ $p['author'] }}</td>
                                <td>{{ $p['year'] }}</td>
                                <td>{{ $p['faculty'] }}</td>
                                <td>{{ $p['citations'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No publications with citations.</p>
            @endif
        </div>

        <!-- ===== KEY FINDINGS ===== -->
        <div style="margin-bottom:20px;">
            <h4 style="color:#1F4E79; font-weight:600; font-size:16px;">Key Findings</h4>
            <ul style="list-style:none; padding:0;">
                @foreach($key_findings as $finding)
                    <li style="padding:6px 0; border-bottom:1px solid #e2e8f0; font-size:10px;">• {{ $finding }}</li>
                @endforeach
            </ul>
        </div>

        <!-- ===== METHODOLOGY ===== -->
        <div style="font-size:9px; color:#4a5568; margin-top:20px; padding:10px; background:#f8fafc; border-radius:8px;">
            <strong>Methodology:</strong> Citation data is aggregated from academic papers. H‑index is calculated from citation counts.
            Rankings are based on the selected filters and university scope.
        </div>
    </div>
@endsection