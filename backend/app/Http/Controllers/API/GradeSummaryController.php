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

        switch ($metric) {

            case "Faculty/Grade":
                return $this->byFaculty();

            case "Department/Grade":
                return $this->byDepartment();

            case "Researchers/Grade":
                return $this->byLecturer();

            case "Publication Type/Grade":
                return $this->byPaperField('publication');

            case "Funding Source/Grade":
                return $this->byPaperField('funding');

            case "Indexed/Grade":
                return $this->byPaperField('indexed');

            case "Collaboration/Grade":
                return $this->byPaperField('collaboration');

            case "Status/Grade":
                return $this->byPaperField('status');

            case "Qualification/Grade":
                return $this->byLecturerField('qualification');

            case "Language/Grade":
                return $this->byPaperField('language');

            default:
                return response()->json([]);
        }
    }

    /* ---------------------------------
       FACULTY
    --------------------------------- */
    private function byFaculty()
    {
        $faculties = Faculty::all();
        $result = [];

        foreach ($faculties as $faculty) {

            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($faculty) {
                $q->where('faculty_id', $faculty->id);
            })->get();

            $result[] = $this->buildGradeRow($faculty->facultyname, $papers);
        }

        return response()->json($result);
    }

    /* ---------------------------------
       DEPARTMENT
    --------------------------------- */
    private function byDepartment()
    {
        $departments = Department::all();
        $result = [];

        foreach ($departments as $dept) {

            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($dept) {
                $q->where('department_id', $dept->id);
            })->get();

            $result[] = $this->buildGradeRow($dept->deptname, $papers);
        }

        return response()->json($result);
    }

    /* ---------------------------------
    RESEARCHERS / LECTURERS
    --------------------------------- */
    private function byLecturer()
    {
        $lecturers = Lecturer::all();
        $result = [];

        foreach ($lecturers as $lecturer) {

            // Get all papers for this lecturer
            $papers = AcademicPaper::where('lecturer_id', $lecturer->id)->get();

            // Build grade row
            $result[] = $this->buildGradeRow($lecturer->lecturername, $papers);
        }

        return response()->json($result);
    }

    /* ---------------------------------
       LECTURER FIELD (Qualification)
    --------------------------------- */
    private function byLecturerField($field)
    {
        $values = Lecturer::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->pluck($field);

        $result = [];

        foreach ($values as $value) {

            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($field, $value) {
                $q->where($field, $value);
            })->get();

            $result[] = $this->buildGradeRow($value, $papers);
        }

        return response()->json($result);
    }

    /* ---------------------------------
       PAPER FIELD
    --------------------------------- */
    private function byPaperField($field)
    {
        $values = AcademicPaper::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->pluck($field);

        $result = [];

        foreach ($values as $value) {

            $papers = AcademicPaper::where($field, $value)->get();

            $result[] = $this->buildGradeRow($value, $papers);
        }

        return response()->json($result);
    }

    /* ---------------------------------
       CORE GRADE CALCULATION
    --------------------------------- */
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
        switch ($metric) {

            case "Faculty/Grade":
                $records = $this->getFacultyGradeData();
                break;

            case "Department/Grade":
                $records = $this->getDepartmentGradeData();
                break;

            case "Researchers/Grade":
                $records = $this->getResearchersGradeData();
                break;

            case "Publication Type/Grade":
                $records = $this->getPaperFieldGradeData('publication');
                break;

            case "Funding Source/Grade":
                $records = $this->getPaperFieldGradeData('funding');
                break;

            case "Indexed/Grade":
                $records = $this->getPaperFieldGradeData('indexed');
                break;

            case "Collaboration/Grade":
                $records = $this->getPaperFieldGradeData('collaboration');
                break;

            case "Status/Grade":
                $records = $this->getPaperFieldGradeData('status');
                break;

            case "Qualification/Grade":
                $records = $this->getLecturerFieldGradeData('qualification');
                break;

            case "Language/Grade":
                $records = $this->getPaperFieldGradeData('language');
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

    /**
     * Get raw faculty grade data as array
     */
    private function getFacultyGradeData()
    {
        $faculties = Faculty::all();
        $result = [];

        foreach ($faculties as $faculty) {
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($faculty) {
                $q->where('faculty_id', $faculty->id);
            })->get();

            $data = $this->buildGradeRow1($faculty->facultyname, $papers);

            // Uniform label key
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

    private function getDepartmentGradeData()
    {
        $departments = Department::all();
        $result = [];

        foreach ($departments as $dept) {
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($dept) {
                $q->where('department_id', $dept->id);
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

    private function getResearchersGradeData()
    {
        $lecturers = Lecturer::all();
        $result = [];

        foreach ($lecturers as $lecturer) {
            $papers = AcademicPaper::where('lecturer_id', $lecturer->id)->get();
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

    private function getPaperFieldGradeData($field)
    {
        $values = AcademicPaper::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->pluck($field);

        $result = [];

        foreach ($values as $value) {
            $papers = AcademicPaper::where($field, $value)->get();
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

    private function getLecturerFieldGradeData($field)
    {
        $values = Lecturer::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->pluck($field);

        $result = [];

        foreach ($values as $value) {
            $papers = AcademicPaper::whereHas('lecturer', function ($q) use ($field, $value) {
                $q->where($field, $value);
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

