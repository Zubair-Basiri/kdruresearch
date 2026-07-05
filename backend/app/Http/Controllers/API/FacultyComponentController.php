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
        $facultyId = $request->input('faculty_id');
        $year = $request->input('year');

        // Build cache key based on filters
        $cacheKey = "dashboard_data_{$facultyId}_{$year}";
        
        // Cache for 10 minutes (adjust as needed)
        $data = Cache::remember($cacheKey, 600, function () use ($facultyId, $year) {
            return $this->buildDashboardData($facultyId, $year);
        });

        return response()->json($data);
    }

    private function buildDashboardData($facultyId, $year)
    {
        // ===== 1. MAIN AGGREGATION QUERY =====
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
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Book (Authored)" THEN 1 ELSE 0 END) as books_authored_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Book (Edited Volume)" THEN 1 ELSE 0 END) as books_translated_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Non-Indexed (Conference Proceedings)" THEN 1 ELSE 0 END) as non_indexed_national_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "National Publication" THEN 1 ELSE 0 END) as national_publications_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "International Indexed Conference" THEN 1 ELSE 0 END) as international_indexed_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.indexed = "Non-Indexed (Conference Proceedings)" THEN 1 ELSE 0 END) as international_non_indexed_conferences_count'),
                DB::raw('SUM(CASE WHEN academic_papers.status = "Published" THEN 1 ELSE 0 END) as books_published_academic_count'),
                DB::raw('SUM(CASE WHEN academic_papers.publication = "Non‑academic Book" THEN 1 ELSE 0 END) as books_published_non_academic_count'),
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

        // Apply faculty filter
        if ($facultyId) {
            $query->where('faculties.id', $facultyId);
        }

        // Apply year filter if provided
        if ($year) {
            $query->where('academic_papers.year', $year);
        }

        $aggregated = $query->get();

        // ===== 2. DETERMINE WHICH YEARS TO RETURN =====
        // If a specific year was requested, use only that year.
        // Otherwise, get the last 3 years present in the data.
        $allYears = $aggregated->pluck('year')->unique()->sort()->values();
        if ($year) {
            $years = collect([$year]);
        } else {
            // Take last 3 years (most recent first, then reverse for chronological order)
            $years = $allYears->sortDesc()->take(3)->sort();
        }

        // Filter aggregated rows to only the selected years
        $filteredRows = $aggregated->filter(fn($row) => $years->contains($row->year));

        // ===== 3. BUILD DATA MAP FOR FAST LOOKUP =====
        $dataMap = [];
        foreach ($filteredRows as $row) {
            $dataMap[$row->faculty_id][$row->year] = $row;
        }

        // ===== 4. TOTAL RESEARCHERS (with same filters) =====
        $researcherQuery = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id');

        if ($facultyId) {
            $researcherQuery->where('faculties.id', $facultyId);
        }
        if ($year) {
            $researcherQuery->where('academic_papers.year', $year);
        } else {
            // If no year filter, count researchers across all years (or you might want to limit to same years as charts)
            // Here we keep it as original: distinct lecturers across all papers.
        }
        $totalResearchers = $researcherQuery->distinct()->count('academic_papers.lecturer_id');

        // ===== 5. KPIs (sum over filtered rows) =====
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
            'h_index' => round($filteredRows->avg('avg_citation')), // simple approximation
            'total_citation' => $filteredRows->sum('total_citation'),
            'first_authors' => $filteredRows->sum('first_authors_count'),
            'second_authors' => $filteredRows->sum('second_authors_count'),
            'third_authors' => $filteredRows->sum('third_authors_count'),
            'other_authors' => $filteredRows->sum('other_authors_count'),
            'total_case_studies' => $filteredRows->sum('total_case_studies_count'),
            'total_research_reports' => $filteredRows->sum('total_research_reports_count'),
        ];

        // ===== 6. CHART DATA =====
        $facultyNames = Faculty::orderBy('facultyname')->pluck('facultyname', 'id');
        
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

    public function getFilters()
    {
        // This can also be cached
        $faculties = Faculty::select('id', 'facultyname')->get();
        $years = AcademicPaper::distinct()->orderBy('year', 'desc')->pluck('year');

        return response()->json([
            'faculties' => $faculties,
            'years'     => $years,
        ]);
    }
}