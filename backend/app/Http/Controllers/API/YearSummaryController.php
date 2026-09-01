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
        $universityId = currentUniversityId();

        switch ($metric) {
            case "Faculty / Year":
                return $this->groupByFaculty($years, $universityId);
            case "Department / Year":
                return $this->groupByDepartment($years, $universityId);
            case "Academic Grade / Year":
                return $this->groupByLecturerField('grade', $years, $universityId);
            case "Researcher / Year":
                return $this->groupByResearcher($years, $universityId);
            case "publication Type / Year":
                return $this->groupByPaperField('publication', $years, $universityId);
            case "Funding Source / Year":
                return $this->groupByPaperField('funding', $years, $universityId);
            case "Collaboration / Year":
                return $this->groupByPaperField('collaboration', $years, $universityId);
            case "Publication Language / Year":
                return $this->groupByPaperField('language', $years, $universityId);
            case "Index / Year":
                return $this->groupByPaperField('indexed', $years, $universityId);
            case "Qualification / Year":
                return $this->groupByLecturerField('qualification', $years, $universityId);
            case "Status / Year":
                return $this->groupByPaperField('status', $years, $universityId);
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
        $universityId = currentUniversityId();

        switch ($type) {
            case "Faculties":
                return $this->categoryByFaculty($years, $universityId);
            case "Departments":
                return $this->categoryByDepartment($years, $universityId);
            case "Academic Grades":
                return $this->categoryByLecturerField('grade', $years, $universityId);
            case "Academic Qualification":
                return $this->categoryByLecturerField('qualification', $years, $universityId);
            case "Publication Types":
                return $this->categoryByPaperField('publication', $years, $universityId);
            case "Funding Sources":
                return $this->categoryByPaperField('funding', $years, $universityId);
            case "Indexed":
                return $this->categoryByIndexType($years, $universityId);
            case "Collaboration":
                return $this->categoryByPaperField('collaboration', $years, $universityId);
            case "Status":
                return $this->categoryByPaperField('status', $years, $universityId);
            case "Publication Language":
                return $this->categoryByPaperField('language', $years, $universityId);
            default:
                return response()->json([]);
        }
    }

    // ------------------------------------------------------------------------
    // CATEGORY METHODS (now accept $years)
    // ------------------------------------------------------------------------

    private function categoryByIndexType($years, $universityId)
    {
        $values = AcademicPaper::whereNotNull('indexed')
            ->where('indexed', '!=', '')
            ->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })
            ->distinct()
            ->pluck('indexed');

        $result = [];
        foreach ($values as $value) {
            $yearData = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->where('indexed', $value)
                    ->when($universityId, function ($q) use ($universityId) {
                        return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                            $sq->where('university_id', $universityId);
                        });
                    })
                    ->count();
                $yearData[$year] = $count;
            }
            $average = array_sum($yearData) / count($years);
            $result[] = ['label' => $value, 'values' => $yearData, 'average' => $average];
        }
        return response()->json($result);
    }

    private function categoryByDepartment($years, $universityId)
    {
        $departments = Department::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();

        $result = [];
        foreach ($departments as $dept) {
            $values = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->whereHas('lecturer', fn($q) => $q->where('department_id', $dept->id))
                    ->when($universityId, function ($q) use ($universityId) {
                        return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                            $sq->where('university_id', $universityId);
                        });
                    })
                    ->count();
                $values[$year] = $count;
            }
            $average = array_sum($values) / count($years);
            $result[] = ['label' => $dept->deptname, 'values' => $values, 'average' => $average];
        }
        return response()->json($result);
    }

    private function categoryByFaculty($years, $universityId)
    {
        $faculties = Faculty::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();

        $result = [];
        foreach ($faculties as $faculty) {
            $values = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->whereHas('lecturer', fn($q) => $q->where('faculty_id', $faculty->id))
                    ->when($universityId, function ($q) use ($universityId) {
                        return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                            $sq->where('university_id', $universityId);
                        });
                    })
                    ->count();
                $values[$year] = $count;
            }
            $average = array_sum($values) / count($years);
            $result[] = ['label' => $faculty->facultyname, 'values' => $values, 'average' => $average];
        }
        return response()->json($result);
    }

    private function categoryByLecturerField($field, $years, $universityId)
    {
        $values = Lecturer::whereNotNull($field)
            ->where($field, '!=', '')
            ->when($universityId, function ($q) use ($universityId) {
                return $q->where('university_id', $universityId);
            })
            ->distinct()
            ->pluck($field);

        $result = [];
        foreach ($values as $value) {
            $yearData = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->whereHas('lecturer', fn($q) => $q->where($field, $value))
                    ->when($universityId, function ($q) use ($universityId) {
                        return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                            $sq->where('university_id', $universityId);
                        });
                    })
                    ->count();
                $yearData[$year] = $count;
            }
            $average = array_sum($yearData) / count($years);
            $result[] = ['label' => $value, 'values' => $yearData, 'average' => $average];
        }
        return response()->json($result);
    }

    private function categoryByPaperField($field, $years, $universityId)
    {
        $values = AcademicPaper::whereNotNull($field)
            ->where($field, '!=', '')
            ->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })
            ->distinct()
            ->pluck($field);

        $result = [];
        foreach ($values as $value) {
            $yearData = [];
            foreach ($years as $year) {
                $count = AcademicPaper::where('year', $year)
                    ->where($field, $value)
                    ->when($universityId, function ($q) use ($universityId) {
                        return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                            $sq->where('university_id', $universityId);
                        });
                    })
                    ->count();
                $yearData[$year] = $count;
            }
            $average = array_sum($yearData) / count($years);
            $result[] = ['label' => $value, 'values' => $yearData, 'average' => $average];
        }
        return response()->json($result);
    }

    // ------------------------------------------------------------------------
    // GROUPING METHODS (now accept $years)
    // ------------------------------------------------------------------------

    private function groupByFaculty($years, $universityId)
    {
        $faculties = Faculty::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();
        return $this->buildYearResponse($faculties, 'faculty_id', 'facultyname', $years, $universityId);
    }

    private function groupByDepartment($years, $universityId)
    {
        $departments = Department::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();
        return $this->buildYearResponse($departments, 'department_id', 'deptname', $years, $universityId);
    }

    private function groupByResearcher($years, $universityId)
    {
        $lecturers = Lecturer::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();
        return $this->buildYearResponse($lecturers, 'lecturer_id', 'lecturername', $years, $universityId);
    }

    private function groupByLecturerField($field, $years, $universityId)
    {
        $values = Lecturer::select($field)
            ->when($universityId, function ($q) use ($universityId) {
                return $q->where('university_id', $universityId);
            })
            ->distinct()
            ->pluck($field);
        return $this->buildCustomFieldResponse($values, $field, true, $years, $universityId);
    }

    private function groupByPaperField($field, $years, $universityId)
    {
        $values = AcademicPaper::select($field)
            ->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })
            ->distinct()
            ->pluck($field);
        return $this->buildCustomFieldResponse($values, $field, false, $years, $universityId);
    }

    // ---- Core builders with university filter ----
    private function buildYearResponse($collection, $foreignKey, $labelField, $years, $universityId)
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
                if ($universityId) {
                    $query->whereHas('lecturer.faculty', function ($q) use ($universityId) {
                        $q->where('university_id', $universityId);
                    });
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

    private function buildCustomFieldResponse($values, $field, $fromLecturer, $years, $universityId)
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
                if ($universityId) {
                    $query->whereHas('lecturer.faculty', function ($q) use ($universityId) {
                        $q->where('university_id', $universityId);
                    });
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