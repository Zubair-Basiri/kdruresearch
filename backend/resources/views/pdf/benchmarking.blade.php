@extends('pdf.generic-analytics')

@section('content')
    <div dir="ltr">
        <!-- Report Title -->
        <h3 style="text-align:center; margin-top:0;">Research Performance Benchmarking</h3>
        <p style="text-align:center; color:#6b7280; font-size:14px; margin-bottom:10px;">
            Compare research performance across universities, faculties, departments, and researchers.
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

        <!-- ===== PRIMARY KPIs ===== -->
        @if(!empty($primary))
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <tr>
                    <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                        <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Publications</div>
                        <div style="font-size:22px; font-weight:700; color:#111827;">{{ $primary['publications'] ?? 0 }}</div>
                    </td>
                    <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                        <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Citations</div>
                        <div style="font-size:22px; font-weight:700; color:#111827;">{{ number_format($primary['citations'] ?? 0) }}</div>
                    </td>
                    <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                        <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Avg Citations</div>
                        <div style="font-size:22px; font-weight:700; color:#111827;">{{ $primary['avg_citations'] ?? 0 }}</div>
                    </td>
                    <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                        <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">H‑index</div>
                        <div style="font-size:22px; font-weight:700; color:#111827;">{{ $primary['h_index'] ?? 0 }}</div>
                    </td>
                    <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                        <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Cited Publications</div>
                        <div style="font-size:22px; font-weight:700; color:#111827;">{{ $primary['cited_publications'] ?? 0 }}</div>
                    </td>
                    <td style="width:16.66%; text-align:center; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 4px;">
                        <div style="font-size:10px; text-transform:uppercase; color:#6b7280;">Q1</div>
                        <div style="font-size:22px; font-weight:700; color:#111827;">{{ $primary['q1'] ?? 0 }}</div>
                    </td>
                </tr>
            </table>
        @endif

        <!-- ===== COMPARISON MATRIX ===== -->
        <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
            <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Benchmark Comparison Matrix</h4>
            @if(!empty($comparison))
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Indicator</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Primary</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Peer Average</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Difference</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparison as $item)
                            <tr>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $item['indicator'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['primary_value'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['peer_average'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">
                                    @php
                                        $diff = $item['difference_percent'];
                                        $color = $diff > 0 ? '#16a34a' : ($diff < 0 ? '#dc2626' : '#6b7280');
                                    @endphp
                                    <span style="color:{{ $color }}; font-weight:bold;">{{ $diff }}%</span>
                                </td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">
                                    @php
                                        $badgeColor = $item['status'] === 'above' ? '#22c55e' : ($item['status'] === 'below' ? '#ef4444' : '#f59e0b');
                                    @endphp
                                    <span style="background:{{ $badgeColor }}; color:#fff; padding:2px 8px; border-radius:12px; font-size:8px; text-transform:uppercase;">
                                        {{ $item['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="text-align:center; color:#6b7280; font-size:10px; margin:10px 0;">
                    @if(empty($peers))
                        No peer entities selected for comparison.
                    @else
                        No comparison data available.
                    @endif
                </p>
            @endif
        </div>

        <!-- ===== RANKING ===== -->
        <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
            <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Research Performance Benchmark Ranking</h4>
            @if(!empty($ranking))
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Rank</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Name</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Publications</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Citations</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Avg Citations</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">H‑index</th>
                            <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Q1</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $primaryId = $primary['entity_id'] ?? null; @endphp
                        @foreach($ranking as $item)
                            @php
                                $isPrimary = ($item['entity']['id'] == $primaryId);
                            @endphp
                            <tr style="{{ $isPrimary ? 'background:#eff6ff;' : '' }}">
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; font-weight:bold;">{{ $item['rank'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $item['entity']['name'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['publications'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['citations'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['avg_citations'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['h_index'] }}</td>
                                <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['q1'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="text-align:center; color:#6b7280; font-size:10px; margin:10px 0;">No ranking data available.</p>
            @endif
        </div>

        <!-- ===== INTERNAL BENCHMARK ===== -->
        @if(isset($level) && $level !== 'university')
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">{{ ucfirst($level) }} Performance (Internal)</h4>
                @if(!empty($internal_benchmark))
                    <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                        <thead>
                            <tr style="background:#f1f5f9;">
                                <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Name</th>
                                <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Publications</th>
                                <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Citations</th>
                                <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Avg</th>
                                <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">H‑index</th>
                                <th style="padding:8px 4px; border:0.5px solid #cbd5e0;">Q1</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($internal_benchmark as $item)
                                <tr>
                                    <td style="padding:6px 3px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">{{ $item['entity']['name'] }}</td>
                                    <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['publications'] }}</td>
                                    <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['citations'] }}</td>
                                    <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['avg_citations'] }}</td>
                                    <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['h_index'] }}</td>
                                    <td style="padding:6px 3px; border:0.5px solid #e2e8f0;">{{ $item['metrics']['q1'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="text-align:center; color:#6b7280; font-size:10px; margin:10px 0;">No internal benchmark data available.</p>
                @endif
            </div>
        @endif

        <!-- ===== INSIGHTS ===== -->
        <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
            <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">Benchmark Insights</h4>
            @if(!empty($insights))
                <ul style="list-style:none; padding:0;">
                    @foreach($insights as $insight)
                        <li style="padding:6px 0; border-bottom:1px solid #e2e8f0; font-size:10px;">💡 {{ $insight }}</li>
                    @endforeach
                </ul>
            @else
                <p style="text-align:center; color:#6b7280; font-size:10px; margin:10px 0;">
                    @if(empty($peers))
                        No peer entities selected. Comparative insights are unavailable.
                    @else
                        No insights available.
                    @endif
                </p>
            @endif
        </div>

        <!-- ===== METHODOLOGY ===== -->
        <div style="font-size:9px; color:#4a5568; margin-top:20px; padding:10px; background:#f8fafc; border-radius:8px;">
            <strong>Methodology:</strong> Benchmarking compares research output across selected entities. Metrics include publications, citations, average citations, H‑index, and Q1 indexed publications. Peer averages are calculated from selected peer entities.
        </div>
    </div>
@endsection