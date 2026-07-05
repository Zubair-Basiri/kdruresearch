<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicPaper;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Lecturer;
use Hekmatinasser\Verta\Verta;
use Mpdf\Mpdf;

class YearSummaryController extends Controller
{
    protected $lastTenYears;
    protected $lastFiveYears;

    public function __construct()
    {
        $currentYear = (int) date('Y');
        $this->lastTenYears = range($currentYear - 9, $currentYear);
        $this->lastFiveYears = range($currentYear - 4, $currentYear);
    }

    private function getYearRange(Request $request)
    {
        $start = $request->input('start_year');
        $end   = $request->input('end_year');

        if ($start && $end) {
            return range($start, $end);
        }

        // fallback to hard‑coded ranges
        return $this->lastTenYears;
    }

    /*
    |--------------------------------------------------------------------------
    | YEAR SUMMARY
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $metric = $request->metric;
        $years = $this->getYearRange($request);

        switch ($metric) {
            case "Faculty / Year":
                return $this->groupByFaculty($years);
            case "Department / Year":
                return $this->groupByDepartment($years);
            case "Academic Grade / Year":
                return $this->groupByLecturerField('grade', $years);
            case "Researcher / Year":
                return $this->groupByResearcher($years);
            case "publication Type / Year":
                return $this->groupByPaperField('publication', $years);
            case "Funding Source / Year":
                return $this->groupByPaperField('funding', $years);
            case "Collaboration / Year":
                return $this->groupByPaperField('collaboration', $years);
            case "Publication Language / Year":
                return $this->groupByPaperField('language', $years);
            case "Index / Year":
                return $this->groupByPaperField('indexed', $years);
            case "Qualification / Year":
                return $this->groupByLecturerField('qualification', $years);
            case "Status / Year":
                return $this->groupByPaperField('status', $years);
            default:
                return response()->json([]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY GROWTH
    |--------------------------------------------------------------------------
    */

    public function category(Request $request)
    {
        $type = $request->category;
        $years = $this->lastFiveYears;

        switch ($type) {
            case "Faculties":
                return $this->categoryByFaculty($years);
            case "Departments":
                return $this->categoryByDepartment($years);
            case "Academic Grades":
                return $this->categoryByLecturerField('grade', $years);
            case "Academic Qualification":
                return $this->categoryByLecturerField('qualification', $years);
            case "Publication Types":
                return $this->categoryByPaperField('publication', $years);
            case "Funding Sources":
                return $this->categoryByPaperField('funding', $years);
            case "Indexed":
                return $this->categoryByIndexType($years);
            case "Collaboration":
                return $this->categoryByPaperField('collaboration', $years);
            case "Status":
                return $this->categoryByPaperField('status', $years);
            case "Publication Language":
                return $this->categoryByPaperField('language', $years);
            default:
                return response()->json([]);
        }
    }

    // ------------------------------------------------------------------------
    // CATEGORY METHODS (now accept $years)
    // ------------------------------------------------------------------------

