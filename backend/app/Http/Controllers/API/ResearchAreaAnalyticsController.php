<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ResearchAreaAnalyticsController extends Controller
{
    /**
     * Get research-area analytics.
     */
    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId();

        $paperQuery = $this->buildPaperQuery($filters, $universityId);
        $papers = $paperQuery->get([
            'academic_papers.id',
            'academic_papers.lecturer_id',
            'academic_papers.citation',
            'academic_papers.year',
            'academic_papers.indexed',
            'academic_papers.publication',
            'academic_papers.collaboration',
            'academic_papers.language',
            'academic_papers.funding',
            'academic_papers.status',
            'lecturers.id as lecturer_id',
            'lecturers.specialized_area',
            'faculties.id as faculty_id',
            'faculties.facultyname as faculty_name',
            'departments.id as department_id',
            'departments.deptname as department_name',
        ]);

        $areaData = $this->extractAreasAndAggregate($papers);
        $kpis = $this->calculateKpis($papers, $areaData);
        $areaOverview = $this->buildAreaOverview($areaData, $papers);
        $distribution = $this->buildDistribution($areaData);
        $topByPublications = $this->getTopAreas($areaData, 'publications', 10);
        $topByCitations = $this->getTopAreas($areaData, 'citations', 10);
        $trend = $this->buildTrend($areaData);
        $facultyBreakdown = $this->buildFacultyBreakdown($areaData);
        $departmentBreakdown = $this->buildDepartmentBreakdown($areaData);
        $topResearchers = $this->getTopResearchersByArea(
            $areaData,
            $filters,
            $universityId,
            $filters['research_area'] ?? null
        );
        $insights = $this->generateInsights($areaData, $papers, $filters);

        return response()->json([
            'kpis' => $kpis,
            'area_overview' => $areaOverview,
            'distribution' => $distribution,
            'top_by_publications' => $topByPublications,
            'top_by_citations' => $topByCitations,
            'trend' => $trend,
            'faculty_breakdown' => $facultyBreakdown,
            'department_breakdown' => $departmentBreakdown,
            'top_researchers' => $topResearchers,
            'insights' => $insights,
            'filters_applied' => $filters,
            'university_scope' => $universityId,
        ]);
    }

    // ---------- Helper Methods ----------

    protected function parseFilters(Request $request)
    {
        return [
            'university' => $request->input('university'),
            'faculty' => $request->input('faculty'),
            'department' => $request->input('department'),
            'researcher' => $request->input('researcher'),
            'research_area' => $request->input('research_area'),
            'year' => $request->input('year'),
            'grade' => $request->input('grade'),
            'publication_type' => $request->input('publication_type'),
            'indexed' => $request->input('indexed'),
            'language' => $request->input('language'),
        ];
    }

    protected function getEffectiveUniversityId()
    {
        $user = auth()->user();
        if (!$user) return null;

        if ($user->role === 'ministry_authority') {
            return null;
        }
        return $user->university_id;
    }

    /**
     * Apply common filters to a query builder.
     * Assumes the query has joined: lecturers, faculties, departments.
     */
    protected function applyCommonFilters($query, $filters, $universityId)
    {
        if ($universityId) {
            $query->where('faculties.university_id', $universityId);
        } elseif (auth()->user() && auth()->user()->role === 'ministry_authority' && $filters['university']) {
            $query->where('faculties.university_id', $filters['university']);
        }

        if ($filters['faculty']) {
            $query->where('faculties.id', $filters['faculty']);
        }
        if ($filters['department']) {
            $query->where('departments.id', $filters['department']);
        }
        if ($filters['researcher']) {
            $query->where('lecturers.id', $filters['researcher']);
        }
        if ($filters['year']) {
            $query->where('academic_papers.year', $filters['year']);
        }
        if ($filters['grade']) {
            $query->where('lecturers.grade', $filters['grade']);
        }
        if ($filters['publication_type']) {
            $query->where('academic_papers.publication', $filters['publication_type']);
        }
        if ($filters['indexed']) {
            $query->where('academic_papers.indexed', $filters['indexed']);
        }
        if ($filters['language']) {
            $query->where('academic_papers.language', $filters['language']);
        }
        if ($filters['research_area']) {
            $query->whereJsonContains('lecturers.specialized_area', $filters['research_area']);
        }
    }

    protected function buildPaperQuery($filters, $universityId)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

        $this->applyCommonFilters($query, $filters, $universityId);
        return $query;
    }

    /**
     * Extract research areas from specialized_area JSON and aggregate.
     */
    protected function extractAreasAndAggregate($papers)
    {
        $areaData = [];

        foreach ($papers as $paper) {
            $areas = json_decode($paper->specialized_area, true) ?? [];
            if (empty($areas)) {
                $areas = ['Not Specified'];
            }

            foreach ($areas as $rawArea) {
                $area = trim($rawArea);
                if (empty($area)) continue;
                $area = ucwords(strtolower($area));

                if (!isset($areaData[$area])) {
                    $areaData[$area] = [
                        'publications' => 0,
                        'citations' => 0,
                        'researchers' => [],
                        'years' => [],
                        'faculties' => [],
                        'departments' => [],
                        'papers' => [],
                        'q1_count' => 0,
                        'indexed_count' => 0,
                    ];
                }

                $areaData[$area]['publications']++;
                $areaData[$area]['citations'] += $paper->citation ?? 0;
                $areaData[$area]['researchers'][$paper->lecturer_id] = true;
                if ($paper->year) {
                    $areaData[$area]['years'][$paper->year] = ($areaData[$area]['years'][$paper->year] ?? 0) + 1;
                }
                if ($paper->faculty_id) {
                    $areaData[$area]['faculties'][$paper->faculty_id] = [
                        'name' => $paper->faculty_name,
                        'count' => ($areaData[$area]['faculties'][$paper->faculty_id]['count'] ?? 0) + 1,
                    ];
                }
                if ($paper->department_id) {
                    $areaData[$area]['departments'][$paper->department_id] = [
                        'name' => $paper->department_name,
                        'count' => ($areaData[$area]['departments'][$paper->department_id]['count'] ?? 0) + 1,
                    ];
                }
                $areaData[$area]['papers'][] = $paper->id;

                if ($paper->indexed) {
                    $areaData[$area]['indexed_count']++;
                    if (strtoupper($paper->indexed) === 'Q1') {
                        $areaData[$area]['q1_count']++;
                    }
                }
            }
        }

        foreach ($areaData as &$data) {
            $data['researchers'] = count($data['researchers']);
            $data['years'] = $data['years'];
            $data['faculties'] = array_values($data['faculties']);
            $data['departments'] = array_values($data['departments']);
            $data['papers'] = count($data['papers']);
        }

        uasort($areaData, fn($a, $b) => $b['publications'] - $a['publications']);
        return $areaData;
    }

    protected function calculateKpis($papers, $areaData)
    {
        $totalPublications = $papers->count();
        $totalCitations = $papers->sum('citation');
        $totalResearchers = $papers->unique('lecturer_id')->count();
        $totalAreas = count($areaData);
        $avgCitations = $totalPublications > 0 ? round($totalCitations / $totalPublications, 2) : 0;

        $q1Count = $papers->filter(function ($p) {
            return $p->indexed && strtoupper($p->indexed) === 'Q1';
        })->count();

        $citedPublications = $papers->filter(function ($p) {
            return ($p->citation ?? 0) > 0;
        })->count();

        $citedPercent = $totalPublications > 0 ? round(($citedPublications / $totalPublications) * 100, 1) : 0;

        return [
            'total_areas' => $totalAreas,
            'total_publications' => $totalPublications,
            'total_citations' => $totalCitations,
            'total_researchers' => $totalResearchers,
            'avg_citations' => $avgCitations,
            'q1_publications' => $q1Count,
            'cited_publications' => $citedPublications,
            'cited_percent' => $citedPercent,
        ];
    }

    protected function buildAreaOverview($areaData, $papers)
    {
        $overview = [];
        foreach ($areaData as $area => $data) {
            $avg = $data['publications'] > 0 ? round($data['citations'] / $data['publications'], 2) : 0;
            $q1Percent = $data['indexed_count'] > 0 ? round(($data['q1_count'] / $data['indexed_count']) * 100, 1) : 0;
            $share = $papers->count() > 0 ? round(($data['publications'] / $papers->count()) * 100, 1) : 0;
            $overview[] = [
                'area' => $area,
                'publications' => $data['publications'],
                'researchers' => $data['researchers'],
                'citations' => $data['citations'],
                'avg_citations' => $avg,
                'q1' => $data['q1_count'],
                'q1_percent' => $q1Percent,
                'share_percent' => $share,
            ];
        }
        return $overview;
    }

    protected function buildDistribution($areaData)
    {
        $total = array_sum(array_column($areaData, 'publications'));
        $dist = [];
        foreach ($areaData as $area => $data) {
            $dist[] = [
                'area' => $area,
                'publications' => $data['publications'],
                'percentage' => $total > 0 ? round(($data['publications'] / $total) * 100, 1) : 0,
            ];
        }
        return $dist;
    }

    protected function getTopAreas($areaData, $metric, $limit = 10)
    {
        $sorted = $areaData;
        uasort($sorted, fn($a, $b) => $b[$metric] - $a[$metric]);
        $top = array_slice($sorted, 0, $limit, true);
        $result = [];
        foreach ($top as $area => $data) {
            $result[] = [
                'area' => $area,
                'value' => $data[$metric],
                'publications' => $data['publications'],
                'citations' => $data['citations'],
                'researchers' => $data['researchers'],
            ];
        }
        return $result;
    }

    protected function buildTrend($areaData)
    {
        $allYears = [];
        foreach ($areaData as $data) {
            foreach (array_keys($data['years']) as $year) {
                $allYears[$year] = true;
            }
        }
        ksort($allYears);
        $years = array_keys($allYears);

        $trend = [];
        foreach ($years as $year) {
            $row = ['year' => $year];
            foreach ($areaData as $area => $data) {
                $row[$area] = $data['years'][$year] ?? 0;
            }
            $trend[] = $row;
        }
        return $trend;
    }

    protected function buildFacultyBreakdown($areaData)
    {
        $breakdown = [];
        foreach ($areaData as $area => $data) {
            foreach ($data['faculties'] as $fac) {
                $key = $fac['name'];
                if (!isset($breakdown[$key])) {
                    $breakdown[$key] = [];
                }
                $breakdown[$key][$area] = ($breakdown[$key][$area] ?? 0) + $fac['count'];
            }
        }
        return $breakdown;
    }

    protected function buildDepartmentBreakdown($areaData)
    {
        $breakdown = [];
        foreach ($areaData as $area => $data) {
            foreach ($data['departments'] as $dept) {
                $key = $dept['name'];
                if (!isset($breakdown[$key])) {
                    $breakdown[$key] = [];
                }
                $breakdown[$key][$area] = ($breakdown[$key][$area] ?? 0) + $dept['count'];
            }
        }
        return $breakdown;
    }

    /**
     * Get top researchers for a specific research area.
     * Fully implemented with proper joins and filters.
     */
    protected function getTopResearchersByArea($areaData, $filters, $universityId, $selectedArea = null)
    {
        if (!$selectedArea || !isset($areaData[$selectedArea])) {
            return [];
        }

        // Main query: get researchers with papers in this area
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published')
            ->whereJsonContains('lecturers.specialized_area', $selectedArea);

        $this->applyCommonFilters($query, $filters, $universityId);

        $results = $query->select(
            'lecturers.id',
            'lecturers.lecturername',
            'faculties.facultyname as faculty',
            'departments.deptname as department',
            DB::raw('COUNT(*) as publications'),
            DB::raw('SUM(academic_papers.citation) as citations'),
            DB::raw('SUM(CASE WHEN academic_papers.citation > 0 THEN 1 ELSE 0 END) as cited_pubs'),
            DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q1" THEN 1 ELSE 0 END) as q1_pubs')
        )
        ->groupBy('lecturers.id', 'lecturers.lecturername', 'faculties.facultyname', 'departments.deptname')
        ->get();

        $top = [];
        foreach ($results as $r) {
            $pubs = (int) $r->publications;
            if ($pubs === 0) continue;
            $cits = (int) $r->citations;
            $avg = round($cits / $pubs, 2);
            $q1Share = round(($r->q1_pubs / $pubs) * 100, 1);
            $citedRate = round(($r->cited_pubs / $pubs) * 100, 1);

            // Calculate h-index for this researcher (only papers in this area)
            // Must join faculties and departments to apply university scope and faculty filters
            $hQuery = DB::table('academic_papers')
                ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
                ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
                ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
                ->where('lecturers.id', $r->id)
                ->whereJsonContains('lecturers.specialized_area', $selectedArea)
                ->whereNull('academic_papers.deleted_at')
                ->where('academic_papers.status', 'Published');

            // Apply filters (except research_area to avoid duplication)
            $filterCopy = $filters;
            $filterCopy['research_area'] = null;
            $this->applyCommonFilters($hQuery, $filterCopy, $universityId);

            $citationsList = $hQuery->pluck('academic_papers.citation')->filter()->sortDesc()->values();
            $hIndex = 0;
            foreach ($citationsList as $i => $cit) {
                if ($cit >= $i + 1) $hIndex = $i + 1;
                else break;
            }

            $top[] = [
                'researcher_id' => $r->id,
                'name' => $r->lecturername,
                'faculty' => $r->faculty,
                'department' => $r->department ?? '-',
                'publications' => $pubs,
                'citations' => $cits,
                'avg_citations' => $avg,
                'h_index' => $hIndex,
                'q1' => (int) $r->q1_pubs,
                'q1_share' => $q1Share,
                'cited_rate' => $citedRate,
            ];
        }

        usort($top, fn($a, $b) => $b['citations'] - $a['citations']);
        return array_slice($top, 0, 20);
    }

    protected function generateInsights($areaData, $papers, $filters)
    {
        $insights = [];
        $total = $papers->count();
        if ($total == 0) return ['No data available.'];

        if (!empty($areaData)) {
            $sorted = $areaData;
            uasort($sorted, fn($a, $b) => $b['publications'] - $a['publications']);
            $topPub = array_keys($sorted)[0];
            $insights[] = "{$topPub} is the largest research area with {$sorted[$topPub]['publications']} publications ({$sorted[$topPub]['publications']}/{$total} publications).";
        }

        $sorted = $areaData;
        uasort($sorted, fn($a, $b) => $b['citations'] - $a['citations']);
        if (!empty($sorted)) {
            $topCit = array_keys($sorted)[0];
            $insights[] = "{$topCit} has the highest citation count with {$sorted[$topCit]['citations']} citations.";
        }

        $sorted = $areaData;
        uasort($sorted, fn($a, $b) => 
            ($b['publications'] > 0 ? $b['citations'] / $b['publications'] : 0) - 
            ($a['publications'] > 0 ? $a['citations'] / $a['publications'] : 0)
        );
        if (!empty($sorted)) {
            $topAvg = array_keys($sorted)[0];
            $avg = round($sorted[$topAvg]['citations'] / $sorted[$topAvg]['publications'], 2);
            $insights[] = "{$topAvg} has the highest average citations per publication ({$avg}).";
        }

        $sorted = $areaData;
        uasort($sorted, fn($a, $b) => 
            ($b['indexed_count'] > 0 ? $b['q1_count'] / $b['indexed_count'] : 0) - 
            ($a['indexed_count'] > 0 ? $a['q1_count'] / $a['indexed_count'] : 0)
        );
        if (!empty($sorted)) {
            $topQ1 = array_keys($sorted)[0];
            $q1pct = round(($sorted[$topQ1]['q1_count'] / $sorted[$topQ1]['indexed_count']) * 100, 1);
            $insights[] = "{$topQ1} has the highest Q1 percentage ({$q1pct}%).";
        }

        $citedPubs = $papers->filter(fn($p) => ($p->citation ?? 0) > 0)->count();
        $citedPct = round(($citedPubs / $total) * 100, 1);
        $insights[] = "{$citedPct}% of publications have received at least one citation.";

        if ($filters['year']) {
            $insights[] = "Data is filtered by year: {$filters['year']}.";
        }

        return array_slice($insights, 0, 6);
    }

    public function areas()
    {
        $areas = DB::table('lecturers')
            ->whereNotNull('specialized_area')
            ->where('specialized_area', '!=', '')
            ->pluck('specialized_area');

        $unique = [];
        foreach ($areas as $json) {
            $arr = json_decode($json, true) ?: [];
            foreach ($arr as $item) {
                $unique[trim($item)] = true;
            }
        }
        ksort($unique);
        return response()->json(array_keys($unique));
    }

    public function previewPdf(Request $request)
    {
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId();

        $paperQuery = $this->buildPaperQuery($filters, $universityId);
        $papers = $paperQuery->get([
            'academic_papers.id',
            'academic_papers.citation',
            'academic_papers.year',
            'academic_papers.indexed',
            'academic_papers.publication',
            'academic_papers.collaboration',
            'academic_papers.language',
            'academic_papers.funding',
            'academic_papers.status',
            'lecturers.id as lecturer_id',
            'lecturers.specialized_area',
            'faculties.id as faculty_id',
            'faculties.facultyname as faculty_name',
            'departments.id as department_id',
            'departments.deptname as department_name',
        ]);

        $areaData = $this->extractAreasAndAggregate($papers);
        $kpis = $this->calculateKpis($papers, $areaData);
        $areaOverview = $this->buildAreaOverview($areaData, $papers);
        $distribution = $this->buildDistribution($areaData);
        $topByPublications = $this->getTopAreas($areaData, 'publications', 10);
        $topByCitations = $this->getTopAreas($areaData, 'citations', 10);
        $trend = $this->buildTrend($areaData);
        $facultyBreakdown = $this->buildFacultyBreakdown($areaData);
        $departmentBreakdown = $this->buildDepartmentBreakdown($areaData);
        $topResearchers = $this->getTopResearchersByArea(
            $areaData,
            $filters,
            $universityId,
            $filters['research_area'] ?? null
        );
        $insights = $this->generateInsights($areaData, $papers, $filters);

        $date = now()->format('Y-m-d');

        $html = view('pdf.research-area-analytics', [
            'kpis' => $kpis,
            'area_overview' => $areaOverview,
            'distribution' => $distribution,
            'top_by_publications' => $topByPublications,
            'top_by_citations' => $topByCitations,
            'trend' => $trend,
            'faculty_breakdown' => $facultyBreakdown,
            'department_breakdown' => $departmentBreakdown,
            'top_researchers' => $topResearchers,
            'insights' => $insights,
            'filters' => $filters,
            'date' => $date,
            'is_ministry' => Auth::user() && Auth::user()->role === 'ministry_authority',
            'university_id' => $universityId,
        ])->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'fontDir' => array_merge((new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [
                storage_path('fonts'),
            ]),
            'fontdata' => [
                'bahij_nazanin' => [
                    'R' => 'Bahij_Nazanin-Regular.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ]
            ],
            'default_font' => 'bahij_nazanin',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'directionality' => 'ltr',
        ]);

        $mpdf->WriteHTML($html);
        return response($mpdf->Output('research-area-analytics.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}