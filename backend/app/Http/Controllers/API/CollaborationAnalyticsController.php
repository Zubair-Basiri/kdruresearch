<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AcademicPaper;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CollaborationAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId($request);

        $baseQuery = $this->buildBaseQuery($filters, $universityId);

        $summary = $this->getSummary($baseQuery);
        $trend = $this->getTrend($baseQuery);
        $collabTypes = $this->getCollaborationTypes($baseQuery);
        $universityComparison = $this->getUniversityComparison($filters, $universityId);
        $facultyCollab = $this->getFacultyCollaboration($filters, $universityId);
        $departmentCollab = $this->getDepartmentCollaboration($filters, $universityId);
        $researcherCollab = $this->getResearcherCollaboration($filters, $universityId);
        $researchAreaCollab = $this->getResearchAreaCollaboration($filters, $universityId);
        $topPublications = $this->getTopCollaborativePublications($baseQuery);
        $keyFindings = $this->generateKeyFindings($summary, $collabTypes, $trend, $universityComparison);

        return response()->json([
            'summary' => $summary,
            'trend' => $trend,
            'collaboration_types' => $collabTypes,
            'university_comparison' => $universityComparison,
            'faculty_collaboration' => $facultyCollab,
            'department_collaboration' => $departmentCollab,
            'researcher_collaboration' => $researcherCollab,
            'research_area_collaboration' => $researchAreaCollab,
            'top_publications' => $topPublications,
            'key_findings' => $keyFindings,
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
            'year' => $request->input('year'),
            'publication_type' => $request->input('publication_type'),
            'indexed' => $request->input('indexed'),
            'language' => $request->input('language'),
            'collaboration_type' => $request->input('collaboration_type'),
            'grade' => $request->input('grade'),
        ];
    }

    protected function getEffectiveUniversityId(Request $request)
    {
        $user = Auth::user();
        if (!$user) return null;

        if ($user->role === 'ministry_authority') {
            return $request->input('university') ? (int) $request->input('university') : null;
        }

        return $user->university_id;
    }

    protected function buildBaseQuery($filters, $universityId)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

        if ($universityId) {
            $query->where('faculties.university_id', $universityId);
        } elseif (Auth::user() && Auth::user()->role === 'ministry_authority' && $filters['university']) {
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
        if ($filters['publication_type']) {
            $query->where('academic_papers.publication', $filters['publication_type']);
        }
        if ($filters['indexed']) {
            $query->where('academic_papers.indexed', $filters['indexed']);
        }
        if ($filters['language']) {
            $query->where('academic_papers.language', $filters['language']);
        }
        if ($filters['grade']) {
            $query->where('lecturers.grade', $filters['grade']);
        }

        return $query;
    }

    protected function getSummary($query)
    {
        $totalPubs = (int) $query->clone()->count();
        $collabPubs = (int) $query->clone()
            ->whereNotNull('academic_papers.collaboration')
            ->where('academic_papers.collaboration', '!=', '')
            ->count();

        $collabRate = $totalPubs > 0 ? round(($collabPubs / $totalPubs) * 100, 1) : 0;

        $collabCitations = (int) $query->clone()
            ->whereNotNull('academic_papers.collaboration')
            ->where('academic_papers.collaboration', '!=', '')
            ->sum('academic_papers.citation') ?: 0;

        $avgCollabCitations = $collabPubs > 0 ? round($collabCitations / $collabPubs, 2) : 0;

        $totalResearchers = (int) $query->clone()->distinct('lecturers.id')->count('lecturers.id');
        $collabResearchers = (int) $query->clone()
            ->whereNotNull('academic_papers.collaboration')
            ->where('academic_papers.collaboration', '!=', '')
            ->distinct('lecturers.id')
            ->count('lecturers.id');

        $totalCitations = (int) $query->clone()->sum('academic_papers.citation') ?: 0;

        return [
            'total_publications' => $totalPubs,
            'collaborative_publications' => $collabPubs,
            'non_collaborative_publications' => $totalPubs - $collabPubs,
            'collaboration_rate' => $collabRate,
            'total_citations' => $totalCitations,
            'collaborative_citations' => $collabCitations,
            'average_collaborative_citations' => $avgCollabCitations,
            'total_researchers' => $totalResearchers,
            'collaborative_researchers' => $collabResearchers,
        ];
    }

    protected function getTrend($query)
    {
        $results = $query->clone()
            ->select(
                'academic_papers.year',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN academic_papers.collaboration IS NOT NULL AND academic_papers.collaboration != "" THEN 1 ELSE 0 END) as collaborative')
            )
            ->whereNotNull('academic_papers.year')
            ->groupBy('academic_papers.year')
            ->orderBy('academic_papers.year', 'asc')
            ->get();

        $trend = [];
        foreach ($results as $row) {
            $rate = $row->total > 0 ? round(($row->collaborative / $row->total) * 100, 1) : 0;
            $trend[] = [
                'year' => $row->year,
                'total' => (int) $row->total,
                'collaborative' => (int) $row->collaborative,
                'rate' => $rate,
            ];
        }
        return $trend;
    }

    protected function getCollaborationTypes($query)
    {
        $types = $query->clone()
            ->whereNotNull('academic_papers.collaboration')
            ->where('academic_papers.collaboration', '!=', '')
            ->select('academic_papers.collaboration', DB::raw('COUNT(*) as count'))
            ->groupBy('academic_papers.collaboration')
            ->get();

        $total = $types->sum('count');
        $result = [];
        foreach ($types as $row) {
            $result[] = [
                'type' => $row->collaboration,
                'count' => (int) $row->count,
                'percentage' => $total > 0 ? round(($row->count / $total) * 100, 1) : 0,
            ];
        }
        return $result;
    }

    protected function getUniversityComparison($filters, $universityId)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'ministry_authority' || $universityId !== null) {
            return [];
        }

        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->join('universities', 'faculties.university_id', '=', 'universities.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

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
        if ($filters['publication_type']) {
            $query->where('academic_papers.publication', $filters['publication_type']);
        }
        if ($filters['indexed']) {
            $query->where('academic_papers.indexed', $filters['indexed']);
        }
        if ($filters['language']) {
            $query->where('academic_papers.language', $filters['language']);
        }
        if ($filters['grade']) {
            $query->where('lecturers.grade', $filters['grade']);
        }

        $results = $query->select(
            'universities.id as university_id',
            'universities.name as university_name',
            DB::raw('COUNT(*) as publications'),
            DB::raw('SUM(CASE WHEN academic_papers.collaboration IS NOT NULL AND academic_papers.collaboration != "" THEN 1 ELSE 0 END) as collaborative'),
            DB::raw('SUM(academic_papers.citation) as citations')
        )
        ->groupBy('universities.id', 'universities.name')
        ->get();

        $comparison = [];
        foreach ($results as $row) {
            $rate = $row->publications > 0 ? round(($row->collaborative / $row->publications) * 100, 1) : 0;
            $avg = $row->collaborative > 0 ? round($row->citations / $row->collaborative, 2) : 0;
            $comparison[] = [
                'university_id' => $row->university_id,
                'university_name' => $row->university_name,
                'publications' => (int) $row->publications,
                'collaborative' => (int) $row->collaborative,
                'rate' => $rate,
                'citations' => (int) $row->citations,
                'avg_citations' => $avg,
            ];
        }

        usort($comparison, fn($a, $b) => $b['rate'] <=> $a['rate']);
        return $comparison;
    }

    protected function getFacultyCollaboration($filters, $universityId)
    {
        $query = Faculty::query();
        if ($universityId) {
            $query->where('university_id', $universityId);
        }
        $faculties = $query->get();

        $result = [];
        foreach ($faculties as $fac) {
            $paperQuery = $this->buildPaperQueryForEntity($fac->id, 'faculty', $filters, $universityId);
            $data = $this->getEntityCollaborationMetrics($paperQuery);
            if ($data['publications'] > 0) {
                $result[] = [
                    'entity_name' => $fac->facultyname,
                    'entity_id' => $fac->id,
                    'publications' => $data['publications'],
                    'collaborative' => $data['collaborative'],
                    'rate' => $data['rate'],
                    'researchers' => $data['researchers'],
                    'collaborative_researchers' => $data['collaborative_researchers'],
                    'citations' => $data['citations'],
                    'avg_citations' => $data['avg_citations'],
                ];
            }
        }

        usort($result, fn($a, $b) => $b['rate'] <=> $a['rate']);
        return $result;
    }

    protected function getDepartmentCollaboration($filters, $universityId)
    {
        $query = Department::query();
        if ($universityId) {
            $query->where('university_id', $universityId);
        }
        $departments = $query->get();

        $result = [];
        foreach ($departments as $dept) {
            $paperQuery = $this->buildPaperQueryForEntity($dept->id, 'department', $filters, $universityId);
            $data = $this->getEntityCollaborationMetrics($paperQuery);
            if ($data['publications'] > 0) {
                $result[] = [
                    'entity_name' => $dept->deptname,
                    'entity_id' => $dept->id,
                    'publications' => $data['publications'],
                    'collaborative' => $data['collaborative'],
                    'rate' => $data['rate'],
                    'researchers' => $data['researchers'],
                    'collaborative_researchers' => $data['collaborative_researchers'],
                    'citations' => $data['citations'],
                    'avg_citations' => $data['avg_citations'],
                ];
            }
        }

        usort($result, fn($a, $b) => $b['rate'] <=> $a['rate']);
        return $result;
    }

    protected function getResearcherCollaboration($filters, $universityId)
    {
        $query = Lecturer::query();
        if ($universityId) {
            $query->whereHas('faculty', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }
        if ($filters['faculty']) {
            $query->where('faculty_id', $filters['faculty']);
        }
        if ($filters['department']) {
            $query->where('department_id', $filters['department']);
        }
        if ($filters['grade']) {
            $query->where('grade', $filters['grade']);
        }

        $researchers = $query->get();
        $result = [];
        foreach ($researchers as $res) {
            $paperQuery = $this->buildPaperQueryForEntity($res->id, 'researcher', $filters, $universityId);
            $data = $this->getEntityCollaborationMetrics($paperQuery);
            if ($data['publications'] > 0) {
                $result[] = [
                    'researcher_name' => $res->lecturername,
                    'researcher_id' => $res->id,
                    'publications' => $data['publications'],
                    'collaborative' => $data['collaborative'],
                    'rate' => $data['rate'],
                    'citations' => $data['citations'],
                    'avg_citations' => $data['avg_citations'],
                ];
            }
        }

        usort($result, fn($a, $b) => $b['rate'] <=> $a['rate']);
        return $result;
    }

    protected function getResearchAreaCollaboration($filters, $universityId)
    {
        $paperQuery = $this->buildBaseQuery($filters, $universityId);
        $papers = $paperQuery->get(['lecturers.specialized_area', 'academic_papers.collaboration', 'academic_papers.citation', 'academic_papers.id']);

        $areaData = [];
        foreach ($papers as $paper) {
            $areas = json_decode($paper->specialized_area, true) ?: ['Not Specified'];
            foreach ($areas as $area) {
                $area = trim($area);
                if (empty($area)) continue;
                $area = ucwords(strtolower($area));
                if (!isset($areaData[$area])) {
                    $areaData[$area] = [
                        'publications' => 0,
                        'collaborative' => 0,
                        'citations' => 0,
                    ];
                }
                $areaData[$area]['publications']++;
                if ($paper->collaboration && $paper->collaboration != '') {
                    $areaData[$area]['collaborative']++;
                }
                $areaData[$area]['citations'] += $paper->citation ?? 0;
            }
        }

        $result = [];
        foreach ($areaData as $area => $data) {
            $rate = $data['publications'] > 0 ? round(($data['collaborative'] / $data['publications']) * 100, 1) : 0;
            $avg = $data['collaborative'] > 0 ? round($data['citations'] / $data['collaborative'], 2) : 0;
            $result[] = [
                'area' => $area,
                'publications' => $data['publications'],
                'collaborative' => $data['collaborative'],
                'rate' => $rate,
                'citations' => $data['citations'],
                'avg_citations' => $avg,
            ];
        }

        usort($result, fn($a, $b) => $b['rate'] <=> $a['rate']);
        return $result;
    }

    protected function buildPaperQueryForEntity($entityId, $entityType, $filters, $universityId)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

        if ($entityType === 'faculty') {
            $query->where('faculties.id', $entityId);
        } elseif ($entityType === 'department') {
            $query->where('departments.id', $entityId);
        } elseif ($entityType === 'researcher') {
            $query->where('lecturers.id', $entityId);
        }

        if ($universityId) {
            $query->where('faculties.university_id', $universityId);
        }

        if ($filters['year']) {
            $query->where('academic_papers.year', $filters['year']);
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
        if ($filters['grade']) {
            $query->where('lecturers.grade', $filters['grade']);
        }

        return $query;
    }

    protected function getEntityCollaborationMetrics($query)
    {
        $totalPubs = (int) $query->clone()->count();
        $collabPubs = (int) $query->clone()
            ->whereNotNull('academic_papers.collaboration')
            ->where('academic_papers.collaboration', '!=', '')
            ->count();
        $rate = $totalPubs > 0 ? round(($collabPubs / $totalPubs) * 100, 1) : 0;
        $researchers = (int) $query->clone()->distinct('lecturers.id')->count('lecturers.id');
        $collabResearchers = (int) $query->clone()
            ->whereNotNull('academic_papers.collaboration')
            ->where('academic_papers.collaboration', '!=', '')
            ->distinct('lecturers.id')
            ->count('lecturers.id');
        $citations = (int) $query->clone()->sum('academic_papers.citation') ?: 0;
        $avgCitations = $collabPubs > 0 ? round($citations / $collabPubs, 2) : 0;

        return [
            'publications' => $totalPubs,
            'collaborative' => $collabPubs,
            'rate' => $rate,
            'researchers' => $researchers,
            'collaborative_researchers' => $collabResearchers,
            'citations' => $citations,
            'avg_citations' => $avgCitations,
        ];
    }

    protected function getTopCollaborativePublications($query)
    {
        $top = $query->clone()
            ->select(
                'academic_papers.id',
                'academic_papers.title',
                'academic_papers.year',
                'faculties.facultyname as faculty',
                'departments.deptname as department',
                'lecturers.lecturername as researcher',
                'academic_papers.collaboration as collaboration_type',
                'academic_papers.citation'
            )
            ->whereNotNull('academic_papers.collaboration')
            ->where('academic_papers.collaboration', '!=', '')
            ->orderBy('academic_papers.citation', 'desc')
            ->limit(20)
            ->get();

        $result = [];
        foreach ($top as $paper) {
            $result[] = [
                'id' => $paper->id,
                'title' => $paper->title,
                'year' => $paper->year,
                'faculty' => $paper->faculty,
                'department' => $paper->department ?? '-',
                'researcher' => $paper->researcher,
                'collaboration_type' => $paper->collaboration_type,
                'citations' => (int) $paper->citation,
            ];
        }
        return $result;
    }

    protected function generateKeyFindings($summary, $collabTypes, $trend, $universityComparison)
    {
        $findings = [];

        $rate = $summary['collaboration_rate'];
        $findings[] = "Collaboration accounts for {$rate}% of publications in the selected scope.";

        if (count($trend) >= 2) {
            $last = end($trend);
            $prev = $trend[count($trend) - 2];
            if ($prev['rate'] > 0) {
                $change = round((($last['rate'] - $prev['rate']) / $prev['rate']) * 100, 1);
                $direction = $change >= 0 ? 'increased' : 'decreased';
                $findings[] = "Collaboration rate has {$direction} by " . abs($change) . "% compared with the previous year.";
            }
        }

        if (!empty($collabTypes)) {
            $topType = $collabTypes[0];
            $findings[] = "The most common collaboration type is {$topType['type']} ({$topType['percentage']}%).";
        }

        if (!empty($universityComparison)) {
            $topUni = $universityComparison[0];
            $findings[] = "{$topUni['university_name']} has the highest collaboration rate ({$topUni['rate']}%).";
        }

        $avgCollab = $summary['average_collaborative_citations'];
        $totalAvg = $summary['total_publications'] > 0 ? round($summary['total_citations'] / $summary['total_publications'], 2) : 0;
        if ($avgCollab > $totalAvg) {
            $findings[] = "Collaborative publications have a higher average citation count than non-collaborative publications.";
        }

        return array_slice($findings, 0, 5);
    }

    // ---------- Additional Endpoint: Collaboration Types ----------
    public function collaborationTypes()
    {
        $types = DB::table('academic_papers')
            ->select('collaboration')
            ->distinct()
            ->whereNotNull('collaboration')
            ->where('collaboration', '!=', '')
            ->pluck('collaboration')
            ->toArray();
        return response()->json($types);
    }

    public function previewPdf(Request $request)
    {
        // Reuse the index logic
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId($request);
        $baseQuery = $this->buildBaseQuery($filters, $universityId);

        $summary = $this->getSummary($baseQuery);
        $trend = $this->getTrend($baseQuery);
        $collabTypes = $this->getCollaborationTypes($baseQuery);
        $universityComparison = $this->getUniversityComparison($filters, $universityId);
        $facultyCollab = $this->getFacultyCollaboration($filters, $universityId);
        $departmentCollab = $this->getDepartmentCollaboration($filters, $universityId);
        $researcherCollab = $this->getResearcherCollaboration($filters, $universityId);
        $researchAreaCollab = $this->getResearchAreaCollaboration($filters, $universityId);
        $topPublications = $this->getTopCollaborativePublications($baseQuery);
        $keyFindings = $this->generateKeyFindings($summary, $collabTypes, $trend, $universityComparison);

        $date = now()->format('Y-m-d');

        $html = view('pdf.collaboration-analysis', [
            'summary' => $summary,
            'trend' => $trend,
            'collab_types' => $collabTypes,
            'university_comparison' => $universityComparison,
            'faculty_collab' => $facultyCollab,
            'department_collab' => $departmentCollab,
            'researcher_collab' => $researcherCollab,
            'research_area_collab' => $researchAreaCollab,
            'top_publications' => $topPublications,
            'key_findings' => $keyFindings,
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
        return response($mpdf->Output('collaboration-analysis.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}