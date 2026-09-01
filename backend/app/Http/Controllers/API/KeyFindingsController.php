<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Hekmatinasser\Verta\Verta;
use Mpdf\Mpdf;

class KeyFindingsController extends Controller
{
    // Add this method to the existing filters() method
    public function filters()
    {
        $universityId = currentUniversityId();

        // Faculties – filter by university if not null
        $facultiesQuery = DB::table('faculties')
            ->orderBy('facultyname');
        if ($universityId) {
            $facultiesQuery->where('university_id', $universityId);
        }
        $faculties = $facultiesQuery->pluck('facultyname', 'id');

        // Departments – filter by university via faculty
        $departmentsQuery = DB::table('departments')
            ->join('faculties', 'departments.faculty_id', '=', 'faculties.id')
            ->orderBy('departments.deptname');
        if ($universityId) {
            $departmentsQuery->where('faculties.university_id', $universityId);
        }
        $departments = $departmentsQuery->pluck('departments.deptname', 'departments.id');

        // Lecturers – filter by university
        $lecturersQuery = DB::table('lecturers')
            ->orderBy('lecturername');
        if ($universityId) {
            $lecturersQuery->where('university_id', $universityId);
        }
        $lecturers = $lecturersQuery;

        // Grades (distinct)
        $grades = (clone $lecturersQuery)->distinct()->orderBy('grade')->pluck('grade');

        // Qualifications (distinct)
        $qualifications = (clone $lecturersQuery)->distinct()->orderBy('qualification')->pluck('qualification');

        // Researcher names
        $researcherNames = (clone $lecturersQuery)->orderBy('lecturername')->pluck('lecturername');

        // Research areas – from lecturers, filtered by university
        $areas = DB::table('lecturers')
            ->whereNotNull('specialized_area')
            ->when($universityId, function ($q) use ($universityId) {
                return $q->where('university_id', $universityId);
            })
            ->pluck('specialized_area');

        $uniqueAreas = [];
        foreach ($areas as $areaJson) {
            $arr = json_decode($areaJson, true) ?: [];
            foreach ($arr as $item) {
                $uniqueAreas[trim($item)] = true;
            }
        }
        ksort($uniqueAreas);
        $researchAreas = array_keys($uniqueAreas);

        // Academic papers – filter by university via lecturer
        $papersQuery = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->when($universityId, function ($q) use ($universityId) {
                return $q->where('faculties.university_id', $universityId);
            });

        // Years (distinct, descending)
        $years = (clone $papersQuery)->distinct()->orderBy('academic_papers.year', 'desc')->pluck('academic_papers.year');

        // Publication types
        $publicationTypes = (clone $papersQuery)->distinct()->orderBy('academic_papers.publication')->pluck('academic_papers.publication');

        // Indexes
        $indexes = (clone $papersQuery)->distinct()->orderBy('academic_papers.indexed')->pluck('academic_papers.indexed');

        // Funding sources
        $fundingSources = (clone $papersQuery)->distinct()->orderBy('academic_papers.funding')->pluck('academic_papers.funding');

        // Statuses
        $statuses = (clone $papersQuery)->distinct()->orderBy('academic_papers.status')->pluck('academic_papers.status');

        // Collaborations
        $collaborations = (clone $papersQuery)->distinct()->orderBy('academic_papers.collaboration')->pluck('academic_papers.collaboration');

        // Author positions
        $authorPositions = (clone $papersQuery)->distinct()->orderBy('academic_papers.author_position')->pluck('academic_papers.author_position');

        return response()->json([
            'faculties'       => $faculties,
            'departments'     => $departments,
            'grades'          => $grades,
            'qualifications'  => $qualifications,
            'years'           => $years,
            'publication_types' => $publicationTypes,
            'indexes'         => $indexes,
            'funding_sources' => $fundingSources,
            'statuses'        => $statuses,
            'collaborations'  => $collaborations,
            'author_positions'=> $authorPositions,
            'research_areas'  => $researchAreas,
            'researcher_names' => $researcherNames,
        ]);
    }

