@extends('pdf.generic-analytics')

@section('content')
    <div dir="ltr">
        <!-- Page Title -->
        <h3 style="text-align:center; margin-top:0;">Top 10 Lists</h3>
        <p style="text-align:center; color:#6b7280; font-size:14px; margin-bottom:10px;">
            Top-performing researchers, publications, faculties, departments, and research areas
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

        <!-- ===== PODIUM (Top 3) – TABLE LAYOUT ===== -->
        @if(!empty($ranking) && count($ranking) >= 1)
            @php
                $top3 = array_slice($ranking, 0, 3);
                $entityLabel = $category === 'researchers' ? 'Researcher' :
                               ($category === 'publications' ? 'Title' :
                               ($category === 'faculties' ? 'Faculty' :
                               ($category === 'departments' ? 'Department' :
                               ($category === 'universities' ? 'University' :
                               ($category === 'research_areas' ? 'Research Area' : 'Entity')))));
            @endphp
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <tr>
                    @foreach($top3 as $index => $item)
                        @php
                            $rankNum = $index + 1;
                            $name = $item['name'] ?? $item['title'] ?? $item['area'] ?? 'N/A';
                            $value = $item['metric_value'] ?? 0;
                            $extra = '';
                            if ($category === 'researchers' && isset($item['publications'])) {
                                $extra = $item['publications'] . ' pubs';
                            }
                            $borderColor = $rankNum === 1 ? '#fbbf24' : ($rankNum === 2 ? '#94a3b8' : '#f59e0b');
                            $bgColor = $rankNum === 1 ? '#fffbeb' : ($rankNum === 2 ? '#f1f5f9' : '#fffbeb');
                        @endphp
                        <td style="width:33%; text-align:center; border:2px solid {{ $borderColor }}; border-radius:12px; padding:16px 8px; background:{{ $bgColor }}; vertical-align:top;">
                            <div style="font-size:32px; font-weight:700; color:#1e293b;">{{ $rankNum }}</div>
                            <div style="font-size:12px; font-weight:600; margin:4px 0; word-wrap:break-word;">{{ $name }}</div>
                            <div style="font-size:20px; font-weight:700; color:#0f172a;">{{ $value }}</div>
                            @if($extra)
                                <div style="font-size:10px; color:#6b7280; margin-top:4px;">{{ $extra }}</div>
                            @endif
                        </td>
                    @endforeach
                    <!-- Fill empty cells if fewer than 3 -->
                    @for($i = count($top3); $i < 3; $i++)
                        <td style="width:33%;"></td>
                    @endfor
                </tr>
            </table>
        @endif

        <!-- ===== RANKING TABLE ===== -->
        @if(!empty($ranking))
            @php
                // Determine display columns from the first row
                $first = $ranking[0] ?? [];
                $allKeys = array_keys($first);
                $exclude = ['id', 'metric_value', 'rank', 'name', 'title', 'area', 'faculty', 'department', 'university'];
                $displayCols = array_filter($allKeys, function($k) use ($exclude) {
                    return !in_array($k, $exclude) && !is_numeric($k);
                });
                $entityKey = isset($first['name']) ? 'name' : (isset($first['title']) ? 'title' : (isset($first['area']) ? 'area' : 'entity'));
                $entityLabel = $category === 'researchers' ? 'Researcher' :
                               ($category === 'publications' ? 'Title' :
                               ($category === 'faculties' ? 'Faculty' :
                               ($category === 'departments' ? 'Department' :
                               ($category === 'universities' ? 'University' :
                               ($category === 'research_areas' ? 'Research Area' : 'Entity')))));

                $headers = ['Rank', $entityLabel];
                $colKeys = [];
                $labelMap = [
                    'publications' => 'Pubs',
                    'citations' => 'Citations',
                    'avg_citations' => 'Avg Cit.',
                    'h_index' => 'H‑index',
                    'q1' => 'Q1',
                    'q1_share' => 'Q1 %',
                    'collaborative' => 'Collab',
                    'collaboration_rate' => 'Collab %',
                    'funded' => 'Funded',
                    'funded_rate' => 'Funded %',
                    'cited' => 'Cited',
                    'cited_rate' => 'Cited %',
                    'researchers' => 'Researchers',
                    'year' => 'Year',
                    'collaboration' => 'Collab Type',
                    'publications_per_researcher' => 'Pubs/Res.',
                    'citations_per_researcher' => 'Cit./Res.',
                ];
                foreach ($displayCols as $col) {
                    $label = $labelMap[$col] ?? ucfirst(str_replace('_', ' ', $col));
                    $headers[] = $label;
                    $colKeys[] = $col;
                }
                $headers[] = 'Metric';
            @endphp
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff;">
                <h4 style="margin-top:0; margin-bottom:12px; font-size:16px; font-weight:600; color:#1F4E79;">
                    Top {{ $limit }} {{ ucfirst($entityLabel) }}s by {{ $metric_label ?? $metric }}
                </h4>
                <table style="width:100%; border-collapse:collapse; font-size:9px; text-align:center;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            @foreach($headers as $hdr)
                                <th style="padding:6px 4px; border:0.5px solid #cbd5e0;">{{ $hdr }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ranking as $item)
                            <tr>
                                <td style="padding:4px 2px; border:0.5px solid #e2e8f0; font-weight:bold;">{{ $item['rank'] }}</td>
                                <td style="padding:4px 2px; border:0.5px solid #e2e8f0; text-align:left; padding-left:6px;">
                                    {{ $item[$entityKey] ?? '-' }}
                                </td>
                                @foreach($colKeys as $col)
                                    <td style="padding:4px 2px; border:0.5px solid #e2e8f0;">
                                        {{ $item[$col] ?? '-' }}
                                    </td>
                                @endforeach
                                <td style="padding:4px 2px; border:0.5px solid #e2e8f0; font-weight:bold;">
                                    {{ $item['metric_value'] ?? 0 }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:20px; background:#fff; text-align:center; color:#6b7280;">
                No ranking data available for the selected filters.
            </div>
        @endif

        <!-- ===== KEY FINDINGS ===== -->
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

        <!-- ===== METHODOLOGY ===== -->
        <div style="font-size:9px; color:#4a5568; margin-top:20px; padding:10px; background:#f8fafc; border-radius:8px;">
            <strong>Methodology:</strong> Rankings are generated based on the selected category and metric. The top 3 entries are highlighted on the podium. Ties are assigned the same rank with subsequent ranks skipped.
        </div>
@endsection
    </div>