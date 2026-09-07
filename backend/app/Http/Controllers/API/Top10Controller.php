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

class Top10Controller extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId($request);
        $category = $request->input('category', 'researchers');
        $metric = $request->input('metric', 'publications');
        $limit = (int) $request->input('limit', 10);
        $direction = $request->input('direction', 'desc');
        $minPublications = (int) $request->input('minimum_publications', 0);

        $allowedCategories = $this->getAllowedCategories($universityId);
        if (!in_array($category, $allowedCategories)) {
            return response()->json(['message' => 'Invalid category'], 422);
        }

        $metrics = $this->getMetricsForCategory($category);
        if (!in_array($metric, array_keys($metrics))) {
            return response()->json(['message' => 'Invalid metric for category'], 422);
        }

        $ranking = $this->buildRanking($category, $metric, $filters, $universityId, $limit, $direction, $minPublications);
        $keyFindings = $this->generateKeyFindings($ranking, $category, $metric, $filters);

        return response()->json([
            'scope' => [
                'university_id' => $universityId,
                'is_ministry' => Auth::user() && Auth::user()->role === 'ministry_authority',
            ],
            'category' => $category,
            'metric' => $metric,
            'limit' => $limit,
            'direction' => $direction,
            'minimum_publications' => $minPublications,
            'ranking' => $ranking,
            'key_findings' => $keyFindings,
            'filters_applied' => $filters,
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
            'grade' => $request->input('grade'),
            'publication_type' => $request->input('publication_type'),
            'indexed' => $request->input('indexed'),
            'language' => $request->input('language'),
            'research_area' => $request->input('research_area'),
            'collaboration_type' => $request->input('collaboration_type'),
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

    protected function getAllowedCategories($universityId)
    {
        $categories = ['researchers', 'publications', 'faculties', 'departments', 'research_areas'];
        if (Auth::user() && Auth::user()->role === 'ministry_authority') {
            $categories[] = 'universities';
        }
        return $categories;
    }

    protected function getMetricsForCategory($category)
    {
        $metrics = [
            'researchers' => [
                'publications' => 'Total Publications',
                'citations' => 'Total Citations',
                'avg_citations' => 'Average Citations per Publication',
                'h_index' => 'H-index',
                'q1' => 'Q1 Publications',
                'q1_share' => 'Q1 Share (%)',
                'collaborative' => 'Collaborative Publications',
                'collaboration_rate' => 'Collaboration Rate (%)',
                'funded' => 'Funded Publications',
                'funded_rate' => 'Funded Publication Rate (%)',
                'cited' => 'Cited Publications',
                'cited_rate' => 'Cited Publication Rate (%)',
            ],
            'publications' => [
                'citations' => 'Total Citations',
                'year' => 'Year',
                'q1' => 'Q1',
                'collaboration' => 'Collaboration Type',
            ],
            'faculties' => [
                'publications' => 'Total Publications',
                'citations' => 'Total Citations',
                'avg_citations' => 'Average Citations per Publication',
                'q1' => 'Q1 Publications',
                'q1_share' => 'Q1 Share (%)',
                'researchers' => 'Total Researchers',
                'collaborative' => 'Collaborative Publications',
                'collaboration_rate' => 'Collaboration Rate (%)',
                'funded' => 'Funded Publications',
                'cited_rate' => 'Cited Publication Rate (%)',
            ],
            'departments' => [
                'publications' => 'Total Publications',
                'citations' => 'Total Citations',
                'avg_citations' => 'Average Citations per Publication',
                'q1' => 'Q1 Publications',
                'q1_share' => 'Q1 Share (%)',
                'researchers' => 'Total Researchers',
                'collaborative' => 'Collaborative Publications',
                'collaboration_rate' => 'Collaboration Rate (%)',
                'funded' => 'Funded Publications',
                'cited_rate' => 'Cited Publication Rate (%)',
            ],
            'universities' => [
                'publications' => 'Total Publications',
                'citations' => 'Total Citations',
                'avg_citations' => 'Average Citations per Publication',
                'researchers' => 'Total Researchers',
                'publications_per_researcher' => 'Publications per Researcher',
                'citations_per_researcher' => 'Citations per Researcher',
                'q1' => 'Q1 Publications',
                'q1_share' => 'Q1 Share (%)',
                'collaboration_rate' => 'Collaboration Rate (%)',
                'funded_rate' => 'Funded Publication Rate (%)',
            ],
            'research_areas' => [
                'publications' => 'Total Publications',
                'citations' => 'Total Citations',
                'avg_citations' => 'Average Citations per Publication',
                'researchers' => 'Total Researchers',
                'q1' => 'Q1 Publications',
                'q1_share' => 'Q1 Share (%)',
                'collaboration_rate' => 'Collaboration Rate (%)',
            ],
        ];

        return $metrics[$category] ?? [];
    }

    protected function buildRanking($category, $metric, $filters, $universityId, $limit, $direction, $minPublications)
    {
        switch ($category) {
            case 'researchers':
                return $this->getTopResearchers($metric, $filters, $universityId, $limit, $direction, $minPublications);
            case 'publications':
                return $this->getTopPublications($metric, $filters, $universityId, $limit, $direction);
            case 'faculties':
                return $this->getTopFaculties($metric, $filters, $universityId, $limit, $direction, $minPublications);
            case 'departments':
                return $this->getTopDepartments($metric, $filters, $universityId, $limit, $direction, $minPublications);
            case 'universities':
                return $this->getTopUniversities($metric, $filters, $universityId, $limit, $direction, $minPublications);
            case 'research_areas':
                return $this->getTopResearchAreas($metric, $filters, $universityId, $limit, $direction, $minPublications);
            default:
                return [];
        }
    }

    protected function buildBasePaperQuery($filters, $universityId)
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
        if ($filters['collaboration_type']) {
            $query->where('academic_papers.collaboration', $filters['collaboration_type']);
        }

        return $query;
    }

    // ---------- Researcher Ranking ----------
    protected function getTopResearchers($metric, $filters, $universityId, $limit, $direction, $minPublications)
    {
        $base = $this->buildBasePaperQuery($filters, $universityId);

        $researchers = $base->select(
            'lecturers.id',
            'lecturers.lecturername as name',
            'faculties.facultyname as faculty',
            'departments.deptname as department',
            'universities.name as university',
            DB::raw('COUNT(*) as publications'),
            DB::raw('SUM(academic_papers.citation) as citations'),
            DB::raw('SUM(CASE WHEN academic_papers.citation > 0 THEN 1 ELSE 0 END) as cited_pubs'),
            DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q1" THEN 1 ELSE 0 END) as q1_pubs'),
            DB::raw('SUM(CASE WHEN academic_papers.indexed IS NOT NULL AND academic_papers.indexed != "" THEN 1 ELSE 0 END) as indexed_pubs'),
            DB::raw('SUM(CASE WHEN academic_papers.collaboration IS NOT NULL AND academic_papers.collaboration != "" THEN 1 ELSE 0 END) as collab_pubs'),
            DB::raw('SUM(CASE WHEN academic_papers.funding IS NOT NULL AND academic_papers.funding != "" THEN 1 ELSE 0 END) as funded_pubs')
        )
        ->leftJoin('universities', 'faculties.university_id', '=', 'universities.id')
        ->groupBy('lecturers.id', 'lecturers.lecturername', 'faculties.facultyname', 'departments.deptname', 'universities.name')
        ->get();

        $result = [];
        foreach ($researchers as $r) {
            $pubs = (int) $r->publications;
            if ($pubs < $minPublications) continue;

            $citations = (int) $r->citations;
            $avg = $pubs > 0 ? round($citations / $pubs, 2) : 0;
            $q1Share = $pubs > 0 ? round(($r->q1_pubs / $pubs) * 100, 1) : 0;
            $collabRate = $pubs > 0 ? round(($r->collab_pubs / $pubs) * 100, 1) : 0;
            $fundedRate = $pubs > 0 ? round(($r->funded_pubs / $pubs) * 100, 1) : 0;
            $citedRate = $pubs > 0 ? round(($r->cited_pubs / $pubs) * 100, 1) : 0;

            $citationsList = DB::table('academic_papers')
                ->where('lecturer_id', $r->id)
                ->whereNull('deleted_at')
                ->where('status', 'Published')
                ->pluck('citation')
                ->filter()
                ->sortDesc()
                ->values();
            $hIndex = 0;
            foreach ($citationsList as $i => $cit) {
                if ($cit >= $i + 1) $hIndex = $i + 1;
                else break;
            }

            $row = [
                'id' => $r->id,
                'name' => $r->name,
                'faculty' => $r->faculty,
                'department' => $r->department ?? '-',
                'university' => $r->university ?? '',
                'publications' => $pubs,
                'citations' => $citations,
                'avg_citations' => $avg,
                'h_index' => $hIndex,
                'q1' => (int) $r->q1_pubs,
                'q1_share' => $q1Share,
                'collaborative' => (int) $r->collab_pubs,
                'collaboration_rate' => $collabRate,
                'funded' => (int) $r->funded_pubs,
                'funded_rate' => $fundedRate,
                'cited' => (int) $r->cited_pubs,
                'cited_rate' => $citedRate,
            ];

            $row['metric_value'] = $this->extractMetricValue($row, $metric);
            if ($row['metric_value'] !== null) {
                $result[] = $row;
            }
        }

        // Sort
        usort($result, function ($a, $b) use ($direction) {
            $valA = $a['metric_value'];
            $valB = $b['metric_value'];
            if ($valA == $valB) return 0;
            if ($direction === 'desc') return $valB <=> $valA;
            return $valA <=> $valB;
        });

        // Assign ranks
        $ranked = [];
        $rank = 1;
        $prevVal = null;
        $skip = 0;

        foreach ($result as $item) {
            if ($prevVal !== null && $item['metric_value'] != $prevVal) {
                $rank += $skip + 1;
                $skip = 0;
            } else if ($prevVal !== null && $item['metric_value'] == $prevVal) {
                $skip++;
            }
            $item['rank'] = $rank;
            $ranked[] = $item;
            $prevVal = $item['metric_value'];
            if (count($ranked) >= $limit) break;
        }

        return $ranked;
    }

    // ---------- Publication Ranking ----------
    protected function getTopPublications($metric, $filters, $universityId, $limit, $direction)
    {
        $query = $this->buildBasePaperQuery($filters, $universityId);

        $publications = $query->select(
            'academic_papers.id',
            'academic_papers.title',
            'academic_papers.year',
            'academic_papers.citation as citations',
            'academic_papers.indexed',
            'academic_papers.collaboration',
            'academic_papers.funding',
            'lecturers.lecturername as researcher',
            'faculties.facultyname as faculty',
            'departments.deptname as department',
            'universities.name as university'
        )
        ->leftJoin('universities', 'faculties.university_id', '=', 'universities.id')
        ->get();

        $result = [];
        foreach ($publications as $pub) {
            $row = [
                'id' => $pub->id,
                'title' => $pub->title,
                'year' => $pub->year,
                'researcher' => $pub->researcher,
                'faculty' => $pub->faculty,
                'department' => $pub->department ?? '-',
                'university' => $pub->university ?? '',
                'citations' => (int) $pub->citations,
                'q1' => $pub->indexed === 'Q1' ? 1 : 0,
                'collaboration' => $pub->collaboration,
                'funded' => $pub->funding ? 1 : 0,
            ];

            $row['metric_value'] = $this->extractMetricValue($row, $metric);
            if ($row['metric_value'] !== null) {
                $result[] = $row;
            }
        }

        usort($result, function ($a, $b) use ($direction) {
            $valA = $a['metric_value'];
            $valB = $b['metric_value'];
            if ($valA == $valB) return 0;
            if ($direction === 'desc') return $valB <=> $valA;
            return $valA <=> $valB;
        });

        $ranked = [];
        $rank = 1;
        $prevVal = null;
        $skip = 0;

        foreach ($result as $item) {
            if ($prevVal !== null && $item['metric_value'] != $prevVal) {
                $rank += $skip + 1;
                $skip = 0;
            } else if ($prevVal !== null && $item['metric_value'] == $prevVal) {
                $skip++;
            }
            $item['rank'] = $rank;
            $ranked[] = $item;
            $prevVal = $item['metric_value'];
            if (count($ranked) >= $limit) break;
        }

        return $ranked;
    }

    // ---------- Faculty Ranking ----------
    protected function getTopFaculties($metric, $filters, $universityId, $limit, $direction, $minPublications)
    {
        $query = Faculty::query();
        if ($universityId) {
            $query->where('university_id', $universityId);
        }
        $faculties = $query->get();

        $result = [];
        foreach ($faculties as $fac) {
            $paperQuery = $this->buildBasePaperQueryForEntity($fac->id, 'faculty', $filters, $universityId);
            $metrics = $this->getEntityMetrics($paperQuery);
            if ($metrics['publications'] < $minPublications) continue;
            if ($metrics['publications'] > 0) {
                $row = [
                    'id' => $fac->id,
                    'name' => $fac->facultyname,
                    'university' => $fac->university ? $fac->university->name : '',
                    'publications' => $metrics['publications'],
                    'citations' => $metrics['citations'],
                    'avg_citations' => $metrics['avg_citations'],
                    'q1' => $metrics['q1'],
                    'q1_share' => $metrics['q1_share'],
                    'researchers' => $metrics['researchers'],
                    'collaborative' => $metrics['collaborative'],
                    'collaboration_rate' => $metrics['collaboration_rate'],
                    'funded' => $metrics['funded'],
                    'cited_rate' => $metrics['cited_rate'],
                ];
                $row['metric_value'] = $this->extractMetricValue($row, $metric);
                if ($row['metric_value'] !== null) {
                    $result[] = $row;
                }
            }
        }

        usort($result, function ($a, $b) use ($direction) {
            $valA = $a['metric_value'];
            $valB = $b['metric_value'];
            if ($valA == $valB) return 0;
            if ($direction === 'desc') return $valB <=> $valA;
            return $valA <=> $valB;
        });

        $ranked = [];
        $rank = 1;
        $prevVal = null;
        $skip = 0;

        foreach ($result as $item) {
            if ($prevVal !== null && $item['metric_value'] != $prevVal) {
                $rank += $skip + 1;
                $skip = 0;
            } else if ($prevVal !== null && $item['metric_value'] == $prevVal) {
                $skip++;
            }
            $item['rank'] = $rank;
            $ranked[] = $item;
            $prevVal = $item['metric_value'];
            if (count($ranked) >= $limit) break;
        }

        return $ranked;
    }

    // ---------- Department Ranking ----------
    protected function getTopDepartments($metric, $filters, $universityId, $limit, $direction, $minPublications)
    {
        $query = Department::query();
        if ($universityId) {
            $query->where('university_id', $universityId);
        }
        $departments = $query->get();

        $result = [];
        foreach ($departments as $dept) {
            $paperQuery = $this->buildBasePaperQueryForEntity($dept->id, 'department', $filters, $universityId);
            $metrics = $this->getEntityMetrics($paperQuery);
            if ($metrics['publications'] < $minPublications) continue;
            if ($metrics['publications'] > 0) {
                $row = [
                    'id' => $dept->id,
                    'name' => $dept->deptname,
                    'faculty' => $dept->faculty ? $dept->faculty->facultyname : '',
                    'university' => $dept->university ? $dept->university->name : '',
                    'publications' => $metrics['publications'],
                    'citations' => $metrics['citations'],
                    'avg_citations' => $metrics['avg_citations'],
                    'q1' => $metrics['q1'],
                    'q1_share' => $metrics['q1_share'],
                    'researchers' => $metrics['researchers'],
                    'collaborative' => $metrics['collaborative'],
                    'collaboration_rate' => $metrics['collaboration_rate'],
                    'funded' => $metrics['funded'],
                    'cited_rate' => $metrics['cited_rate'],
                ];
                $row['metric_value'] = $this->extractMetricValue($row, $metric);
                if ($row['metric_value'] !== null) {
                    $result[] = $row;
                }
            }
        }

        usort($result, function ($a, $b) use ($direction) {
            $valA = $a['metric_value'];
            $valB = $b['metric_value'];
            if ($valA == $valB) return 0;
            if ($direction === 'desc') return $valB <=> $valA;
            return $valA <=> $valB;
        });

        $ranked = [];
        $rank = 1;
        $prevVal = null;
        $skip = 0;

        foreach ($result as $item) {
            if ($prevVal !== null && $item['metric_value'] != $prevVal) {
                $rank += $skip + 1;
                $skip = 0;
            } else if ($prevVal !== null && $item['metric_value'] == $prevVal) {
                $skip++;
            }
            $item['rank'] = $rank;
            $ranked[] = $item;
            $prevVal = $item['metric_value'];
            if (count($ranked) >= $limit) break;
        }

        return $ranked;
    }

    // ---------- University Ranking ----------
    protected function getTopUniversities($metric, $filters, $universityId, $limit, $direction, $minPublications)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'ministry_authority') {
            return [];
        }

        $query = University::query();
        $universities = $query->get();

        $result = [];
        foreach ($universities as $uni) {
            $paperQuery = $this->buildBasePaperQueryForEntity($uni->id, 'university', $filters, $uni->id);
            $metrics = $this->getEntityMetrics($paperQuery);
            if ($metrics['publications'] < $minPublications) continue;
            if ($metrics['publications'] > 0) {
                $row = [
                    'id' => $uni->id,
                    'name' => $uni->name,
                    'publications' => $metrics['publications'],
                    'citations' => $metrics['citations'],
                    'avg_citations' => $metrics['avg_citations'],
                    'researchers' => $metrics['researchers'],
                    'publications_per_researcher' => $metrics['researchers'] > 0 ? round($metrics['publications'] / $metrics['researchers'], 2) : 0,
                    'citations_per_researcher' => $metrics['researchers'] > 0 ? round($metrics['citations'] / $metrics['researchers'], 2) : 0,
                    'q1' => $metrics['q1'],
                    'q1_share' => $metrics['q1_share'],
                    'collaboration_rate' => $metrics['collaboration_rate'],
                    'funded_rate' => $metrics['funded_rate'],
                ];
                $row['metric_value'] = $this->extractMetricValue($row, $metric);
                if ($row['metric_value'] !== null) {
                    $result[] = $row;
                }
            }
        }

        usort($result, function ($a, $b) use ($direction) {
            $valA = $a['metric_value'];
            $valB = $b['metric_value'];
            if ($valA == $valB) return 0;
            if ($direction === 'desc') return $valB <=> $valA;
            return $valA <=> $valB;
        });

        $ranked = [];
        $rank = 1;
        $prevVal = null;
        $skip = 0;

        foreach ($result as $item) {
            if ($prevVal !== null && $item['metric_value'] != $prevVal) {
                $rank += $skip + 1;
                $skip = 0;
            } else if ($prevVal !== null && $item['metric_value'] == $prevVal) {
                $skip++;
            }
            $item['rank'] = $rank;
            $ranked[] = $item;
            $prevVal = $item['metric_value'];
            if (count($ranked) >= $limit) break;
        }

        return $ranked;
    }

    // ---------- Research Area Ranking ----------
    protected function getTopResearchAreas($metric, $filters, $universityId, $limit, $direction, $minPublications)
    {
        $paperQuery = $this->buildBasePaperQuery($filters, $universityId);
        $papers = $paperQuery->get(['lecturers.specialized_area', 'academic_papers.citation', 'academic_papers.collaboration', 'academic_papers.indexed', 'academic_papers.funding']);

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
                        'citations' => 0,
                        'collaborative' => 0,
                        'q1' => 0,
                        'funded' => 0,
                    ];
                }
                $areaData[$area]['publications']++;
                $areaData[$area]['citations'] += $paper->citation ?? 0;
                if ($paper->collaboration && $paper->collaboration != '') {
                    $areaData[$area]['collaborative']++;
                }
                if ($paper->indexed === 'Q1') {
                    $areaData[$area]['q1']++;
                }
                if ($paper->funding && $paper->funding != '') {
                    $areaData[$area]['funded']++;
                }
            }
        }

        $result = [];
        foreach ($areaData as $area => $data) {
            $pubs = $data['publications'];
            if ($pubs < $minPublications) continue;
            $citations = $data['citations'];
            $avg = $pubs > 0 ? round($citations / $pubs, 2) : 0;
            $q1Share = $pubs > 0 ? round(($data['q1'] / $pubs) * 100, 1) : 0;
            $collabRate = $pubs > 0 ? round(($data['collaborative'] / $pubs) * 100, 1) : 0;
            $row = [
                'area' => $area,
                'publications' => $pubs,
                'citations' => $citations,
                'avg_citations' => $avg,
                'researchers' => 0,
                'q1' => $data['q1'],
                'q1_share' => $q1Share,
                'collaboration_rate' => $collabRate,
            ];
            $row['metric_value'] = $this->extractMetricValue($row, $metric);
            if ($row['metric_value'] !== null) {
                $result[] = $row;
            }
        }

        usort($result, function ($a, $b) use ($direction) {
            $valA = $a['metric_value'];
            $valB = $b['metric_value'];
            if ($valA == $valB) return 0;
            if ($direction === 'desc') return $valB <=> $valA;
            return $valA <=> $valB;
        });

        $ranked = [];
        $rank = 1;
        $prevVal = null;
        $skip = 0;

        foreach ($result as $item) {
            if ($prevVal !== null && $item['metric_value'] != $prevVal) {
                $rank += $skip + 1;
                $skip = 0;
            } else if ($prevVal !== null && $item['metric_value'] == $prevVal) {
                $skip++;
            }
            $item['rank'] = $rank;
            $ranked[] = $item;
            $prevVal = $item['metric_value'];
            if (count($ranked) >= $limit) break;
        }

        return $ranked;
    }

    // ---------- Entity Helpers ----------
    protected function buildBasePaperQueryForEntity($entityId, $entityType, $filters, $universityId)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

        if ($entityType === 'university') {
            $query->where('faculties.university_id', $entityId);
        } elseif ($entityType === 'faculty') {
            $query->where('faculties.id', $entityId);
        } elseif ($entityType === 'department') {
            $query->where('departments.id', $entityId);
        }

        if ($universityId && $entityType !== 'university') {
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
        if ($filters['collaboration_type']) {
            $query->where('academic_papers.collaboration', $filters['collaboration_type']);
        }

        return $query;
    }

    protected function getEntityMetrics($query)
    {
        $totalPubs = (int) $query->clone()->count();
        $citations = (int) $query->clone()->sum('academic_papers.citation') ?: 0;
        $collabPubs = (int) $query->clone()
            ->whereNotNull('academic_papers.collaboration')
            ->where('academic_papers.collaboration', '!=', '')
            ->count();
        $q1Pubs = (int) $query->clone()->where('academic_papers.indexed', 'Q1')->count();
        $fundedPubs = (int) $query->clone()
            ->whereNotNull('academic_papers.funding')
            ->where('academic_papers.funding', '!=', '')
            ->count();
        $researchers = (int) $query->clone()->distinct('lecturers.id')->count('lecturers.id');
        $citedPubs = (int) $query->clone()->where('academic_papers.citation', '>', 0)->count();

        $avg = $totalPubs > 0 ? round($citations / $totalPubs, 2) : 0;
        $q1Share = $totalPubs > 0 ? round(($q1Pubs / $totalPubs) * 100, 1) : 0;
        $collabRate = $totalPubs > 0 ? round(($collabPubs / $totalPubs) * 100, 1) : 0;
        $fundedRate = $totalPubs > 0 ? round(($fundedPubs / $totalPubs) * 100, 1) : 0;
        $citedRate = $totalPubs > 0 ? round(($citedPubs / $totalPubs) * 100, 1) : 0;

        return [
            'publications' => $totalPubs,
            'citations' => $citations,
            'avg_citations' => $avg,
            'q1' => $q1Pubs,
            'q1_share' => $q1Share,
            'researchers' => $researchers,
            'collaborative' => $collabPubs,
            'collaboration_rate' => $collabRate,
            'funded' => $fundedPubs,
            'funded_rate' => $fundedRate,
            'cited_rate' => $citedRate,
        ];
    }

    protected function extractMetricValue($row, $metric)
    {
        $map = [
            'publications' => 'publications',
            'citations' => 'citations',
            'avg_citations' => 'avg_citations',
            'h_index' => 'h_index',
            'q1' => 'q1',
            'q1_share' => 'q1_share',
            'collaborative' => 'collaborative',
            'collaboration_rate' => 'collaboration_rate',
            'funded' => 'funded',
            'funded_rate' => 'funded_rate',
            'cited' => 'cited',
            'cited_rate' => 'cited_rate',
            'year' => 'year',
            'collaboration' => 'collaboration',
            'researchers' => 'researchers',
            'publications_per_researcher' => 'publications_per_researcher',
            'citations_per_researcher' => 'citations_per_researcher',
        ];
        $key = $map[$metric] ?? null;
        return $key ? ($row[$key] ?? null) : null;
    }

    // ---------- Key Findings ----------
    protected function generateKeyFindings($ranking, $category, $metric, $filters)
    {
        $findings = [];
        if (empty($ranking)) {
            $findings[] = 'No ranking data available for the selected filters.';
            return $findings;
        }

        $top = $ranking[0];
        $metricLabel = $this->getMetricLabel($category, $metric);

        $entityName = $top['name'] ?? $top['title'] ?? $top['area'] ?? 'Entity';
        $entityLabel = $this->getEntityLabel($category);

        $findings[] = "The top {$entityLabel} by {$metricLabel} is {$entityName} with a value of {$top['metric_value']}.";

        if (count($ranking) > 1) {
            $second = $ranking[1];
            $secondName = $second['name'] ?? $second['title'] ?? $second['area'] ?? 'Entity';
            if ($second['metric_value'] == $top['metric_value']) {
                $findings[] = "There is a tie for first place between {$entityName} and {$secondName}.";
            } else {
                $diff = round($top['metric_value'] - $second['metric_value'], 2);
                $findings[] = "The top entity outperforms the second-ranked by {$diff}.";
            }
        }

        if ($filters['year']) {
            $findings[] = "Data is filtered by year: {$filters['year']}.";
        }

        return $findings;
    }

    protected function getMetricLabel($category, $metric)
    {
        $metrics = $this->getMetricsForCategory($category);
        return $metrics[$metric] ?? $metric;
    }

    protected function getEntityLabel($category)
    {
        $map = [
            'researchers' => 'researcher',
            'publications' => 'publication',
            'faculties' => 'faculty',
            'departments' => 'department',
            'universities' => 'university',
            'research_areas' => 'research area',
        ];
        return $map[$category] ?? 'entity';
    }

    public function previewPdf(Request $request)
    {
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId($request);
        $category = $request->input('category', 'researchers');
        $metric = $request->input('metric', 'publications');
        $limit = (int) $request->input('limit', 10);
        $direction = $request->input('direction', 'desc');
        $minPublications = (int) $request->input('minimum_publications', 0);

        $ranking = $this->buildRanking($category, $metric, $filters, $universityId, $limit, $direction, $minPublications);
        $keyFindings = $this->generateKeyFindings($ranking, $category, $metric, $filters);

        $metricLabel = $this->getMetricLabel($category, $metric);

        $date = now()->format('Y-m-d');

        $html = view('pdf.top-10-lists', [
            'category' => $category,
            'metric' => $metric,
            'limit' => $limit,
            'direction' => $direction,
            'minimum_publications' => $minPublications,
            'ranking' => $ranking,
            'key_findings' => $keyFindings,
            'filters' => $filters,
            'date' => $date,
            'is_ministry' => Auth::user() && Auth::user()->role === 'ministry_authority',
            'university_id' => $universityId,
            'metric_label' => $metricLabel,
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
        return response($mpdf->Output('top-10-lists.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}