    private function categoryByIndexType($years)
    {
        $values = AcademicPaper::whereNotNull('indexed')
            ->where('indexed', '!=', '')
            ->distinct()
            ->pluck('indexed');

        $result = [];
        foreach ($values as $value) {
            $yearData = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->where('indexed', $value)
                    ->count();
                $yearData[$year] = $count;
            }
            $average = array_sum($yearData) / count($years);
            $result[] = [
                'label'   => $value,
                'values'  => $yearData,
                'average' => $average
            ];
        }
        return response()->json($result);
    }

    private function categoryByDepartment($years)
    {
        $departments = Department::all();
        $result = [];
        foreach ($departments as $dept) {
            $values = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->whereHas('lecturer', fn($q) => $q->where('department_id', $dept->id))
                    ->count();
                $values[$year] = $count;
            }
            $average = array_sum($values) / count($years);
            $result[] = [
                'label'   => $dept->deptname,
                'values'  => $values,
                'average' => $average
            ];
        }
        return response()->json($result);
    }

    private function categoryByFaculty($years)
    {
        $faculties = Faculty::all();
        $result = [];
        foreach ($faculties as $faculty) {
            $values = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->whereHas('lecturer', fn($q) => $q->where('faculty_id', $faculty->id))
                    ->count();
                $values[$year] = $count;
            }
            $average = array_sum($values) / count($years);
            $result[] = [
                'label'   => $faculty->facultyname,
                'values'  => $values,
                'average' => $average
            ];
        }
        return response()->json($result);
    }

    private function categoryByLecturerField($field, $years)
    {
        $values = Lecturer::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->pluck($field);

        $result = [];
        foreach ($values as $value) {
            $yearData = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->whereHas('lecturer', fn($q) => $q->where($field, $value))
                    ->count();
                $yearData[$year] = $count;
            }
            $average = array_sum($yearData) / count($years);
            $result[] = [
                'label'   => $value,
                'values'  => $yearData,
                'average' => $average
            ];
        }
        return response()->json($result);
    }

    private function categoryByPaperField($field, $years)
    {
        $values = AcademicPaper::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->pluck($field);

        $result = [];
        foreach ($values as $value) {
            $yearData = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->where($field, $value)
                    ->count();
                $yearData[$year] = $count;
            }
            $average = array_sum($yearData) / count($years);
            $result[] = [
                'label'   => $value,
                'values'  => $yearData,
                'average' => $average
            ];
        }
        return response()->json($result);
    }

    // ------------------------------------------------------------------------
    // GROUPING METHODS (now accept $years)
    // ------------------------------------------------------------------------

    private function groupByFaculty($years)
    {
        $faculties = Faculty::all();
        return $this->buildYearResponse($faculties, 'faculty_id', 'facultyname', $years);
    }

    private function groupByDepartment($years)
    {
        $departments = Department::all();
        return $this->buildYearResponse($departments, 'department_id', 'deptname', $years);
    }

    private function groupByResearcher($years)
    {
        $lecturers = Lecturer::all();
        return $this->buildYearResponse($lecturers, 'lecturer_id', 'lecturername', $years);
    }

    private function groupByLecturerField($field, $years)
    {
        $values = Lecturer::select($field)->distinct()->pluck($field);
        return $this->buildCustomFieldResponse($values, $field, true, $years);
    }

    private function groupByPaperField($field, $years)
    {
        $values = AcademicPaper::select($field)->distinct()->pluck($field);
        return $this->buildCustomFieldResponse($values, $field, false, $years);
    }

    // ------------------------------------------------------------------------
    // CORE BUILDERS (now accept $years)
    // ------------------------------------------------------------------------

    private function buildYearResponse($collection, $foreignKey, $labelField, $years)
    {
        $result = [];
        foreach ($collection as $item) {
            $row = [
                'label'         => $item->$labelField,
                'values'        => [],
                'total'         => 0,
                'totalCitation' => 0
            ];
            foreach ($years as $year) {
                $query = AcademicPaper::where('year', $year);
                if ($foreignKey === 'faculty_id' || $foreignKey === 'department_id') {
                    $query->whereHas('lecturer', fn($q) => $q->where($foreignKey, $item->id));
                } else {
                    $query->where($foreignKey, $item->id);
                }
                $count = $query->count();
                $citation = $query->sum('citation');

                $row['values'][$year] = $count;
                $row['total'] += $count;
                $row['totalCitation'] += $citation;
            }
            $result[] = $row;
        }
        return response()->json($result);
    }

    private function buildCustomFieldResponse($values, $field, $fromLecturer, $years)
    {
        $result = [];
        foreach ($values as $value) {
            $row = [
                'label'         => $value ?? 'Unknown',
                'values'        => [],
                'total'         => 0,
                'totalCitation' => 0
            ];
            foreach ($years as $year) {
                $query = AcademicPaper::where('year', $year);
                if ($fromLecturer) {
                    $query->whereHas('lecturer', fn($q) => $q->where($field, $value));
                } else {
                    $query->where($field, $value);
                }
                $count = $query->count();
                $citation = $query->sum('citation');

                $row['values'][$year] = $count;
                $row['total'] += $count;
                $row['totalCitation'] += $citation;
            }
            $result[] = $row;
        }
        return response()->json($result);
    }

    public function previewPdf(Request $request)
    {
        $metric = $request->query('metric');
        $category = $request->query('category');
        $isCategory = !empty($category);
        $title = $isCategory ? $category : $metric;

        if ((!$isCategory && !$metric) || ($isCategory && !$category)) {
            return response('Missing metric or category parameter', 400);
        }

        // Get the data (reuse existing methods)
        if ($isCategory) {
            $internalRequest = new Request(['category' => $category]);
            if ($request->has('start_year')) $internalRequest->merge(['start_year' => $request->start_year]);
            if ($request->has('end_year')) $internalRequest->merge(['end_year' => $request->end_year]);
            $jsonResponse = $this->category($internalRequest);
        } else {
            $internalRequest = new Request(['metric' => $metric]);
            if ($request->has('start_year')) $internalRequest->merge(['start_year' => $request->start_year]);
            if ($request->has('end_year')) $internalRequest->merge(['end_year' => $request->end_year]);
            $jsonResponse = $this->index($internalRequest);
        }

        $rows = $jsonResponse->getData(true);
        if (empty($rows)) {
            return response("No data for " . ($isCategory ? "category" : "metric") . ": " . ($isCategory ? $category : $metric), 404);
        }

        // Extract years from the first row's values
        $years = array_keys($rows[0]['values'] ?? []);
        sort($years);

        // Prepare rows for the PDF
        $pdfRows = [];
        foreach ($rows as $row) {
            $pdfRow = ['label' => $row['label'], 'values' => []];
            foreach ($years as $year) {
                $pdfRow['values'][$year] = $row['values'][$year] ?? 0;
            }
            if (isset($row['total'])) $pdfRow['total'] = $row['total'];
            if (isset($row['totalCitation'])) $pdfRow['totalCitation'] = $row['totalCitation'];
            if (isset($row['average'])) $pdfRow['average'] = $row['average'];
            $pdfRows[] = $pdfRow;
        }

        // Determine if it's a category report (has 'average')
        $hasAverage = isset($rows[0]['average']);

        // Hijri Shamsi date with Pashto numerals
        date_default_timezone_set('Asia/Kabul');
        $verta = new Verta();
        $shamsi = $verta->format('Y-m-d');
        $pashtoDate = $this->toPashtoNumbers($shamsi);

        // Render the PDF view
        $html = view('pdf.year-summary-dynamic', [
            'metricTitle' => $title,
            'years'       => $years,
            'rows'        => $pdfRows,
            'isCategory'  => $hasAverage,
            'date'        => $pashtoDate,
        ])->render();

        // mPDF configuration
        $mpdf = new Mpdf([
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
        return response($mpdf->Output('year-summary.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    private function toPashtoNumbers($string)
    {
        $western = ['0','1','2','3','4','5','6','7','8','9'];
        $pashto  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return str_replace($western, $pashto, $string);
    }
}