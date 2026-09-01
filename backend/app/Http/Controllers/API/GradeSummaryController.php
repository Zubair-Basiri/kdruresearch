<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\AcademicPaper;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Lecturer;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Hekmatinasser\Verta\Verta;

class GradeSummaryController extends Controller
{
    public function index(Request $request)
    {
        $metric = $request->metric;
        $universityId = currentUniversityId();

        switch ($metric) {

            case "Faculty/Grade":
                return $this->byFaculty($universityId);

            case "Department/Grade":
                return $this->byDepartment($universityId);

            case "Researchers/Grade":
                return $this->byLecturer($universityId);

            case "Publication Type/Grade":
                return $this->byPaperField('publication', $universityId);

            case "Funding Source/Grade":
                return $this->byPaperField('funding', $universityId);

            case "Indexed/Grade":
                return $this->byPaperField('indexed', $universityId);

            case "Collaboration/Grade":
                return $this->byPaperField('collaboration', $universityId);

            case "Status/Grade":
                return $this->byPaperField('status', $universityId);

            case "Qualification/Grade":
                return $this->byLecturerField('qualification', $universityId);

            case "Language/Grade":
                return $this->byPaperField('language', $universityId);

            default:
                return response()->json([]);
        }
    }

    /* ---------------------------------
       FACULTY
    --------------------------------- */
    private function byFaculty($universityId)
    {
        $faculties = Faculty::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();
        $result = [];
        foreach ($faculties as $faculty) {
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($faculty) {
                $q->where('faculty_id', $faculty->id);
            })->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })->get();

            $result[] = $this->buildGradeRow($faculty->facultyname, $papers);
        }
        return response()->json($result);
    }

    /* ---------------------------------
       DEPARTMENT
    --------------------------------- */
    private function byDepartment($universityId)
    {
        $departments = Department::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();

        $result = [];
        foreach ($departments as $dept) {
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($dept) {
                $q->where('department_id', $dept->id);
            })->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })->get();

            $result[] = $this->buildGradeRow($dept->deptname, $papers);
        }
        return response()->json($result);
    }

    /* ---------------------------------
    RESEARCHERS / LECTURERS
    --------------------------------- */
    private function byLecturer($universityId)
    {
        $lecturers = Lecturer::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();

        $result = [];
        foreach ($lecturers as $lecturer) {
            $papers = AcademicPaper::where('lecturer_id', $lecturer->id)
                ->when($universityId, function ($q) use ($universityId) {
                    return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                        $sq->where('university_id', $universityId);
                    });
                })->get();

            $result[] = $this->buildGradeRow($lecturer->lecturername, $papers);
        }
        return response()->json($result);
    }

    /* ---------------------------------
       LECTURER FIELD (Qualification)
    --------------------------------- */
    private function byLecturerField($field, $universityId)
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
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($field, $value) {
                $q->where($field, $value);
            })->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })->get();

            $result[] = $this->buildGradeRow($value, $papers);
        }
        return response()->json($result);
    }

    /* ---------------------------------
       PAPER FIELD
    --------------------------------- */
    private function byPaperField($field, $universityId)
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
            $papers = AcademicPaper::where($field, $value)
                ->when($universityId, function ($q) use ($universityId) {
                    return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                        $sq->where('university_id', $universityId);
                    });
                })->get();

            $result[] = $this->buildGradeRow($value, $papers);
        }
        return response()->json($result);
    }

    /* ---- Core grade calculation ---- */
    private function buildGradeRow($label, $papers)
    {
        $grades = [
            "Jr. Teaching Assist." => 0,
            "Teaching Assistant" => 0,
            "Sr. Teaching Assistant" => 0,
            "Assist. Prof." => 0,
            "Assoc. Prof." => 0,
            "Professor" => 0,
        ];

        $totalCitation = 0;

        foreach ($papers as $paper) {
            $grade = optional($paper->lecturer)->grade;
            if (isset($grades[$grade])) {
                $grades[$grade]++;
            }
            $totalCitation += $paper->citation ?? 0;
        }

        return [
            'label' => $label,
            'values' => array_merge(
                ["Total papers" => $papers->count()],
                $grades,
                ["Total Citations" => $totalCitation]
            )
        ];
    }

    public function previewPdf(Request $request)
    {
        $metric = $request->query('metric');
        $data = $this->getReportData($metric);
        
        $html = view('pdf.grade-summary', $data)->render();
        
        // Set Kabul timezone for the date
        date_default_timezone_set('Asia/Kabul');
        $verta = new Verta();
        $shamsi = $verta->format('Y-m-d');
        $pashtoDate = $this->toPashtoNumbers($shamsi);
        
        // Override the date in $data
        $data['date'] = $pashtoDate;
        
        // Or simply pass it directly to view
        $html = view('pdf.grade-summary', array_merge($data, ['date' => $pashtoDate]))->render();
        
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // Add Bahij Nazanin font (regular)
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
        
        // Write the HTML
        $mpdf->WriteHTML($html);
        
        // Output inline
        return response($mpdf->Output('grade-summary.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    private function toPashtoNumbers($string)
    {
        $western = ['0','1','2','3','4','5','6','7','8','9'];
        $pashto  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return str_replace($western, $pashto, $string);
    }

    // /**
    //  * Force download PDF
    //  */
    // public function downloadPdf(Request $request)
    // {
    //     $metric = $request->query('metric');
    //     $data = $this->getReportData($metric);
        
    //     $html = view('pdf.grade-summary', $data)->render();
        
    //     $defaultConfig = (new ConfigVariables())->getDefaults();
    //     $fontDirs = $defaultConfig['fontDir'];

    //     $defaultFontConfig = (new FontVariables())->getDefaults();
    //     $fontData = $defaultFontConfig['fontdata'];

    //     $mpdf = new \Mpdf\Mpdf([
    //         'mode' => 'utf-8',
    //         'format' => 'A4-L',

    //         'fontDir' => array_merge($fontDirs, [
    //             storage_path('fonts'),
    //         ]),

    //         'fontdata' => $fontData + [
    //             'bahij' => [
    //                 'R' => 'Bahij_Titr-Bold.ttf',
    //             ]
    //         ],

    //         'default_font' => 'bahij',
    //         'directionality' => 'rtl',
    //     ]);
        
    //     $mpdf->WriteHTML($html);
        
    //     // D = force download
    //     return response($mpdf->Output('grade-summary.pdf', 'D'), 200)
    //         ->header('Content-Type', 'application/pdf');
    // }

    private function getReportData($metric)
    {
        $universityId = currentUniversityId();

        switch ($metric) {
            case "Faculty/Grade":
                $records = $this->getFacultyGradeData($universityId);
                break;
            case "Department/Grade":
                $records = $this->getDepartmentGradeData($universityId);
                break;
            case "Researchers/Grade":
                $records = $this->getResearchersGradeData($universityId);
                break;
            case "Publication Type/Grade":
                $records = $this->getPaperFieldGradeData('publication', $universityId);
                break;
            case "Funding Source/Grade":
                $records = $this->getPaperFieldGradeData('funding', $universityId);
                break;
            case "Indexed/Grade":
                $records = $this->getPaperFieldGradeData('indexed', $universityId);
                break;
            case "Collaboration/Grade":
                $records = $this->getPaperFieldGradeData('collaboration', $universityId);
                break;
            case "Status/Grade":
                $records = $this->getPaperFieldGradeData('status', $universityId);
                break;
            case "Qualification/Grade":
                $records = $this->getLecturerFieldGradeData('qualification', $universityId);
                break;
            case "Language/Grade":
                $records = $this->getPaperFieldGradeData('language', $universityId);
                break;
            default:
                $records = [];
        }

        date_default_timezone_set('Asia/Kabul');

        $rows = [];
        foreach ($records as $record) {
            $row = new \stdClass();
            $row->label = $record['label'];
            $row->values = [
                $record['total_papers'],
                $record['jr_ta'],
                $record['ta'],
                $record['senior_ta'],
                $record['asst_prof'],
                $record['assoc_prof'],
                $record['prof'],
                $record['total_citations'],
            ];
            $rows[] = $row;
        }

        return [
            'metric' => $metric,
            'rows'   => $rows,
            'date'   => now()->toDateString(),
        ];
    }

    /* ---- Data helpers with university filter ---- */
    private function getFacultyGradeData($universityId)
    {
        $faculties = Faculty::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();

        $result = [];
        foreach ($faculties as $faculty) {
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($faculty) {
                $q->where('faculty_id', $faculty->id);
            })->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })->get();

            $data = $this->buildGradeRow1($faculty->facultyname, $papers);
            $result[] = [
                'label' => $faculty->facultyname,
                'total_papers' => $data['total_papers'],
                'jr_ta' => $data['jr_ta'],
                'ta' => $data['ta'],
                'senior_ta' => $data['senior_ta'],
                'asst_prof' => $data['asst_prof'],
                'assoc_prof' => $data['assoc_prof'],
                'prof' => $data['prof'],
                'total_citations' => $data['total_citations'],
            ];
        }
        return $result;
    }

    private function getDepartmentGradeData($universityId)
    {
        $departments = Department::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();

        $result = [];
        foreach ($departments as $dept) {
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($dept) {
                $q->where('department_id', $dept->id);
            })->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })->get();

            $data = $this->buildGradeRow1($dept->deptname, $papers);
            $result[] = [
                'label' => $dept->deptname,
                'total_papers' => $data['total_papers'],
                'jr_ta' => $data['jr_ta'],
                'ta' => $data['ta'],
                'senior_ta' => $data['senior_ta'],
                'asst_prof' => $data['asst_prof'],
                'assoc_prof' => $data['assoc_prof'],
                'prof' => $data['prof'],
                'total_citations' => $data['total_citations'],
            ];
        }
        return $result;
    }

    private function getResearchersGradeData($universityId)
    {
        $lecturers = Lecturer::when($universityId, function ($q) use ($universityId) {
            return $q->where('university_id', $universityId);
        })->get();

        $result = [];
        foreach ($lecturers as $lecturer) {
            $papers = AcademicPaper::where('lecturer_id', $lecturer->id)
                ->when($universityId, function ($q) use ($universityId) {
                    return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                        $sq->where('university_id', $universityId);
                    });
                })->get();

            $data = $this->buildGradeRow1($lecturer->lecturername, $papers);
            $result[] = [
                'label' => $lecturer->lecturername,
                'total_papers' => $data['total_papers'],
                'jr_ta' => $data['jr_ta'],
                'ta' => $data['ta'],
                'senior_ta' => $data['senior_ta'],
                'asst_prof' => $data['asst_prof'],
                'assoc_prof' => $data['assoc_prof'],
                'prof' => $data['prof'],
                'total_citations' => $data['total_citations'],
            ];
        }
        return $result;
    }

    private function getPaperFieldGradeData($field, $universityId)
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
            $papers = AcademicPaper::where($field, $value)
                ->when($universityId, function ($q) use ($universityId) {
                    return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                        $sq->where('university_id', $universityId);
                    });
                })->get();

            $data = $this->buildGradeRow1($value, $papers);
            $result[] = [
                'label' => $value,
                'total_papers' => $data['total_papers'],
                'jr_ta' => $data['jr_ta'],
                'ta' => $data['ta'],
                'senior_ta' => $data['senior_ta'],
                'asst_prof' => $data['asst_prof'],
                'assoc_prof' => $data['assoc_prof'],
                'prof' => $data['prof'],
                'total_citations' => $data['total_citations'],
            ];
        }
        return $result;
    }

    private function getLecturerFieldGradeData($field, $universityId)
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
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($field, $value) {
                $q->where($field, $value);
            })->when($universityId, function ($q) use ($universityId) {
                return $q->whereHas('lecturer.faculty', function ($sq) use ($universityId) {
                    $sq->where('university_id', $universityId);
                });
            })->get();

            $data = $this->buildGradeRow1($value, $papers);
            $result[] = [
                'label' => $value,
                'total_papers' => $data['total_papers'],
                'jr_ta' => $data['jr_ta'],
                'ta' => $data['ta'],
                'senior_ta' => $data['senior_ta'],
                'asst_prof' => $data['asst_prof'],
                'assoc_prof' => $data['assoc_prof'],
                'prof' => $data['prof'],
                'total_citations' => $data['total_citations'],
            ];
        }
        return $result;
    }

    private function buildGradeRow1($facultyName, $papers)
    {
        // Example calculations – adjust according to your actual logic
        $totalPapers = $papers->count();
        $jrTa = $papers->filter(function ($paper) {
            return $paper->lecturer && $paper->lecturer->grade === 'Jr. Teaching Assist.';
        })->count();
        $ta = $papers->filter(function ($paper) {
            return $paper->lecturer && $paper->lecturer->grade === 'Teaching Assistant';
        })->count();
        $seniorTa = $papers->filter(function ($paper) {
            return $paper->lecturer && $paper->lecturer->grade === 'Sr. Teaching Assistant';
        })->count();
        $asstProf = $papers->filter(function ($paper) {
            return $paper->lecturer && $paper->lecturer->grade === 'Assist. Prof.';
        })->count();
        $assocProf = $papers->filter(function ($paper) {
            return $paper->lecturer && $paper->lecturer->grade === 'Assoc. Prof.';
        })->count();
        $prof = $papers->filter(function ($paper) {
            return $paper->lecturer && $paper->lecturer->grade === 'Professor';
        })->count();
        $totalCitations = $papers->sum('citation');

        return [
            'facultyName' => $facultyName,
            'total_papers' => $totalPapers,
            'jr_ta' => $jrTa,
            'ta' => $ta,
            'senior_ta' => $seniorTa,
            'asst_prof' => $asstProf,
            'assoc_prof' => $assocProf,
            'prof' => $prof,
            'total_citations' => $totalCitations,
        ];
    }
}

