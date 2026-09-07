<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\University;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Lecturer;

class BenchmarkingController extends Controller
{
    /**
     * Get benchmarking data.
     * level = university | faculty | department | researcher
     */
    public function index(Request $request)
    {
        $level = $request->input('level', 'university');
        $primaryId = $request->input('primary_id');
        $peerIds = $request->input('peer_ids', '');

        // Convert peer_ids to array
        if (is_string($peerIds)) {
            $peerIds = array_filter(explode(',', $peerIds));
        } elseif (!is_array($peerIds)) {
            $peerIds = [];
        }

        if ($primaryId) {
            $primaryId = (int) $primaryId;
        }

        $filters = $this->parseFilters($request);
        $universityId = currentUniversityId();

        $entities = $this->getEntities($level, $primaryId, $peerIds, $universityId);
        if (empty($entities)) {
            return response()->json(['message' => 'No entities selected'], 422);
        }

        // Calculate metrics for each entity
        $metrics = [];
        foreach ($entities as $entity) {
            $metrics[$entity['id']] = $this->calculateMetrics($entity, $filters, $universityId);
        }

        $primaryKey = $primaryId ?? $entities[0]['id'];
        $primaryMetrics = $metrics[$primaryKey] ?? null;

        $peerMetrics = [];
        foreach ($entities as $entity) {
            if ($entity['id'] != $primaryKey) {
                $peerMetrics[] = [
                    'entity' => $entity,
                    'metrics' => $metrics[$entity['id']],
                ];
            }
        }

        $peerAverage = $this->calculatePeerAverage($peerMetrics);
        $ranking = $this->buildRanking($metrics, $entities);
        $comparison = $this->buildComparisonMatrix($primaryMetrics, $peerAverage, $peerMetrics);
        $insights = $this->generateInsights($primaryMetrics, $peerAverage, $comparison);

        $internalBenchmark = [];
        if ($level !== 'university') {
            $internalBenchmark = $this->getInternalBenchmark($level, $filters, $universityId);
        }

        return response()->json([
            'level' => $level,
            'primary' => $primaryMetrics,
            'peers' => $peerMetrics,
            'peer_average' => $peerAverage,
            'ranking' => $ranking,
            'comparison' => $comparison,
            'insights' => $insights,
            'internal_benchmark' => $internalBenchmark,
            'available_levels' => $this->getAvailableLevels($universityId),
        ]);
    }

    // ---------- Helper Methods ----------

    protected function parseFilters(Request $request)
    {
        return [
            'year' => $request->input('year'),
            'faculty' => $request->input('faculty'),
            'department' => $request->input('department'),
            'researcher' => $request->input('researcher'),
            'grade' => $request->input('grade'),
            'publication_type' => $request->input('publication_type'),
            'indexed' => $request->input('indexed'),
            'language' => $request->input('language'),
        ];
    }

    protected function getEntities($level, $primaryId, $peerIds, $universityId)
    {
        $entities = [];

        if ($level === 'university') {
            $query = University::query();
            if ($universityId) {
                $query->where('id', $universityId);
            }
            // If primaryId is not set, use the user's university (or the first available)
            if (!$primaryId && $universityId) {
                $primaryId = $universityId;
            }
            if ($primaryId) {
                $query->orWhere('id', $primaryId);
            }
            if (!empty($peerIds)) {
                $query->orWhereIn('id', $peerIds);
            }
            $results = $query->get();
            foreach ($results as $uni) {
                $entities[] = ['id' => $uni->id, 'name' => $uni->name, 'type' => 'university'];
            }
        } elseif ($level === 'faculty') {
            $query = Faculty::query();
            if ($universityId) {
                $query->where('university_id', $universityId);
            }
            if ($primaryId) {
                $query->orWhere('id', $primaryId);
            }
            if (!empty($peerIds)) {
                $query->orWhereIn('id', $peerIds);
            }
            $results = $query->get();
            foreach ($results as $fac) {
                $entities[] = ['id' => $fac->id, 'name' => $fac->facultyname, 'type' => 'faculty'];
            }
        } elseif ($level === 'department') {
            $query = Department::query();
            if ($universityId) {
                $query->where('university_id', $universityId);
            }
            if ($primaryId) {
                $query->orWhere('id', $primaryId);
            }
            if (!empty($peerIds)) {
                $query->orWhereIn('id', $peerIds);
            }
            $results = $query->get();
            foreach ($results as $dept) {
                $entities[] = ['id' => $dept->id, 'name' => $dept->deptname, 'type' => 'department'];
            }
        } elseif ($level === 'researcher') {
            $query = Lecturer::query();
            if ($universityId) {
                $query->whereHas('faculty', function ($q) use ($universityId) {
                    $q->where('university_id', $universityId);
                });
            }
            if ($primaryId) {
                $query->orWhere('id', $primaryId);
            }
            if (!empty($peerIds)) {
                $query->orWhereIn('id', $peerIds);
            }
            $results = $query->get();
            foreach ($results as $lec) {
                $entities[] = ['id' => $lec->id, 'name' => $lec->lecturername, 'type' => 'researcher'];
            }
        }

        // Remove duplicates
        $unique = [];
        foreach ($entities as $e) {
            $key = $e['type'] . '_' . $e['id'];
            if (!isset($unique[$key])) {
                $unique[$key] = $e;
            }
        }
        return array_values($unique);
    }