    // Update index method to include researcher name search
    public function index(Request $request)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->join('departments', 'lecturers.department_id', '=', 'departments.id')
            ->select(
                'lecturers.lecturername as researcher_name',
                'academic_papers.title as title',
                'faculties.facultyname as faculty',
                'departments.deptname as department',
                'lecturers.grade',
                'lecturers.qualification as education',
                'academic_papers.year',
                'academic_papers.publication as publication_type',
                'academic_papers.indexed as index',
                'academic_papers.funding',
                'academic_papers.status',
                'academic_papers.collaboration',
                'academic_papers.author_position',
                'lecturers.specialized_area as research_area',
                'academic_papers.language as language'
            );

        $universityId = currentUniversityId();
        if ($universityId) {
            $query->where('faculties.university_id', $universityId);
        }

        // Year range
        if ($request->filled('start_year')) {
            $query->where('academic_papers.year', '>=', $request->start_year);
        }
        if ($request->filled('end_year')) {
            $query->where('academic_papers.year', '<=', $request->end_year);
        }

        // Researcher name search (NEW)
        if ($request->filled('researcher_name')) {
            $query->where('lecturers.lecturername', 'like', '%' . $request->researcher_name . '%');
        }

        // Other filters (arrays)
        if ($request->has('faculty')) {
            $query->whereIn('faculties.id', $request->input('faculty'));
        }
        if ($request->has('department')) {
            $query->whereIn('departments.id', $request->input('department'));
        }
        if ($request->has('grade')) {
            $query->whereIn('lecturers.grade', $request->input('grade'));
        }
        if ($request->has('qualification')) {
            $query->whereIn('lecturers.qualification', $request->input('qualification'));
        }
        if ($request->has('publication_type')) {
            $query->whereIn('academic_papers.publication', $request->input('publication_type'));
        }
        if ($request->has('index')) {
            $query->whereIn('academic_papers.indexed', $request->input('index'));
        }
        if ($request->has('funding')) {
            $query->whereIn('academic_papers.funding', $request->input('funding'));
        }
        if ($request->has('status')) {
            $query->whereIn('academic_papers.status', $request->input('status'));
        }
        if ($request->has('collaboration')) {
            $query->whereIn('academic_papers.collaboration', $request->input('collaboration'));
        }
        if ($request->has('author_position')) {
            $query->whereIn('academic_papers.author_position', $request->input('author_position'));
        }
        if ($request->has('research_area')) {
            $areas = $request->input('research_area');
            $query->where(function ($q) use ($areas) {
                foreach ($areas as $area) {
                    $q->orWhere('lecturers.specialized_area', 'like', "%$area%");
                }
            });
        }

        $results = $query->get();

        // Format research_area
        $results = $results->map(function ($row) {
            $row->research_area = $this->formatResearchArea($row->research_area);
            return $row;
        });

