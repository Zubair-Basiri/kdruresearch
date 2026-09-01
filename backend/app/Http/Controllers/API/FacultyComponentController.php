<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\AcademicPaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FacultyComponentController extends Controller
{
    public function getData(Request $request)
    {
        $user = auth()->user();
        $facultyId = $request->input('faculty_id');
        $year = $request->input('year');
        $universityId = currentUniversityId();

        // --- GUEST VALIDATION ---
        if ($user && $user->email === 'guest@example.com' && $user->role === 'user') {
            if (is_null($universityId)) {
                return response()->json(['message' => 'Please select a university first.'], 422);
            }
            // If a specific faculty is requested, ensure it belongs to the guest's university
            if ($facultyId) {
                $faculty = Faculty::where('id', $facultyId)->where('university_id', $universityId)->first();
                if (!$faculty) {
                    return response()->json(['message' => 'Unauthorized faculty for this university.'], 403);
                }
            }
        }
        // --- END GUEST ---

        $isMinistryAll = ($user && $user->role === 'ministry_authority' && empty($facultyId));

        $cacheKey = "dashboard_data_{$universityId}_{$facultyId}_{$year}";
        if ($isMinistryAll) {
            $cacheKey = "dashboard_data_ministry_all_{$year}";
        }

        $data = Cache::remember($cacheKey, 600, function () use ($facultyId, $year, $universityId, $isMinistryAll) {
            return $this->buildDashboardData($facultyId, $year, $universityId, $isMinistryAll);
        });

        return response()->json($data);
    }

    private function buildDashboardData($facultyId, $year, $universityId, $isMinistryAll = false)
    {
        if ($isMinistryAll) {
            return $this->buildMinistryAllData($year);
        }

        // ===== EXISTING LOGIC (unchanged) =====
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->select(
                'faculties.id as faculty_id',
                'faculties.facultyname as faculty_name',
                'academic_papers.year',
                DB::raw('COUNT(*) as total_publications'),
                DB::raw('SUM(CAST(REGEXP_REPLACE(academic_papers.funding, "[^0-9.]", "") AS DECIMAL(10,2))) as total_funding'),
                DB::raw('COUNT(DISTINCT academic_papers.lecturer_id) as total_researchers'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q1" THEN 1 ELSE 0 END) as q1_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q2" THEN 1 ELSE 0 END) as q2_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q3" THEN 1 ELSE 0 END) as q3_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q4" THEN 1 ELSE 0 END) as q4_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Peer-Reviewed Journal Article" THEN 1 ELSE 0 END) as peer_reviewed_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Book (Authored) Academic" THEN 1 ELSE 0 END) as books_authored_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Translation Work" THEN 1 ELSE 0 END) as books_translated_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Non-Indexed (Conference Proceedings)" THEN 1 ELSE 0 END) as non_indexed_national_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.collaboration = "National" THEN 1 ELSE 0 END) as national_publications_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Indexed (Conference Proceedings)" THEN 1 ELSE 0 END) as international_indexed_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Non-Indexed (Conference Proceedings)" THEN 1 ELSE 0 END) as international_non_indexed_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Book (Authored) Academic" AND academic_papers.status = "Published" THEN 1 ELSE 0 END) as books_published_academic_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Book (Authored) Non-academic" AND academic_papers.status = "Published" THEN 1 ELSE 0 END) as books_published_non_academic_count'),
                DB::raw('AVG(academic_papers.citation) as avg_citation'),
                DB::raw('SUM(academic_papers.citation) as total_citation'),
                DB::raw('SUM(CASE WHEN academic_papers.author_position = "1" THEN 1 ELSE 0 END) as first_authors_count'),
                DB::raw('SUM(CASE WHEN academic_papers.author_position = "2" THEN 1 ELSE 0 END) as second_authors_count'),
                DB::raw('SUM(CASE WHEN academic_papers.author_position = "3" THEN 1 ELSE 0 END) as third_authors_count'),
                DB::raw('SUM(CASE WHEN academic_papers.author_position NOT IN ("1", "2", "3") THEN 1 ELSE 0 END) as other_authors_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Case Study" THEN 1 ELSE 0 END) as total_case_studies_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Research Report" THEN 1 ELSE 0 END) as total_research_reports_count')
            )
            ->groupBy('faculties.id', 'faculties.facultyname', 'academic_papers.year');

        if ($universityId) {
            $query->where('faculties.university_id', $universityId);
        }
        if ($facultyId) {
            $query->where('faculties.id', $facultyId);
        }
        if ($year) {
            $query->where('academic_papers.year', $year);
        }

        $aggregated = $query->get();

        $allYears = $aggregated->pluck('year')->unique()->sort()->values();
        if ($year) {
            $years = collect([$year]);
        } else {
            $years = $allYears->sortDesc()->take(3)->sort();
        }

        $filteredRows = $aggregated->filter(fn($row) => $years->contains($row->year));

        $dataMap = [];
        foreach ($filteredRows as $row) {
            $dataMap[$row->faculty_id][$row->year] = $row;
        }

        $researcherQuery = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id');
        if ($universityId) {
            $researcherQuery->where('faculties.university_id', $universityId);
        }
        if ($facultyId) {
            $researcherQuery->where('faculties.id', $facultyId);
        }
        if ($year) {
            $researcherQuery->where('academic_papers.year', $year);
        }
        $totalResearchers = $researcherQuery->distinct()->count('academic_papers.lecturer_id');

        $kpis = [
            'publications' => $filteredRows->sum('total_publications'),
            'funding'      => $filteredRows->sum('total_funding'),
            'researchers'  => $totalResearchers,
            'q1'           => $filteredRows->sum('q1_count'),
            'q2'           => $filteredRows->sum('q2_count'),
            'q3'           => $filteredRows->sum('q3_count'),
            'q4'           => $filteredRows->sum('q4_count'),
            'peer_reviewed' => $filteredRows->sum('peer_reviewed_count'),
            'books_authored' => $filteredRows->sum('books_authored_count'),
            'books_translated' => $filteredRows->sum('books_translated_count'),
            'non_indexed_national_conferences' => $filteredRows->sum('non_indexed_national_conferences_count'),
            'national_publications' => $filteredRows->sum('national_publications_count'),
            'international_indexed_conferences' => $filteredRows->sum('international_indexed_conferences_count'),
            'international_non_indexed_conferences' => $filteredRows->sum('international_non_indexed_conferences_count'),
            'books_published' => $filteredRows->sum('books_published_academic_count'),
            'books_published_non_academic' => $filteredRows->sum('books_published_non_academic_count'),
            'h_index' => round($filteredRows->avg('avg_citation')),
            'total_citation' => $filteredRows->sum('total_citation'),
            'first_authors' => $filteredRows->sum('first_authors_count'),
            'second_authors' => $filteredRows->sum('second_authors_count'),
            'third_authors' => $filteredRows->sum('third_authors_count'),
            'other_authors' => $filteredRows->sum('other_authors_count'),
            'total_case_studies' => $filteredRows->sum('total_case_studies_count'),
            'total_research_reports' => $filteredRows->sum('total_research_reports_count'),
        ];

        // ===== FACULTY NAMES FOR CHART CATEGORIES =====
        $facultyQuery = Faculty::orderBy('facultyname');
        if ($universityId) {
            $facultyQuery->where('university_id', $universityId);
        }

        // --- NEW: Ministry Authority with specific faculty → only show that faculty in charts ---
        $user = auth()->user();
        if ($user && $user->role === 'ministry_authority' && $facultyId) {
            $facultyQuery->where('id', $facultyId);
        }
        // --- END NEW ---

        $facultyNames = $facultyQuery->pluck('facultyname', 'id');

        $metrics = [
            'publications' => 'total_publications',
            'funding'      => 'total_funding',
            'researchers'  => 'total_researchers',
            'q1'           => 'q1_count',
            'q2'           => 'q2_count',
            'q3'           => 'q3_count',
            'q4'           => 'q4_count',
            'peer_reviewed' => 'peer_reviewed_count',
            'books_authored' => 'books_authored_count',
            'books_translated' => 'books_translated_count',
            'non_indexed_national_conferences' => 'non_indexed_national_conferences_count',
            'national_publications' => 'national_publications_count',
            'international_indexed_conferences' => 'international_indexed_conferences_count',
            'international_non_indexed_conferences' => 'international_non_indexed_conferences_count',
            'books_published' => 'books_published_academic_count',
            'books_published_non_academic' => 'books_published_non_academic_count',
            'total_citation' => 'total_citation',
            'first_authors' => 'first_authors_count',
            'second_authors' => 'second_authors_count',
            'third_authors' => 'third_authors_count',
            'other_authors' => 'other_authors_count',
            'total_case_studies' => 'total_case_studies_count',
            'total_research_reports' => 'total_research_reports_count',
        ];

        $chartData = [];
        foreach ($metrics as $chartKey => $dbColumn) {
            $series = [];
            foreach ($years as $yr) {
                $dataForYear = [];
                foreach ($facultyNames as $fid => $fname) {
                    $row = $dataMap[$fid][$yr] ?? null;
                    $dataForYear[] = $row ? (float) $row->$dbColumn : 0;
                }
                $series[] = [
                    'name' => (string) $yr,
                    'data' => $dataForYear
                ];
            }
            $chartData[$chartKey] = [
                'categories' => array_values($facultyNames->toArray()),
                'series'     => $series
            ];
        }

        return [
            'kpis'       => $kpis,
            'chart_data' => $chartData,
        ];
    }

    /**
     * Ministry Authority – All Faculties: aggregate by faculty name across all universities.
     */
    private function buildMinistryAllData($year)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->select(
                'faculties.facultyname as faculty_name',
                'academic_papers.year',
                DB::raw('COUNT(*) as total_publications'),
                DB::raw('SUM(CAST(REGEXP_REPLACE(academic_papers.funding, "[^0-9.]", "") AS DECIMAL(10,2))) as total_funding'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q1" THEN 1 ELSE 0 END) as q1_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q2" THEN 1 ELSE 0 END) as q2_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q3" THEN 1 ELSE 0 END) as q3_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Q4" THEN 1 ELSE 0 END) as q4_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Peer-Reviewed Journal Article" THEN 1 ELSE 0 END) as peer_reviewed_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Book (Authored) Academic" THEN 1 ELSE 0 END) as books_authored_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Translation Work" THEN 1 ELSE 0 END) as books_translated_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Non-Indexed (Conference Proceedings)" THEN 1 ELSE 0 END) as non_indexed_national_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.collaboration = "National" THEN 1 ELSE 0 END) as national_publications_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Indexed (Conference Proceedings)" THEN 1 ELSE 0 END) as international_indexed_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Non-Indexed (Conference Proceedings)" THEN 1 ELSE 0 END) as international_non_indexed_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Book (Authored) Academic" AND academic_papers.status = "Published" THEN 1 ELSE 0 END) as books_published_academic_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Book (Authored) Non-academic" AND academic_papers.status = "Published" THEN 1 ELSE 0 END) as books_published_non_academic_count'),
                DB::raw('AVG(academic_papers.citation) as avg_citation'),
                DB::raw('SUM(academic_papers.citation) as total_citation'),
                DB::raw('SUM(CASE WHEN academic_papers.author_position = "1" THEN 1 ELSE 0 END) as first_authors_count'),
                DB::raw('SUM(CASE WHEN academic_papers.author_position = "2" THEN 1 ELSE 0 END) as second_authors_count'),
                DB::raw('SUM(CASE WHEN academic_papers.author_position = "3" THEN 1 ELSE 0 END) as third_authors_count'),
                DB::raw('SUM(CASE WHEN academic_papers.author_position NOT IN ("1", "2", "3") THEN 1 ELSE 0 END) as other_authors_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Case Study" THEN 1 ELSE 0 END) as total_case_studies_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Research Report" THEN 1 ELSE 0 END) as total_research_reports_count')
            )
            ->groupBy('faculties.facultyname', 'academic_papers.year');

        if ($year) {
            $query->where('academic_papers.year', $year);
        }

        $aggregated = $query->get();

        $allYears = $aggregated->pluck('year')->unique()->sort()->values();
        if ($year) {
            $years = collect([$year]);
        } else {
            $years = $allYears->sortDesc()->take(3)->sort();
        }

        $filteredRows = $aggregated->filter(fn($row) => $years->contains($row->year));

        $dataMap = [];
        foreach ($filteredRows as $row) {
            $dataMap[$row->faculty_name][$row->year] = $row;
        }

        $researcherQuery = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id');
        if ($year) {
            $researcherQuery->where('academic_papers.year', $year);
        }
        $totalResearchers = $researcherQuery->distinct()->count('academic_papers.lecturer_id');

        $kpis = [
            'publications' => $filteredRows->sum('total_publications'),
            'funding'      => $filteredRows->sum('total_funding'),
            'researchers'  => $totalResearchers,
            'q1'           => $filteredRows->sum('q1_count'),
            'q2'           => $filteredRows->sum('q2_count'),
            'q3'           => $filteredRows->sum('q3_count'),
            'q4'           => $filteredRows->sum('q4_count'),
            'peer_reviewed' => $filteredRows->sum('peer_reviewed_count'),
            'books_authored' => $filteredRows->sum('books_authored_count'),
            'books_translated' => $filteredRows->sum('books_translated_count'),
            'non_indexed_national_conferences' => $filteredRows->sum('non_indexed_national_conferences_count'),
            'national_publications' => $filteredRows->sum('national_publications_count'),
            'international_indexed_conferences' => $filteredRows->sum('international_indexed_conferences_count'),
            'international_non_indexed_conferences' => $filteredRows->sum('international_non_indexed_conferences_count'),
            'books_published' => $filteredRows->sum('books_published_academic_count'),
            'books_published_non_academic' => $filteredRows->sum('books_published_non_academic_count'),
            'h_index' => round($filteredRows->avg('avg_citation')),
            'total_citation' => $filteredRows->sum('total_citation'),
            'first_authors' => $filteredRows->sum('first_authors_count'),
            'second_authors' => $filteredRows->sum('second_authors_count'),
            'third_authors' => $filteredRows->sum('third_authors_count'),
            'other_authors' => $filteredRows->sum('other_authors_count'),
            'total_case_studies' => $filteredRows->sum('total_case_studies_count'),
            'total_research_reports' => $filteredRows->sum('total_research_reports_count'),
        ];

        $facultyNames = $filteredRows->pluck('faculty_name')->unique()->sort()->values();

        $metrics = [
            'publications' => 'total_publications',
            'funding'      => 'total_funding',
            'q1'           => 'q1_count',
            'q2'           => 'q2_count',
            'q3'           => 'q3_count',
            'q4'           => 'q4_count',
            'peer_reviewed' => 'peer_reviewed_count',
            'books_authored' => 'books_authored_count',
            'books_translated' => 'books_translated_count',
            'non_indexed_national_conferences' => 'non_indexed_national_conferences_count',
            'national_publications' => 'national_publications_count',
            'international_indexed_conferences' => 'international_indexed_conferences_count',
            'international_non_indexed_conferences' => 'international_non_indexed_conferences_count',
            'books_published' => 'books_published_academic_count',
            'books_published_non_academic' => 'books_published_non_academic_count',
            'total_citation' => 'total_citation',
            'first_authors' => 'first_authors_count',
            'second_authors' => 'second_authors_count',
            'third_authors' => 'third_authors_count',
            'other_authors' => 'other_authors_count',
            'total_case_studies' => 'total_case_studies_count',
            'total_research_reports' => 'total_research_reports_count',
        ];

        $chartData = [];
        foreach ($metrics as $chartKey => $dbColumn) {
            $series = [];
            foreach ($years as $yr) {
                $dataForYear = [];
                foreach ($facultyNames as $fname) {
                    $row = $dataMap[$fname][$yr] ?? null;
                    $dataForYear[] = $row ? (float) $row->$dbColumn : 0;
                }
                $series[] = [
                    'name' => (string) $yr,
                    'data' => $dataForYear
                ];
            }
            $chartData[$chartKey] = [
                'categories' => $facultyNames->toArray(),
                'series'     => $series
            ];
        }

        // Researchers chart (per faculty name per year)
        $researcherData = [];
        foreach ($years as $yr) {
            $subQuery = DB::table('academic_papers')
                ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
                ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
                ->select('faculties.facultyname as faculty_name', DB::raw('COUNT(DISTINCT academic_papers.lecturer_id) as researcher_count'))
                ->where('academic_papers.year', $yr)
                ->groupBy('faculties.facultyname')
                ->get()
                ->keyBy('faculty_name');
            $researcherData[$yr] = $subQuery;
        }

        $researcherSeries = [];
        foreach ($years as $yr) {
            $dataForYear = [];
            foreach ($facultyNames as $fname) {
                $dataForYear[] = (float) ($researcherData[$yr][$fname]->researcher_count ?? 0);
            }
            $researcherSeries[] = [
                'name' => (string) $yr,
                'data' => $dataForYear
            ];
        }
        $chartData['researchers'] = [
            'categories' => $facultyNames->toArray(),
            'series'     => $researcherSeries
        ];

        return [
            'kpis'       => $kpis,
            'chart_data' => $chartData,
        ];
    }

    public function getFilters()
    {
        $user = auth()->user();
        $universityId = currentUniversityId();

        // --- GUEST VALIDATION ---
        if ($user && $user->email === 'guest@example.com' && $user->role === 'user') {
            if (is_null($universityId)) {
                return response()->json(['message' => 'Please select a university first.'], 422);
            }
        }
        // --- END GUEST ---

        if ($user && $user->role === 'ministry_authority') {
            $faculties = Faculty::with('university')
                ->select('id', 'facultyname', 'university_id')
                ->orderBy('facultyname')
                ->get()
                ->map(function ($faculty) {
                    return [
                        'id'              => $faculty->id,
                        'facultyname'     => $faculty->facultyname,
                        'university_name' => $faculty->university ? $faculty->university->name : '',
                    ];
                });
        } else {
            $facultyQuery = Faculty::select('id', 'facultyname');
            if ($universityId) {
                $facultyQuery->where('university_id', $universityId);
            }
            $faculties = $facultyQuery->orderBy('facultyname')->get();
        }

        $yearsQuery = AcademicPaper::distinct()->orderBy('year', 'desc');
        if ($universityId && !($user && $user->role === 'ministry_authority')) {
            $yearsQuery->whereHas('lecturer.faculty', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }
        $years = $yearsQuery->pluck('year');

        return response()->json([
            'faculties' => $faculties,
            'years'     => $years,
        ]);
    }
}