    protected function calculateMetrics($entity, $filters, $universityId)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

        // Entity restriction
        if ($entity['type'] === 'university') {
            $query->where('faculties.university_id', $entity['id']);
        } elseif ($entity['type'] === 'faculty') {
            $query->where('faculties.id', $entity['id']);
        } elseif ($entity['type'] === 'department') {
            $query->where('departments.id', $entity['id']);
        } elseif ($entity['type'] === 'researcher') {
            $query->where('lecturers.id', $entity['id']);
        }

        // University restriction for non-ministry users
        if ($universityId && $entity['type'] !== 'university') {
            $query->where('faculties.university_id', $universityId);
        }

        // ---- Apply faculty/department/researcher data filters ----
        if ($entity['type'] === 'university') {
            // For university level: filter by NAME to match across universities
            if ($filters['faculty']) {
                $facultyName = DB::table('faculties')->where('id', $filters['faculty'])->value('facultyname');
                if ($facultyName) {
                    $query->where('faculties.facultyname', $facultyName);
                }
            }
            if ($filters['department']) {
                $deptName = DB::table('departments')->where('id', $filters['department'])->value('deptname');
                if ($deptName) {
                    $query->where('departments.deptname', $deptName);
                }
            }
            if ($filters['researcher']) {
                $researcherName = DB::table('lecturers')->where('id', $filters['researcher'])->value('lecturername');
                if ($researcherName) {
                    $query->where('lecturers.lecturername', $researcherName);
                }
            }
        } else {
            // For faculty/department/researcher level: filter by ID (already correct)
            if ($filters['faculty']) {
                $query->where('faculties.id', $filters['faculty']);
            }
            if ($filters['department']) {
                $query->where('departments.id', $filters['department']);
            }
            if ($filters['researcher']) {
                $query->where('lecturers.id', $filters['researcher']);
            }
        }
        // ---------------------------------------------------------

        // Other filters (year, publication_type, indexed, language, grade)
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

        // Aggregations (unchanged)
        $totalPublications = (int) $query->count();
        $totalCitations = (int) $query->sum('academic_papers.citation') ?: 0;
        $citedPublications = (int) $query->clone()->where('academic_papers.citation', '>', 0)->count();
        $uncited = $totalPublications - $citedPublications;
        $avgCitations = $totalPublications > 0 ? round($totalCitations / $totalPublications, 2) : 0;
        $highestCitation = (int) $query->clone()->max('academic_papers.citation') ?: 0;

        // H-index
        $citations = $query->clone()->pluck('academic_papers.citation')->filter()->sortDesc()->values();
        $hIndex = 0;
        foreach ($citations as $i => $cit) {
            if ($cit >= $i + 1) $hIndex = $i + 1;
            else break;
        }

        // Q1-Q4
        $q1 = (int) $query->clone()->where('academic_papers.indexed', 'Q1')->count();
        $q2 = (int) $query->clone()->where('academic_papers.indexed', 'Q2')->count();
        $q3 = (int) $query->clone()->where('academic_papers.indexed', 'Q3')->count();
        $q4 = (int) $query->clone()->where('academic_papers.indexed', 'Q4')->count();