        return response()->json($results);
    }

    // Add PDF preview method
    public function previewPdf(Request $request)
    {
        // 1. Convert comma-separated multi-select values into arrays (unchanged)
        $convertedParams = [];
        if ($request->filled('start_year')) {
            $convertedParams['start_year'] = $request->start_year;
        }
        if ($request->filled('end_year')) {
            $convertedParams['end_year'] = $request->end_year;
        }
        if ($request->filled('researcher_name')) {
            $convertedParams['researcher_name'] = $request->researcher_name;
        }

        $multiSelectKeys = [
            'faculty', 'department', 'grade', 'qualification', 'publication_type',
            'index', 'funding', 'status', 'collaboration', 'author_position', 'research_area'
        ];
        foreach ($multiSelectKeys as $key) {
            if ($request->filled($key)) {
                $value = $request->input($key);
                $convertedParams[$key] = is_string($value) ? explode(',', $value) : $value;
            }
        }

        $internalRequest = new Request($convertedParams);
        $data = $this->index($internalRequest)->getData(true);

        if (empty($data)) {
            return response("No data matching the filters", 404);
        }

        // 2. Prepare columns and rows (unchanged)
        $selectedColumns = $request->input('selected_columns', []);
        if (is_string($selectedColumns)) {
            $selectedColumns = array_filter(explode(',', $selectedColumns));
        }
        $allColumns = [
            'title' => 'Title',
            'researcher_name' => 'Researcher Name',
            'faculty' => 'Faculty',
            'department' => 'Department',
            'grade' => 'Grade',
            'education' => 'Education',
            'year' => 'Year',
            'publication_type' => 'Publication Type',
            'index' => 'Index',
            'funding' => 'Funding',
            'status' => 'Status',
            'collaboration' => 'Collaboration',
            'author_position' => 'Author Position',
            'research_area' => 'Research Area',
        ];
        if (empty($selectedColumns)) {
            $selectedColumns = array_keys($allColumns);
        }
        $columns = [];
        foreach ($selectedColumns as $colKey) {
            if (isset($allColumns[$colKey])) {
                $columns[$colKey] = $allColumns[$colKey];
            }
        }

        $rows = [];
        foreach ($data as $item) {
            $row = [];
            foreach (array_keys($columns) as $key) {
                $value = $item[$key] ?? '—';
                if ($key === 'research_area' && is_array($value)) {
                    $value = implode(', ', $value);
                }
                $row[] = $value;
            }
            $rows[] = $row;
        }

        // 3. Date, filter summary, breakdowns (unchanged – keep all your existing breakdown calculations)
        date_default_timezone_set('Asia/Kabul');
        $verta = new \Hekmatinasser\Verta\Verta();
        $shamsi = $verta->format('Y-m-d');
        $pashtoDate = $this->toPashtoNumbers($shamsi);

        $filtersSummary = [];
        if ($request->filled('start_year') || $request->filled('end_year')) {
            $start = $request->start_year ?? 'any';
            $end = $request->end_year ?? 'any';
            $filtersSummary[] = "Years: {$start} – {$end}";
        }
        if ($request->filled('researcher_name')) {
            $filtersSummary[] = "Researcher: " . $request->researcher_name;
        }

        // Column summaries
        $columnSummaries = [];
        foreach (array_keys($columns) as $colKey) {
            $values = array_column($data, $colKey);
            $filtered = array_filter($values, fn($v) => $v !== null && $v !== '' && $v !== '—');
            $unique = count(array_unique($filtered));
            $columnSummaries[] = [
                'label' => $allColumns[$colKey],
                'count' => $unique,
                'total' => count($data),
            ];
        }

        // Grade breakdown
        $gradeCounts = [];
        foreach ($data as $row) {
            $grade = $row['grade'] ?? null;
            if ($grade && $grade !== '' && $grade !== '—') {
                $gradeCounts[$grade] = ($gradeCounts[$grade] ?? 0) + 1;
            }
        }
        $gradeBreakdown = [];
        foreach ($gradeCounts as $grade => $count) {
            $gradeBreakdown[] = ['grade' => $grade, 'count' => $count, 'total' => count($data)];
        }

        // Education breakdown
        $eduCounts = [];
        foreach ($data as $row) {
            $edu = $row['education'] ?? null;
            if ($edu && $edu !== '' && $edu !== '—') {
                $eduCounts[$edu] = ($eduCounts[$edu] ?? 0) + 1;
            }
        }
        $educationBreakdown = [];
        foreach ($eduCounts as $edu => $count) {
            $educationBreakdown[] = ['education' => $edu, 'count' => $count, 'total' => count($data)];
        }

        // Publication Type breakdown
        $pubTypeCounts = [];
        foreach ($data as $row) {
            $pub = $row['publication_type'] ?? null;
            if ($pub && $pub !== '' && $pub !== '—') {
                $pubTypeCounts[$pub] = ($pubTypeCounts[$pub] ?? 0) + 1;
            }
        }
        $publicationTypeBreakdown = [];
        foreach ($pubTypeCounts as $type => $count) {
            $publicationTypeBreakdown[] = ['type' => $type, 'count' => $count, 'total' => count($data)];
        }

        // Index breakdown
        $indexCounts = [];
        foreach ($data as $row) {
            $idx = $row['index'] ?? null;
            if ($idx && $idx !== '' && $idx !== '—') {
                $indexCounts[$idx] = ($indexCounts[$idx] ?? 0) + 1;
            }
        }
        $indexBreakdown = [];
        foreach ($indexCounts as $index => $count) {
            $indexBreakdown[] = ['index' => $index, 'count' => $count, 'total' => count($data)];
        }

        // Language breakdown
        $langCounts = [];
        foreach ($data as $row) {
            $lang = $row['language'] ?? null;
            if ($lang && $lang !== '' && $lang !== '—') {
                $langCounts[$lang] = ($langCounts[$lang] ?? 0) + 1;
            }
        }
        $languageBreakdown = [];
        foreach ($langCounts as $lang => $count) {
            $languageBreakdown[] = ['language' => $lang, 'count' => $count, 'total' => count($data)];
        }

        // Collaboration breakdown
        $collabCounts = [];
        foreach ($data as $row) {
            $collab = $row['collaboration'] ?? null;
            if ($collab && $collab !== '' && $collab !== '—') {
                $collabCounts[$collab] = ($collabCounts[$collab] ?? 0) + 1;
            }
        }
        $collaborationBreakdown = [];
        foreach ($collabCounts as $collab => $count) {
            $collaborationBreakdown[] = ['collaboration' => $collab, 'count' => $count, 'total' => count($data)];
        }

        // Common data for views (unchanged)
        $commonViewData = [
            'columns'            => array_values($columns),
            'date'               => $pashtoDate,
            'filterSummary'      => implode(' | ', $filtersSummary),
            'totalRecords'       => count($data),
            'columnSummaries'    => $columnSummaries,
            'gradeBreakdown'     => $gradeBreakdown,
            'educationBreakdown' => $educationBreakdown,
            'publicationTypeBreakdown' => $publicationTypeBreakdown,
            'indexBreakdown'           => $indexBreakdown,
            'languageBreakdown'        => $languageBreakdown,
            'collaborationBreakdown'   => $collaborationBreakdown,
        ];

        // 4. Setup mPDF (unchanged)
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
            'directionality' => 'rtl',
        ]);

        // ===== 5. RENDER IN CORRECT ORDER =====

        $headerViewData = array_merge($commonViewData, [
            'skipHeader'      => false,
            'skipTable'       => true,
            'skipBreakdowns'  => true,
            'skipFooter'      => true,
        ]);
        $htmlHeader = view('pdf.key-findings', $headerViewData)->render();
        $mpdf->WriteHTML($htmlHeader);

        // 5b. Table opening + thead (once)
        $theadHtml = view('pdf.key-findings-table-head', ['columns' => array_values($columns)])->render();
        $mpdf->WriteHTML($theadHtml);

        // 5c. Open tbody (once)
        $mpdf->WriteHTML('<tbody>');

        // 5d. Table rows – process in chunks (only <tr> rows)
        $chunkSize = 500;
        $rowChunks = array_chunk($rows, $chunkSize);
        foreach ($rowChunks as $chunk) {
            $rowsHtml = view('pdf.key-findings-rows', [
                'rows' => $chunk,
            ])->render();
            $mpdf->WriteHTML($rowsHtml);
        }

        // 5e. Close tbody and table (once)
        $mpdf->WriteHTML('</tbody></table>');

        // 5f. Breakdowns (once, skip header)
        $breakdownViewData = array_merge($commonViewData, [
            'skipHeader'      => true,
            'skipTable'       => true,
            'skipBreakdowns'  => false,
            'skipFooter'      => true,
        ]);
        $htmlBreakdowns = view('pdf.key-findings', $breakdownViewData)->render();
        $mpdf->WriteHTML($htmlBreakdowns);

        // 5g. Footer (once, skip everything except footer)
        $footerViewData = array_merge($commonViewData, [
            'skipHeader'      => true,
            'skipTable'       => true,
            'skipBreakdowns'  => true,
            'skipFooter'      => false,
        ]);
        $htmlFooter = view('pdf.key-findings', $footerViewData)->render();
        $mpdf->WriteHTML($htmlFooter);

        // 6. Output PDF (unchanged)
        return response($mpdf->Output('key-findings.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    // Helper to convert numbers to Pashto digits (add if missing)
    private function toPashtoNumbers($string)
    {
        $western = ['0','1','2','3','4','5','6','7','8','9'];
        $pashto  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return str_replace($western, $pashto, $string);
    }

    private function getDistinctResearchAreas()
    {
        $areas = DB::table('lecturers')
            ->whereNotNull('specialized_area')
            ->pluck('specialized_area');

        $unique = [];
        foreach ($areas as $areaJson) {
            $arr = json_decode($areaJson, true) ?: [];
            foreach ($arr as $item) {
                $unique[trim($item)] = true;
            }
        }
        ksort($unique);
        return array_keys($unique);
    }

    private function formatResearchArea($json)
    {
        if (!$json) return '';
        $arr = json_decode($json, true);
        return is_array($arr) ? implode(', ', $arr) : '';
    }
}