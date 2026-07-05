<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\AcademicPaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacultyAggregationController extends Controller
{
    public function index()
    {
        // Get all distinct years, ascending
        $years = AcademicPaper::select('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year');

        $faculties = Faculty::all();
        $result = [];

        foreach ($faculties as $faculty) {
            // Base query for papers belonging to this faculty
            $paperQuery = AcademicPaper::query()
                ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
                ->where('lecturers.faculty_id', $faculty->id);

            // Aggregated metrics (single query)
            $metricsData = $paperQuery->clone()
                ->selectRaw('
                    COUNT(*) as publications,
                    SUM(CASE WHEN academic_papers.publication IN ("Book (Authored) Academic", "Book (Authored) Non-academic") THEN 1 ELSE 0 END) as books,
                    SUM(CASE WHEN academic_papers.publication = "Translation Work" THEN 1 ELSE 0 END) as translations,
                    SUM(CASE WHEN academic_papers.indexed IS NOT NULL AND academic_papers.indexed != "" THEN 1 ELSE 0 END) as indexed,
                    SUM(CASE WHEN academic_papers.indexed IS NULL OR academic_papers.indexed = "" THEN 1 ELSE 0 END) as non_indexed,
                    SUM(academic_papers.citation) as citations,
                    SUM(CASE WHEN academic_papers.indexed = "Q1" THEN 1 ELSE 0 END) as Q1,
                    SUM(CASE WHEN academic_papers.indexed = "Q2" THEN 1 ELSE 0 END) as Q2,
                    SUM(CASE WHEN academic_papers.indexed = "Q3" THEN 1 ELSE 0 END) as Q3,
                    SUM(CASE WHEN academic_papers.indexed = "Q4" THEN 1 ELSE 0 END) as Q4,
                    SUM(CASE WHEN academic_papers.publication IN ("International Conference", "Conference Presentation (Abstract/Poster)") AND academic_papers.collaboration LIKE "%International%" THEN 1 ELSE 0 END) as internationalConferences,
                    SUM(CASE WHEN academic_papers.publication IN ("National Conference", "Conference Presentation (Abstract/Poster)") AND academic_papers.collaboration = "National" THEN 1 ELSE 0 END) as nationalConferences,
                    SUM(CASE WHEN academic_papers.publication = "Peer-Reviewed Journal Article" THEN 1 ELSE 0 END) as peerReviewed,
                    SUM(CASE WHEN academic_papers.publication = "Case Study" THEN 1 ELSE 0 END) as caseStudies,
                    SUM(CASE WHEN academic_papers.publication = "Research Report" THEN 1 ELSE 0 END) as researchReports,
                    SUM(CASE WHEN academic_papers.author_position = "1" THEN 1 ELSE 0 END) as firstAuthors,
                    SUM(CASE WHEN academic_papers.author_position = "2" THEN 1 ELSE 0 END) as secondAuthors,
                    SUM(CASE WHEN academic_papers.author_position = "3" THEN 1 ELSE 0 END) as thirdAuthors,
                    SUM(CASE WHEN academic_papers.author_position NOT IN ("1","2","3") THEN 1 ELSE 0 END) as otherAuthors,
                    COUNT(DISTINCT academic_papers.lecturer_id) as totalResearchers
                ')
                ->first();

            // H‑Index – placeholder (0) unless you have a dedicated column
            $hIndex = 0;

            // Leaders (top performers within this faculty)
            $leaders = [
                'topResearcher' => $this->getTopResearcher($faculty->id),
                'mostBooks' => $this->getTopByPublication($faculty->id, 'Book (Authored) Academic'),
                'mostTranslations' => $this->getTopByPublication($faculty->id, 'Translation Work'),
                'indexedJournals' => $this->getTopIndexed($faculty->id),
                'peerReviewed' => $this->getTopByPublication($faculty->id, 'Peer-Reviewed Journal Article'),
                'department' => $this->getTopDepartment($faculty->id),
                'grade' => $this->getTopGrade($faculty->id),
            ];

            // Yearly counts (per faculty)
            $yearly = [];
            foreach ($years as $year) {
                $yearly[$year] = $paperQuery->clone()->where('academic_papers.year', $year)->count();
            }

            // Language counts
            $languages = [
                'pashto' => $paperQuery->clone()->where('academic_papers.language', 'Pashto')->count(),
                'dari'   => $paperQuery->clone()->where('academic_papers.language', 'Dari')->count(),
                'english'=> $paperQuery->clone()->where('academic_papers.language', 'English')->count(),
            ];

            // Funding counts
            $funding = [
                'funded' => $paperQuery->clone()->where('academic_papers.funding', 'Funded')->count(),
                'self'   => $paperQuery->clone()->where('academic_papers.funding', 'Self-Funded')->count(),
            ];

            // Combine metrics
            $metrics = [
                'publications' => $metricsData->publications ?? 0,
                'books' => $metricsData->books ?? 0,
                'translations' => $metricsData->translations ?? 0,
                'indexed' => $metricsData->indexed ?? 0,
                'nonIndexed' => $metricsData->non_indexed ?? 0,
                'citations' => $metricsData->citations ?? 0,
                'hIndex' => $hIndex,
                'Q1' => $metricsData->Q1 ?? 0,
                'Q2' => $metricsData->Q2 ?? 0,
                'Q3' => $metricsData->Q3 ?? 0,
                'Q4' => $metricsData->Q4 ?? 0,
                'internationalConferences' => $metricsData->internationalConferences ?? 0,
                'nationalConferences' => $metricsData->nationalConferences ?? 0,
                'peerReviewed' => $metricsData->peerReviewed ?? 0,
                'caseStudies' => $metricsData->caseStudies ?? 0,
                'researchReports' => $metricsData->researchReports ?? 0,
                'firstAuthors' => $metricsData->firstAuthors ?? 0,
                'secondAuthors' => $metricsData->secondAuthors ?? 0,
                'thirdAuthors' => $metricsData->thirdAuthors ?? 0,
                'otherAuthors' => $metricsData->otherAuthors ?? 0,
                'totalResearchers' => $metricsData->totalResearchers ?? 0,
            ];

            $result[] = [
                'name' => $faculty->facultyname,
                'metrics' => $metrics,
                'leaders' => $leaders,
                'yearly' => $yearly,
                'languages' => $languages,
                'funding' => $funding,
            ];
        }

        return response()->json([
            'years' => $years,
            'faculties' => $result,
        ]);
    }

   // ---------- Leader helper methods (per faculty) – return empty string if none exist ----------

    private function getTopResearcher($facultyId)
    {
        $lecturer = Lecturer::where('faculty_id', $facultyId)
            ->whereHas('academicPapers')  // must have at least one paper
            ->withCount('academicPapers')
            ->orderByDesc('academic_papers_count')
            ->first();
        return $lecturer?->lecturername ?? '-';
    }

    private function getTopByPublication($facultyId, $publicationType)
    {
        $lecturer = Lecturer::where('faculty_id', $facultyId)
            ->whereHas('academicPapers', function ($q) use ($publicationType) {
                $q->where('publication', $publicationType);
            })
            ->withCount(['academicPapers' => function ($q) use ($publicationType) {
                $q->where('publication', $publicationType);
            }])
            ->orderByDesc('academic_papers_count')
            ->first();
        return $lecturer?->lecturername ?? '-';
    }

    private function getTopIndexed($facultyId)
    {
        $lecturer = Lecturer::where('faculty_id', $facultyId)
            ->whereHas('academicPapers', function ($q) {
                $q->whereNotNull('indexed')->where('indexed', '!=', '');
            })
            ->withCount(['academicPapers' => function ($q) {
                $q->whereNotNull('indexed')->where('indexed', '!=', '');
            }])
            ->orderByDesc('academic_papers_count')
            ->first();
        return $lecturer?->lecturername ?? '-';
    }

    private function getTopDepartment($facultyId)
    {
        $dept = DB::table('departments')
            ->join('lecturers', 'departments.id', '=', 'lecturers.department_id')
            ->join('academic_papers', 'lecturers.id', '=', 'academic_papers.lecturer_id')
            ->where('departments.faculty_id', $facultyId)
            ->select('departments.deptname', DB::raw('COUNT(academic_papers.id) as paper_count'))
            ->groupBy('departments.id', 'departments.deptname')
            ->having('paper_count', '>', 0)   // only departments with at least one paper
            ->orderByDesc('paper_count')
            ->first();
        return $dept?->deptname ?? '-';
    }

    private function getTopGrade($facultyId)
    {
        $grade = Lecturer::where('faculty_id', $facultyId)
            ->whereHas('academicPapers')   // must have at least one paper
            ->join('academic_papers', 'lecturers.id', '=', 'academic_papers.lecturer_id')
            ->select('lecturers.grade', DB::raw('COUNT(academic_papers.id) as paper_count'))
            ->groupBy('lecturers.grade')
            ->having('paper_count', '>', 0)
            ->orderByDesc('paper_count')
            ->first();
        return $grade?->grade ?? '-';
    }
}