<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\AcademicPaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FacultyAggregationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isMinistry = $user && $user->role === 'ministry_authority';

        // Determine if we filter by university
        $universityId = $isMinistry ? null : currentUniversityId();

        // ---------- 1. Get years (filtered by university only if not ministry) ----------
        $yearsQuery = AcademicPaper::query();
        if ($universityId) {
            $yearsQuery->whereHas('lecturer.faculty', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }
        $years = $yearsQuery->select('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year');

        // ---------- 2. Get faculties (filtered by university only if not ministry) ----------
        $faculties = Faculty::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();

        // ---------- 3. If ministry, group by faculty name ----------
        if ($isMinistry) {
            // Group faculties by name
            $groups = $faculties->groupBy('facultyname');

            $result = [];
            foreach ($groups as $name => $facultyGroup) {
                $facultyIds = $facultyGroup->pluck('id')->toArray();

                // Build aggregated data for this name group
                $aggregated = $this->buildAggregatedData($facultyIds, $years);
                $aggregated['name'] = $name;
                $result[] = $aggregated;
            }

            return response()->json([
                'years'     => $years,
                'faculties' => $result,
            ]);
        }

        // ---------- 4. Non-ministry: existing per-faculty logic ----------
        $result = [];
        foreach ($faculties as $faculty) {
            $data = $this->buildFacultyData($faculty->id, $years);
            if ($data) {
                $data['name'] = $faculty->facultyname;
                $result[] = $data;
            }
        }

        return response()->json([
            'years'     => $years,
            'faculties' => $result,
        ]);
    }

    /**
     * Build aggregated data for a group of faculty IDs (used for ministry_authority).
     */
    private function buildAggregatedData(array $facultyIds, $years)
    {
        // Base query for papers belonging to any faculty in the group
        $paperQuery = AcademicPaper::query()
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->whereIn('lecturers.faculty_id', $facultyIds);

        // ---------- Metrics ----------
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

        // H‑Index – placeholder (0)
        $hIndex = 0;

        // ---------- Leaders (computed across the whole group) ----------
        $leaders = [
            'topResearcher'     => $this->getTopResearcher($facultyIds),
            'mostBooks'         => $this->getTopByPublication($facultyIds, 'Book (Authored) Academic'),
            'mostTranslations'  => $this->getTopByPublication($facultyIds, 'Translation Work'),
            'indexedJournals'   => $this->getTopIndexed($facultyIds),
            'peerReviewed'      => $this->getTopByPublication($facultyIds, 'Peer-Reviewed Journal Article'),
            'department'        => $this->getTopDepartment($facultyIds),
            'grade'             => $this->getTopGrade($facultyIds),
        ];

        // ---------- Yearly counts ----------
        $yearly = [];
        foreach ($years as $year) {
            $yearly[$year] = $paperQuery->clone()->where('academic_papers.year', $year)->count();
        }

        // ---------- Language counts ----------
        $languages = [
            'pashto'  => $paperQuery->clone()->where('academic_papers.language', 'Pashto')->count(),
            'dari'    => $paperQuery->clone()->where('academic_papers.language', 'Dari')->count(),
            'english' => $paperQuery->clone()->where('academic_papers.language', 'English')->count(),
        ];

        // ---------- Funding counts ----------
        $funding = [
            'funded' => $paperQuery->clone()->where('academic_papers.funding', 'Funded')->count(),
            'self'   => $paperQuery->clone()->where('academic_papers.funding', 'Self-Funded')->count(),
        ];

        // ---------- Build final structure ----------
        return [
            'metrics'   => [
                'publications'            => $metricsData->publications ?? 0,
                'books'                   => $metricsData->books ?? 0,
                'translations'            => $metricsData->translations ?? 0,
                'indexed'                 => $metricsData->indexed ?? 0,
                'nonIndexed'              => $metricsData->non_indexed ?? 0,
                'citations'               => $metricsData->citations ?? 0,
                'hIndex'                  => $hIndex,
                'Q1'                      => $metricsData->Q1 ?? 0,
                'Q2'                      => $metricsData->Q2 ?? 0,
                'Q3'                      => $metricsData->Q3 ?? 0,
                'Q4'                      => $metricsData->Q4 ?? 0,
                'internationalConferences'=> $metricsData->internationalConferences ?? 0,
                'nationalConferences'     => $metricsData->nationalConferences ?? 0,
                'peerReviewed'            => $metricsData->peerReviewed ?? 0,
                'caseStudies'             => $metricsData->caseStudies ?? 0,
                'researchReports'         => $metricsData->researchReports ?? 0,
                'firstAuthors'            => $metricsData->firstAuthors ?? 0,
                'secondAuthors'           => $metricsData->secondAuthors ?? 0,
                'thirdAuthors'            => $metricsData->thirdAuthors ?? 0,
                'otherAuthors'            => $metricsData->otherAuthors ?? 0,
                'totalResearchers'        => $metricsData->totalResearchers ?? 0,
            ],
            'leaders'   => $leaders,
            'yearly'    => $yearly,
            'languages' => $languages,
            'funding'   => $funding,
        ];
    }

    /**
     * Build data for a single faculty (used for non-ministry users).
     */
    private function buildFacultyData($facultyId, $years)
    {
        // Base query for papers belonging to this faculty
        $paperQuery = AcademicPaper::query()
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->where('lecturers.faculty_id', $facultyId);

        // ---------- Metrics ----------
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

        $hIndex = 0; // placeholder

        // ---------- Leaders (for a single faculty) ----------
        // We use helper methods that accept a single ID by passing an array with one ID.
        $facultyIds = [$facultyId];
        $leaders = [
            'topResearcher'     => $this->getTopResearcher($facultyIds),
            'mostBooks'         => $this->getTopByPublication($facultyIds, 'Book (Authored) Academic'),
            'mostTranslations'  => $this->getTopByPublication($facultyIds, 'Translation Work'),
            'indexedJournals'   => $this->getTopIndexed($facultyIds),
            'peerReviewed'      => $this->getTopByPublication($facultyIds, 'Peer-Reviewed Journal Article'),
            'department'        => $this->getTopDepartment($facultyIds),
            'grade'             => $this->getTopGrade($facultyIds),
        ];

        // ---------- Yearly counts ----------
        $yearly = [];
        foreach ($years as $year) {
            $yearly[$year] = $paperQuery->clone()->where('academic_papers.year', $year)->count();
        }

        // ---------- Language counts ----------
        $languages = [
            'pashto'  => $paperQuery->clone()->where('academic_papers.language', 'Pashto')->count(),
            'dari'    => $paperQuery->clone()->where('academic_papers.language', 'Dari')->count(),
            'english' => $paperQuery->clone()->where('academic_papers.language', 'English')->count(),
        ];

        // ---------- Funding counts ----------
        $funding = [
            'funded' => $paperQuery->clone()->where('academic_papers.funding', 'Funded')->count(),
            'self'   => $paperQuery->clone()->where('academic_papers.funding', 'Self-Funded')->count(),
        ];

        return [
            'metrics'   => [
                'publications'            => $metricsData->publications ?? 0,
                'books'                   => $metricsData->books ?? 0,
                'translations'            => $metricsData->translations ?? 0,
                'indexed'                 => $metricsData->indexed ?? 0,
                'nonIndexed'              => $metricsData->non_indexed ?? 0,
                'citations'               => $metricsData->citations ?? 0,
                'hIndex'                  => $hIndex,
                'Q1'                      => $metricsData->Q1 ?? 0,
                'Q2'                      => $metricsData->Q2 ?? 0,
                'Q3'                      => $metricsData->Q3 ?? 0,
                'Q4'                      => $metricsData->Q4 ?? 0,
                'internationalConferences'=> $metricsData->internationalConferences ?? 0,
                'nationalConferences'     => $metricsData->nationalConferences ?? 0,
                'peerReviewed'            => $metricsData->peerReviewed ?? 0,
                'caseStudies'             => $metricsData->caseStudies ?? 0,
                'researchReports'         => $metricsData->researchReports ?? 0,
                'firstAuthors'            => $metricsData->firstAuthors ?? 0,
                'secondAuthors'           => $metricsData->secondAuthors ?? 0,
                'thirdAuthors'            => $metricsData->thirdAuthors ?? 0,
                'otherAuthors'            => $metricsData->otherAuthors ?? 0,
                'totalResearchers'        => $metricsData->totalResearchers ?? 0,
            ],
            'leaders'   => $leaders,
            'yearly'    => $yearly,
            'languages' => $languages,
            'funding'   => $funding,
        ];
    }

    // ---------- Leader helper methods (accept array of faculty IDs) ----------

    private function getTopResearcher($facultyIds)
    {
        $lecturer = Lecturer::whereIn('faculty_id', $facultyIds)
            ->whereHas('academicPapers')
            ->withCount('academicPapers')
            ->orderByDesc('academic_papers_count')
            ->first();
        return $lecturer?->lecturername ?? '-';
    }

    private function getTopByPublication($facultyIds, $publicationType)
    {
        $lecturer = Lecturer::whereIn('faculty_id', $facultyIds)
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

    private function getTopIndexed($facultyIds)
    {
        $lecturer = Lecturer::whereIn('faculty_id', $facultyIds)
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

    private function getTopDepartment($facultyIds)
    {
        $dept = DB::table('departments')
            ->join('lecturers', 'departments.id', '=', 'lecturers.department_id')
            ->join('academic_papers', 'lecturers.id', '=', 'academic_papers.lecturer_id')
            ->whereIn('departments.faculty_id', $facultyIds)
            ->select('departments.deptname', DB::raw('COUNT(academic_papers.id) as paper_count'))
            ->groupBy('departments.id', 'departments.deptname')
            ->having('paper_count', '>', 0)
            ->orderByDesc('paper_count')
            ->first();
        return $dept?->deptname ?? '-';
    }

    private function getTopGrade($facultyIds)
    {
        $grade = Lecturer::whereIn('faculty_id', $facultyIds)
            ->whereHas('academicPapers')
            ->join('academic_papers', 'lecturers.id', '=', 'academic_papers.lecturer_id')
            ->select('lecturers.grade', DB::raw('COUNT(academic_papers.id) as paper_count'))
            ->groupBy('lecturers.grade')
            ->having('paper_count', '>', 0)
            ->orderByDesc('paper_count')
            ->first();
        return $grade?->grade ?? '-';
    }
}