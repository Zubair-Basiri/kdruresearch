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
        return response()->json([
            'faculties'       => DB::table('faculties')->orderBy('facultyname')->pluck('facultyname', 'id'),
            'departments'     => DB::table('departments')->orderBy('deptname')->pluck('deptname', 'id'),
            'grades'          => DB::table('lecturers')->distinct()->orderBy('grade')->pluck('grade'),
            'qualifications'  => DB::table('lecturers')->distinct()->orderBy('qualification')->pluck('qualification'),
            'years'           => DB::table('academic_papers')->distinct()->orderBy('year', 'desc')->pluck('year'),
            'publication_types' => DB::table('academic_papers')->distinct()->orderBy('publication')->pluck('publication'),
            'indexes'         => DB::table('academic_papers')->distinct()->orderBy('indexed')->pluck('indexed'),
            'funding_sources' => DB::table('academic_papers')->distinct()->orderBy('funding')->pluck('funding'),
            'statuses'        => DB::table('academic_papers')->distinct()->orderBy('status')->pluck('status'),
            'collaborations'  => DB::table('academic_papers')->distinct()->orderBy('collaboration')->pluck('collaboration'),
            'author_positions'=> DB::table('academic_papers')->distinct()->orderBy('author_position')->pluck('author_position'),
            'research_areas'  => $this->getDistinctResearchAreas(),
            'researcher_names' => DB::table('lecturers')->orderBy('lecturername')->pluck('lecturername'), // NEW
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
                'lecturers.specialized_area as research_area'
            );

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
        // Create a new request with the same parameters, converting comma‑separated
        // multi‑select values into arrays.
        $convertedParams = [];

        // Copy simple parameters
        if ($request->filled('start_year')) {
            $convertedParams['start_year'] = $request->start_year;
        }
        if ($request->filled('end_year')) {
            $convertedParams['end_year'] = $request->end_year;
        }
        if ($request->filled('researcher_name')) {
            $convertedParams['researcher_name'] = $request->researcher_name;
        }

        // List of filter keys that accept multiple values
        $multiSelectKeys = [
            'faculty', 'department', 'grade', 'qualification', 'publication_type',
            'index', 'funding', 'status', 'collaboration', 'author_position', 'research_area'
        ];

        foreach ($multiSelectKeys as $key) {
            if ($request->filled($key)) {
                $value = $request->input($key);
                // Convert comma‑separated string to array
                if (is_string($value)) {
                    $convertedParams[$key] = explode(',', $value);
                } else {
                    $convertedParams[$key] = $value;
                }
            }
        }

        // Build the new request
        $internalRequest = new Request($convertedParams);

        // Get the data using the index method (which now receives proper arrays)
        $data = $this->index($internalRequest)->getData(true);

        if (empty($data)) {
            return response("No data matching the filters", 404);
        }

        // Selected columns (may be a comma‑separated string)
        $selectedColumns = $request->input('selected_columns', []);
        if (is_string($selectedColumns)) {
            $selectedColumns = array_filter(explode(',', $selectedColumns));
        }

        // All possible column keys and their labels
        $allColumns = [
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

        // If no columns selected, use all
        if (empty($selectedColumns)) {
            $selectedColumns = array_keys($allColumns);
        }

        // Build columns array
        $columns = [];
        foreach ($selectedColumns as $colKey) {
            if (isset($allColumns[$colKey])) {
                $columns[$colKey] = $allColumns[$colKey];
            }
        }

        // Prepare table rows
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

        // Hijri Shamsi date with Pashto numerals
        date_default_timezone_set('Asia/Kabul');
        $verta = new \Hekmatinasser\Verta\Verta();
        $shamsi = $verta->format('Y-m-d');
        $pashtoDate = $this->toPashtoNumbers($shamsi);

        // Filter summary
        $filtersSummary = [];
        if ($request->filled('start_year') || $request->filled('end_year')) {
            $start = $request->start_year ?? 'any';
            $end = $request->end_year ?? 'any';
            $filtersSummary[] = "Years: {$start} – {$end}";
        }
        if ($request->filled('researcher_name')) {
            $filtersSummary[] = "Researcher: " . $request->researcher_name;
        }

        // Render view
        $html = view('pdf.key-findings', [
            'columns'       => array_values($columns),
            'rows'          => $rows,
            'date'          => $pashtoDate,
            'filterSummary' => implode(' | ', $filtersSummary),
            'totalRecords'  => count($data),
        ])->render();

        // mPDF configuration
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

        $mpdf->WriteHTML($html);
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