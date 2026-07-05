<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\AcademicPaper;
use Mpdf\Mpdf;
use Hekmatinasser\Verta\Verta;

class FacultySummaryController extends Controller
{
    public function index(Request $request)
    {
        $metric = $request->metric;  // e.g., "Publication Type / Faculty"

        switch ($metric) {

            case "Publication Type / Faculty":
                return $this->byPaperField('publication');

            case "Academic Grade / Faculty":
                return $this->byLecturerGrade();

            case "Education / Faculty":
                return $this->byLecturerField('qualification');

            case "Department / Faculty":
                return $this->byDepartment();

            case "Indexed / Faculty":
                return $this->byPaperField('indexed');

            case "Research Area / Faculty":
                return $this->byLecturerField('specialized_area');

            case "Publication Language / Faculty":
                return $this->byPaperField('language');

            case "Year / Faculty":
                return $this->byPaperField('year');

            case "Researchers / Faculty":
                return $this->byLecturerList();

            default:
                return response()->json([]);
        }
    }

    public function previewPdf(Request $request)
    {
        $metric = $request->query('metric');
        if (!$metric) {
            return response("Missing metric parameter", 400);
        }

        // Get the same data as your API endpoint
        $internalRequest = new Request(['metric' => $metric]);
        $jsonResponse = $this->index($internalRequest);
        $rows = $jsonResponse->getData(true); // array of rows

        if (empty($rows)) {
            return response("No data for metric: $metric", 404);
        }

        // Extract faculty names (columns) from the first row's values
        $faculties = array_keys($rows[0]['values'] ?? []);
        sort($faculties); // optional: alphabetical order

        // Reorder each row's values to match the sorted faculty list
        $orderedRows = [];
        foreach ($rows as $row) {
            $orderedValues = [];
            foreach ($faculties as $fac) {
                $orderedValues[] = $row['values'][$fac] ?? 0;
            }
            $orderedRows[] = [
                'label' => $row['label'],
                'values' => $orderedValues,
            ];
        }

        // Pashto Hijri Shamsi date
        date_default_timezone_set('Asia/Kabul');
        $verta = new Verta();
        $shamsi = $verta->format('Y-m-d');
        $pashtoDate = $this->toPashtoNumbers($shamsi);

        // Render the dynamic view
        $html = view('pdf.faculty-summary', [
            'metricTitle' => $metric,
            'faculties'   => $faculties,
            'rows'        => $orderedRows,
            'date'        => $pashtoDate,
        ])->render();

        // mPDF configuration with Bahij Nazanin
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
        return response($mpdf->Output('faculty-summary.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    private function toPashtoNumbers($string)
    {
        $western = ['0','1','2','3','4','5','6','7','8','9'];
        $pashto  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return str_replace($western, $pashto, $string);
    }

    /* ---------------------------
       Generic: Count Papers by Paper Field
    ---------------------------- */
    private function byPaperField($field)
    {
        $faculties = Faculty::pluck('facultyname', 'id');
        $distinctValues = AcademicPaper::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->pluck($field)
            ->toArray();

        if ($field === 'year') {
        rsort($distinctValues); // descending numeric sort
        }

        $result = [];

        foreach ($distinctValues as $value) {
            $row = ['label' => $value, 'values' => []];

            foreach ($faculties as $id => $name) {
                $count = AcademicPaper::where($field, $value)
                    ->whereHas('lecturer', function ($q) use ($id) {
                        $q->where('faculty_id', $id);
                    })
                    ->count();

                $row['values'][$name] = $count;
            }

            $result[] = $row;
        }

        return response()->json($result);
    }

    /* ---------------------------
       Lecturer Field (qualification / grade / specialized_area)
    ---------------------------- */
    private function byLecturerField($field, $raw = false)
{
    $faculties = Faculty::pluck('facultyname', 'id');

    if ($field === 'specialized_area') {
        // Get all unique research areas from the JSON array in specialized_area
        $allAreas = collect();
        $lecturers = Lecturer::whereNotNull($field)
            ->where($field, '!=', '')
            ->get();

        foreach ($lecturers as $lecturer) {
            $areas = $lecturer->$field;
            if (is_string($areas)) {
                $decoded = json_decode($areas, true);
                if (is_array($decoded)) {
                    $allAreas = $allAreas->merge($decoded);
                } elseif (!empty($areas)) {
                    $allAreas->push($areas);
                }
            } elseif (is_array($areas)) {
                $allAreas = $allAreas->merge($areas);
            }
        }
        $distinctValues = $allAreas->filter()->unique()->sort()->values();
    } else {
        $distinctValues = Lecturer::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->pluck($field);
    }

    $result = [];

    foreach ($distinctValues as $value) {
        $row = ['label' => $value, 'values' => []];

        foreach ($faculties as $id => $name) {
            if ($field === 'specialized_area') {
                // Count lecturers in this faculty that have this area anywhere in their JSON
                $count = Lecturer::where('faculty_id', $id)
                    ->where(function ($query) use ($field, $value) {
                        // Match the area as JSON string value (supports both MySQL and PostgreSQL)
                        $query->where($field, 'LIKE', '%"' . addslashes($value) . '"%')
                              ->orWhere($field, 'LIKE', '%' . addslashes($value) . '%');
                    })
                    ->count();
            } else {
                $count = Lecturer::where('faculty_id', $id)
                    ->where($field, $value)
                    ->count();
            }
            $row['values'][$name] = $count;
        }

        $result[] = $row;
    }

    return $raw ? $result : response()->json($result);
}

    /* ---------------------------
       Lecturer Academic Grades
    ---------------------------- */
    private function byLecturerGrade()
    {
        $faculties = Faculty::pluck('facultyname', 'id');

        $grades = [
            "Jr. Teaching Assist.",
            "Teaching Assistant",
            "Sr. Teaching Assistant",
            "Assist. Prof.",
            "Assoc. Prof.",
            "Professor",
        ];

        $result = [];

        foreach ($grades as $grade) {
            $row = ['label' => $grade, 'values' => []];

            foreach ($faculties as $id => $name) {
                $count = Lecturer::where('faculty_id', $id)
                    ->where('grade', $grade)
                    ->count();

                $row['values'][$name] = $count;
            }

            $result[] = $row;
        }

        return response()->json($result);
    }

    /* ---------------------------
       Departments
    ---------------------------- */
    private function byDepartment()
    {
        $faculties = Faculty::pluck('facultyname', 'id');

        $departments = Department::pluck('deptname', 'id');

        $result = [];

        foreach ($departments as $deptName) {
            $row = ['label' => $deptName, 'values' => []];

            foreach ($faculties as $id => $name) {
                $count = Lecturer::where('faculty_id', $id)
                    ->whereHas('department', function ($q) use ($deptName) {
                        $q->where('deptname', $deptName);
                    })
                    ->count();

                $row['values'][$name] = $count;
            }

            $result[] = $row;
        }

        return response()->json($result);
    }

    /* ---------------------------
       Researchers / Faculty
    ---------------------------- */
    private function byLecturerList()
    {
        $faculties = Faculty::pluck('facultyname', 'id');

        $lecturers = Lecturer::pluck('lecturername');

        $result = [];

        foreach ($lecturers as $lecturer) {
            $row = ['label' => $lecturer, 'values' => []];

            foreach ($faculties as $id => $name) {
                $count = Lecturer::where('faculty_id', $id)
                    ->where('lecturername', $lecturer)
                    ->count();

                $row['values'][$name] = $count;
            }

            $result[] = $row;
        }

        return response()->json($result);
    }
}