        $indexedPublications = (int) $query->clone()->whereNotNull('academic_papers.indexed')
            ->where('academic_papers.indexed', '!=', '')
            ->count();

        // Researchers count
        $researchersCount = (int) $query->clone()->distinct('lecturers.id')->count('lecturers.id');

        // Collaboration
        $collaboration = $query->clone()->where('academic_papers.collaboration', 'International')->count();
        $totalCollaboration = $query->clone()->whereNotNull('academic_papers.collaboration')->count();
        $internationalCollaboration = $totalCollaboration > 0 ? round(($collaboration / $totalCollaboration) * 100, 1) : 0;

        // Funding
        $fundingCount = (int) $query->clone()->whereNotNull('academic_papers.funding')
            ->where('academic_papers.funding', '!=', '')
            ->count();
        $fundedPercentage = $totalPublications > 0 ? round(($fundingCount / $totalPublications) * 100, 1) : 0;

        return [
            'entity_id' => $entity['id'],
            'entity_name' => $entity['name'],
            'entity_type' => $entity['type'],
            'publications' => $totalPublications,
            'citations' => $totalCitations,
            'cited_publications' => $citedPublications,
            'uncited_publications' => $uncited,
            'avg_citations' => $avgCitations,
            'highest_citation' => $highestCitation,
            'h_index' => $hIndex,
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'q4' => $q4,
            'indexed_publications' => $indexedPublications,
            'researchers_count' => $researchersCount,
            'international_collaboration_percent' => $internationalCollaboration,
            'funded_percentage' => $fundedPercentage,
        ];
    }

    protected function calculatePeerAverage($peerMetrics)
    {
        if (empty($peerMetrics)) {
            return null;
        }
        $keys = ['publications', 'citations', 'avg_citations', 'h_index', 'q1', 'q2', 'q3', 'q4', 'indexed_publications', 'researchers_count'];
        $avg = [];
        foreach ($keys as $key) {
            $sum = 0;
            $count = 0;
            foreach ($peerMetrics as $p) {
                if (isset($p['metrics'][$key]) && is_numeric($p['metrics'][$key])) {
                    $sum += $p['metrics'][$key];
                    $count++;
                }
            }
            $avg[$key] = $count > 0 ? round($sum / $count, 2) : 0;
        }
        $avg['entity_name'] = 'Peer Average';
        $avg['entity_type'] = 'peer_average';
        return $avg;
    }

    protected function buildRanking($metrics, $entities)
    {
        // Sort by a composite score (e.g., citations * 0.6 + h_index * 0.4)
        $ranked = [];
        foreach ($entities as $entity) {
            $m = $metrics[$entity['id']] ?? null;
            if ($m) {
                $score = ($m['citations'] * 0.6) + ($m['h_index'] * 0.4);
                $ranked[] = [
                    'entity' => $entity,
                    'metrics' => $m,
                    'score' => $score,
                ];
            }
        }
        usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);
        $rank = 1;
        foreach ($ranked as &$item) {
            $item['rank'] = $rank++;
        }
        return $ranked;
    }

    protected function buildComparisonMatrix($primary, $peerAverage, $peerMetrics)
    {
        $comparison = [];
        if (!$primary || !$peerAverage) return $comparison;

        $keys = ['publications', 'citations', 'avg_citations', 'h_index', 'q1', 'q2', 'q3', 'q4', 'indexed_publications'];
        $labels = [
            'publications' => 'Publications',
            'citations' => 'Citations',
            'avg_citations' => 'Avg Citations',
            'h_index' => 'H-index',
            'q1' => 'Q1',
            'q2' => 'Q2',
            'q3' => 'Q3',
            'q4' => 'Q4',
            'indexed_publications' => 'Indexed Publications',
        ];

        foreach ($keys as $key) {
            $primaryVal = $primary[$key] ?? 0;
            $peerAvgVal = $peerAverage[$key] ?? 0;
            $diff = $primaryVal - $peerAvgVal;
            $diffPercent = $peerAvgVal != 0 ? round(($diff / $peerAvgVal) * 100, 1) : ($primaryVal > 0 ? 100 : 0);
            $status = $diffPercent > 10 ? 'above' : ($diffPercent < -10 ? 'below' : 'near');
            $comparison[] = [
                'indicator' => $labels[$key] ?? $key,
                'primary_value' => $primaryVal,
                'peer_average' => $peerAvgVal,
                'difference' => $diff,
                'difference_percent' => $diffPercent,
                'status' => $status,
            ];
        }
        return $comparison;
    }

    protected function generateInsights($primary, $peerAverage, $comparison)
    {
        $insights = [];
        if (!$primary || !$peerAverage) return $insights;

        // Check if keys exist before accessing
        if (isset($primary['citations'], $peerAverage['citations']) && $peerAverage['citations'] > 0) {
            $diff = round((($primary['citations'] - $peerAverage['citations']) / $peerAverage['citations']) * 100, 1);
            if ($diff > 0) {
                $insights[] = "Citation impact is {$diff}% above the peer average.";
            } elseif ($diff < 0) {
                $insights[] = "Citation impact is " . abs($diff) . "% below the peer average.";
            }
        }

        if (isset($primary['h_index'], $peerAverage['h_index'])) {
            if ($primary['h_index'] > $peerAverage['h_index']) {
                $insights[] = "H-index is above the peer average.";
            } elseif ($primary['h_index'] < $peerAverage['h_index']) {
                $insights[] = "H-index is below the peer average.";
            }
        }

        if (isset($primary['q1'], $peerAverage['q1'])) {
            if ($primary['q1'] > $peerAverage['q1']) {
                $insights[] = "Q1 publication output is above the peer average.";
            } elseif ($primary['q1'] < $peerAverage['q1']) {
                $insights[] = "Q1 publication output is below the peer average.";
            }
        }

        // Cited percentage comparison
        if (isset($primary['cited_publications'], $primary['publications'], $peerAverage['cited_publications'], $peerAverage['publications'])) {
            $primaryCited = $primary['cited_publications'] / max(1, $primary['publications']) * 100;
            $peerCited = $peerAverage['cited_publications'] / max(1, $peerAverage['publications']) * 100;
            if ($primaryCited > $peerCited + 5) {
                $insights[] = "Higher percentage of publications are cited compared to peers.";
            } elseif ($primaryCited < $peerCited - 5) {
                $insights[] = "Lower percentage of publications are cited compared to peers.";
            }
        }

        return array_slice($insights, 0, 5);
    }

    protected function getInternalBenchmark($level, $filters, $universityId)
    {
        // For faculty, department, researcher benchmarking (within the same university)
        if ($level === 'faculty') {
            return $this->getFacultyBenchmark($filters, $universityId);
        } elseif ($level === 'department') {
            return $this->getDepartmentBenchmark($filters, $universityId);
        } elseif ($level === 'researcher') {
            return $this->getResearcherBenchmark($filters, $universityId);
        }
        return [];
    }

    protected function getFacultyBenchmark($filters, $universityId)
    {
        $query = Faculty::query();
        if ($universityId) {
            $query->where('university_id', $universityId);
        }
        $faculties = $query->get();
        $result = [];
        foreach ($faculties as $fac) {
            $metrics = $this->calculateMetrics(['id' => $fac->id, 'type' => 'faculty', 'name' => $fac->facultyname], $filters, $universityId);
            $result[] = [
                'entity' => ['id' => $fac->id, 'name' => $fac->facultyname, 'type' => 'faculty'],
                'metrics' => $metrics,
            ];
        }
        usort($result, fn($a, $b) => $b['metrics']['citations'] <=> $a['metrics']['citations']);
        return $result;
    }

    protected function getDepartmentBenchmark($filters, $universityId)
    {
        $query = Department::query();
        if ($universityId) {
            $query->where('university_id', $universityId);
        }
        $departments = $query->get();
        $result = [];
        foreach ($departments as $dept) {
            $metrics = $this->calculateMetrics(['id' => $dept->id, 'type' => 'department', 'name' => $dept->deptname], $filters, $universityId);
            $result[] = [
                'entity' => ['id' => $dept->id, 'name' => $dept->deptname, 'type' => 'department'],
                'metrics' => $metrics,
            ];
        }
        usort($result, fn($a, $b) => $b['metrics']['citations'] <=> $a['metrics']['citations']);
        return $result;
    }

    protected function getResearcherBenchmark($filters, $universityId)
    {
        $query = Lecturer::query();
        if ($universityId) {
            $query->whereHas('faculty', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }
        $researchers = $query->get();
        $result = [];
        foreach ($researchers as $res) {
            $metrics = $this->calculateMetrics(['id' => $res->id, 'type' => 'researcher', 'name' => $res->lecturername], $filters, $universityId);
            if ($metrics['publications'] > 0) { // Only show those with publications
                $result[] = [
                    'entity' => ['id' => $res->id, 'name' => $res->lecturername, 'type' => 'researcher'],
                    'metrics' => $metrics,
                ];
            }
        }
        usort($result, fn($a, $b) => $b['metrics']['citations'] <=> $a['metrics']['citations']);
        return $result;
    }

    protected function getAvailableLevels($universityId)
    {
        $levels = [];
        // Check if multiple universities exist
        $uniCount = University::count();
        if ($uniCount > 1) {
            $levels[] = 'university';
        }
        // Faculty, department, researcher are always available if data exists
        $levels[] = 'faculty';
        $levels[] = 'department';
        $levels[] = 'researcher';
        return $levels;
    }

    protected function getEffectiveUniversityId(Request $request)
    {
        if (auth()->user() && auth()->user()->role === 'ministry_authority') {
            return null;
        }
        return currentUniversityId();
    }

    public function previewPdf(Request $request)
    {
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId($request);
        $level = $request->input('level', 'university');
        $primaryId = $request->input('primary_id');
        $peerIds = $request->input('peer_ids', '');

        if (is_string($peerIds)) {
            $peerIds = array_filter(explode(',', $peerIds));
        } elseif (!is_array($peerIds)) {
            $peerIds = [];
        }

        if ($primaryId) {
            $primaryId = (int) $primaryId;
        }

        $entities = $this->getEntities($level, $primaryId, $peerIds, $universityId);
        if (empty($entities)) {
            return response("No entities selected", 404);
        }

        $metrics = [];
        foreach ($entities as $entity) {
            $metrics[$entity['id']] = $this->calculateMetrics($entity, $filters, $universityId);
        }

        $primaryKey = $primaryId ?? $entities[0]['id'];
        $primaryMetrics = $metrics[$primaryKey] ?? null;

        $peerMetrics = [];
        foreach ($entities as $entity) {
            if ($entity['id'] != $primaryKey) {
                $peerMetrics[] = [
                    'entity' => $entity,
                    'metrics' => $metrics[$entity['id']],
                ];
            }
        }

        $peerAverage = $this->calculatePeerAverage($peerMetrics);
        $ranking = $this->buildRanking($metrics, $entities);
        $comparison = $this->buildComparisonMatrix($primaryMetrics, $peerAverage, $peerMetrics);
        $insights = $this->generateInsights($primaryMetrics, $peerAverage, $comparison);

        // ===== ADD INTERNAL BENCHMARK (same as index method) =====
        $internalBenchmark = [];
        if ($level !== 'university') {
            $internalBenchmark = $this->getInternalBenchmark($level, $filters, $universityId);
        }

        $date = now()->format('Y-m-d');

        $html = view('pdf.benchmarking', [
            'level'              => $level,
            'primary'            => $primaryMetrics,
            'peers'              => $peerMetrics,
            'peer_average'       => $peerAverage,
            'ranking'            => $ranking,
            'comparison'         => $comparison,
            'insights'           => $insights,
            'internal_benchmark' => $internalBenchmark, // <-- PASS THIS
            'filters'            => $filters,
            'date'               => $date,
            'is_ministry' => auth()->user() && auth()->user()->role === 'ministry_authority',
            'university_id'      => $universityId,
        ])->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'fontDir'       => array_merge((new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [
                storage_path('fonts'),
            ]),
            'fontdata'      => [
                'bahij_nazanin' => [
                    'R'          => 'Bahij_Nazanin-Regular.ttf',
                    'useOTL'     => 0xFF,
                    'useKashida' => 75,
                ]
            ],
            'default_font'  => 'bahij_nazanin',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'directionality'   => 'rtl',
        ]);

        $mpdf->WriteHTML($html);
        return response($mpdf->Output('benchmarking.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}