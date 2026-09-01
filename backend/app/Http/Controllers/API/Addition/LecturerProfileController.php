<?php

namespace App\Http\Controllers\API\Addition;

use App\Http\Controllers\Controller;
use App\Models\LecturerProfile;
use App\Models\PromotionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Hekmatinasser\Verta\Verta;
use Mpdf\Mpdf;

class LecturerProfileController extends Controller
{
    public function index(Request $request)
    {
        $universityId = currentUniversityId();
        $query = LecturerProfile::with([
            'faculty:id,facultyname',
            'department:id,deptname',
            'promotionHistories:id,lecturer_profile_id,from_grade,to_grade,promotion_date'
        ])->select([
            'id',
            'name',
            'father_name',
            'code_no',
            'status',
            'faculty_id',
            'department_id',
            'academic_grade',
            'qualification',
            'course',
            'domestic_international',
            'academic_grade_entrence_date',
            'promotion_date'
        ]);
        if ($universityId) {
            $query->whereHas('faculty', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }

        // 🔍 Search across name, qualification, code_no
        if ($request->filled('name')) {
            $search = $request->name;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "{$search}%")
                ->orWhere('qualification', 'like', "{$search}%")
                ->orWhere('code_no', 'like', "{$search}%");
            });
        }

        // Other filters...
        if ($request->filled('academic_grade')) {
            $query->where('academic_grade', $request->academic_grade);
        }
        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $perPage = min($request->input('per_page', 10), 50);
        $profiles = $query->orderBy('id', 'asc')->paginate($perPage);

        return response()->json($profiles);
    }

    public function store(Request $request)
    {
        $universityId = currentUniversityId();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'code_no' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'faculty_id' => 'nullable|exists:faculties,id',
            'department_id' => 'nullable|exists:departments,id',
            'academic_grade' => 'nullable|string|max:50',
            'qualification' => 'nullable|string|max:255',
            'course' => 'nullable|string|max:255',
            'domestic_international' => 'nullable|in:domestic,international',
            'academic_grade_entrence_date' => 'nullable|date',
            'promotion_date' => 'nullable|date',
            'promotion_histories' => 'nullable|array',
            'promotion_histories.*.from_grade' => 'required|string',
            'promotion_histories.*.to_grade' => 'required|string',
            'promotion_histories.*.promotion_date' => 'required|date',
            'promotion_histories.*.notes' => 'nullable|string',
        ]);

        if ($universityId) {
            if (isset($validated['faculty_id'])) {
                $faculty = Faculty::where('id', $validated['faculty_id'])
                    ->where('university_id', $universityId)
                    ->first();
                if (!$faculty) {
                    return response()->json(['message' => 'Invalid faculty for this university'], 422);
                }
            }
            if (isset($validated['department_id'])) {
                $department = Department::where('id', $validated['department_id'])
                    ->where('university_id', $universityId)
                    ->first();
                if (!$department) {
                    return response()->json(['message' => 'Invalid department for this university'], 422);
                }
            }
        }

        DB::transaction(function () use ($validated) {
            $profile = LecturerProfile::create([
                'name' => $validated['name'],
                'father_name' => $validated['father_name'] ?? null,
                'code_no' => $validated['code_no'] ?? null,
                'status' => $validated['status'] ?? null,
                'faculty_id' => $validated['faculty_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'academic_grade' => $validated['academic_grade'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'course' => $validated['course'] ?? null,
                'domestic_international' => $validated['domestic_international'] ?? null,
                'academic_grade_entrence_date' => $validated['academic_grade_entrence_date'] ?? null,
                'promotion_date' => $validated['promotion_date'] ?? null,
            ]);

            foreach ($validated['promotion_histories'] ?? [] as $history) {
                PromotionHistory::create([
                    'lecturer_profile_id' => $profile->id,
                    'from_grade' => $history['from_grade'],
                    'to_grade' => $history['to_grade'],
                    'promotion_date' => $history['promotion_date'],
                    'notes' => $history['notes'] ?? null,
                ]);
            }
        });

        return response()->json(['message' => 'Profile created'], 201);
    }

    public function show($id)
    {
        $universityId = currentUniversityId();
        $profile = LecturerProfile::with(['faculty', 'department', 'promotionHistories'])->findOrFail($id);
        if ($universityId) {
            $faculty = $profile->faculty;
            if (!$faculty || $faculty->university_id != $universityId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }
        return response()->json($profile);
    }

    public function update(Request $request, $id)
    {
        $universityId = currentUniversityId();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'code_no' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'faculty_id' => 'nullable|exists:faculties,id',
            'department_id' => 'nullable|exists:departments,id',
            'academic_grade' => 'nullable|string|max:50',
            'qualification' => 'nullable|string|max:255',
            'course' => 'nullable|string|max:255',
            'domestic_international' => 'nullable|in:domestic,international',
            'academic_grade_entrence_date' => 'nullable|date',
            'promotion_date' => 'nullable|date',
            'promotion_histories' => 'nullable|array',
            'promotion_histories.*.id' => 'nullable|exists:promotion_histories,id',
            'promotion_histories.*.from_grade' => 'required|string',
            'promotion_histories.*.to_grade' => 'required|string',
            'promotion_histories.*.promotion_date' => 'required|date',
            'promotion_histories.*.notes' => 'nullable|string',
        ]);

        if ($universityId) {
            // Validate faculty and department if provided
            if (isset($validated['faculty_id'])) {
                $faculty = Faculty::where('id', $validated['faculty_id'])
                    ->where('university_id', $universityId)
                    ->first();
                if (!$faculty) {
                    return response()->json(['message' => 'Invalid faculty for this university'], 422);
                }
            }
            if (isset($validated['department_id'])) {
                $department = Department::where('id', $validated['department_id'])
                    ->where('university_id', $universityId)
                    ->first();
                if (!$department) {
                    return response()->json(['message' => 'Invalid department for this university'], 422);
                }
            }
            // Also ensure the profile itself belongs to this university
            $profile = LecturerProfile::findOrFail($id);
            $faculty = $profile->faculty;
            if (!$faculty || $faculty->university_id != $universityId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        DB::transaction(function () use ($validated, $id) {
            $profile = LecturerProfile::findOrFail($id);
            $profile->update([
                'name' => $validated['name'],
                'father_name' => $validated['father_name'] ?? null,
                'code_no' => $validated['code_no'] ?? null,
                'status' => $validated['status'] ?? null,
                'faculty_id' => $validated['faculty_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'academic_grade' => $validated['academic_grade'] ?? null,
                'qualification' => $validated['qualification'] ?? null,
                'course' => $validated['course'] ?? null,
                'domestic_international' => $validated['domestic_international'] ?? null,
                'academic_grade_entrence_date' => $validated['academic_grade_entrence_date'] ?? null,
                'promotion_date' => $validated['promotion_date'] ?? null,
            ]);

            // Sync promotion histories
            $existingIds = $profile->promotionHistories->pluck('id')->toArray();
            $incomingIds = array_filter(array_column($validated['promotion_histories'] ?? [], 'id'), fn($id) => $id !== null);
            $toDelete = array_diff($existingIds, $incomingIds);
            PromotionHistory::whereIn('id', $toDelete)->delete();

            foreach ($validated['promotion_histories'] ?? [] as $historyData) {
                $historyData['lecturer_profile_id'] = $profile->id;
                if (isset($historyData['id'])) {
                    PromotionHistory::where('id', $historyData['id'])->update($historyData);
                } else {
                    PromotionHistory::create($historyData);
                }
            }
        });

        return response()->json(['message' => 'Profile updated']);
    }

    public function destroy($id)
    {
        $universityId = currentUniversityId();
        $profile = LecturerProfile::findOrFail($id);
        if ($universityId) {
            $faculty = $profile->faculty;
            if (!$faculty || $faculty->university_id != $universityId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }
        DB::transaction(function () use ($profile) {
            $profile->promotionHistories()->delete();
            $profile->delete();
        });
        return response()->json(['message' => 'Deleted']);
    }

    public function exportPdf(Request $request)
    {
        $universityId = currentUniversityId();
        // Translation maps (same as before)
        $facultyTranslationMap = [
            'Computer Science' => 'کمپیوټر ساینس',
            'Economics' => 'اقتصاد',
            'Education' => 'ښووونه او روزنه',
            'Engineering' => 'انجینري',
            'Journalism' => 'ژورنالیزم',
            'Law & Political Science' => 'حقوق او سیاسي علوم',
            'Languages & Literature' => 'ژبه او ادبیات',
            'Medicine' => 'طب',
            'Pharmacy' => 'پارمسي',
            'Public Administration & Policy' => 'عامه اداره او پالیسې',
            'Shariah' => 'شرعیات',
            'Stomatology' => 'ستوماتولوژي',
        ];

        $departmentTranslationMap = [
            "Network" => "نیټورک",
            "Database" => "ډېټابيس",
            "Software" => "سافټویر",
            "Water & Environmental Science" => "د اوبو او چاپيریال علوم",
            "Energy" => "انرژي",
            "Civil" => "سیول",
            "Architecture" => "مهندسې",
            "English" => "انګلیسي",
            "Sport" => "سپورت",
            "Biology" => "بیولوژي",
            "Pashto" => "پښتو",
            "Pyschology & Pedogogy" => "ارواپوهنه او پیداګوژی",
            "History" => "تاریخ",
            "Dari" => "دری",
            "Math" => "ریاضي",
            "Social Science" => "ټولنیز علوم",
            "Physics" => "فزیک",
            "Chemistry" => "کیمیا",
            "Computer Learning" => "کمپیوټر زده‌کړه",
            "Geography" => "جغرافیه",
            "Islamic Knowledge & Culture" => "اسلامي پوهه او فرهنګ",
            "Islamic Teachings" => "اسلامي تعلیمات",
            "Fiqh and Qaanon" => "فقه او قانون",
            "Islamic Saqafat" => "اسلامي ثقافت",
            "Aqidah and Philosophy" => "عقیده او فلسفه",
            "BBA" => "اداره او منیجمنټ",
            "Econometry" => "اقتصاد پوهنه",
            "Entrepreneurship" => "تشبث",
            "National Economics" => "ملي اقتصاد",
            "Banking & Finance" => "بانکداري او ماليې",
            "Radio and TV" => "راډیو او تلویزیون",
            "Media" => "رسنۍ",
            "Para Clinic" => "پیرا کلینیک",
            "Surgery" => "جراحي",
            "Pediatrics" => "اطفال",
            "Internal Medicine" => "داخلي طب",
            "Forensic" => "عدلي طب",
            "ENT" => "غوږ، پوزه او ستوني",
            "Eye" => "سترګه",
            "Dermatology" => "د پوستکي درملنه (جلدي طب)",
            "Neuro pyschiatry" => "دماغي او رواني درملنه (نیورو سایکاټري)",
            "Radiology" => "رادیولوژي",
            "Orthopedic" => "د هډوکو درملنه (ارتوپيډي)",
            "Gyncialogy" => "د ښځو درملنه (نسايي طب)",
            "Public Administration" => "عامه اداره",
            "Public Policy" => "عامه پالیسي",
            "Development Management" => "پراختیايي مدیریت",
            "Attorney and Justice" => "قانون او عدالت",
            "Management and Diplomacy" => "مدیریت او ډیپلوماسۍ",
            "Arabic" => "عربي",
            "General Pharmacy" => "عمومي فارمسي",
            "General Stomatology" => "سټوماتولوژي",
            "Political Science and IR" => "سیاسي علوم او نړیوالې اړیکې",
            "Journalism" => "ژورنالیزم",
            "Public Health" => "عامه روغتیا",
            "English Language & Literature" => "د انګلیسي ژبه او ادبیات",
            "Pashto Language & Literature" => "د پښتو ژبه او ادبیات",
            "Public Relations" => "عامه اړیکې",
            "Islamic Studies" => "اسلامي تعلیمات",
            "Water & Envirnmental Science" => "د اوبو او چاپيریال علوم",
            "Architechure" => "مهندسي",
            "Physical Education" => "فزیکي زده کړې",
        ];

        $academicGrades = [
            'Jr. Teaching Assist.' => 'نامزد پوهنیار',
            'Teaching Assistant' => 'پوهنیار',
            'Sr. Teaching Assistant' => 'د پوهندوی مرستیال',
            'Assist. Prof.' => 'پوهندوی',
            'Assoc. Prof.' => 'پوهنمل',
            'Professor' => 'پوهاند',
        ];

        $qualifications = [
            'Bachelor' => 'لیسانس',
            'Master' => 'ماستر',
            'PhD' => 'دوکتورا',
            'Post PhD' => 'پوست دوکتورا',
        ];

        $statusMap = [
            'active' => 'فعال',
            'inactive' => 'غیرفعال',
        ];

        // Helper to convert numbers to Pashto digits
        $toPashtoNumbers = function($value) {
            if ($value === null || $value === '') return '';
            $western = ['0','1','2','3','4','5','6','7','8','9'];
            $pashto  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            return str_replace($western, $pashto, (string)$value);
        };

        // Helper to keep date format as is, only convert digits to Pashto
        $formatPashtoDate = function($dateStr) use ($toPashtoNumbers) {
            if (!$dateStr) return '-';
            return $toPashtoNumbers($dateStr);
        };

        // Fetch data (same as index, without pagination)
        $query = LecturerProfile::with([
            'faculty:id,facultyname',
            'department:id,deptname',
        ])->select([
            'id', 'name', 'father_name', 'code_no', 'status',
            'faculty_id', 'department_id', 'academic_grade',
            'qualification', 'course', 'domestic_international',
            'academic_grade_entrence_date', 'promotion_date'
        ]);

        if ($universityId) {
            $query->whereHas('faculty', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }

        // Apply filters (same as index)
        if ($request->filled('name')) {
            $search = $request->name;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('qualification', 'like', "%{$search}%")
                ->orWhere('code_no', 'like', "%{$search}%");
            });
        }
        if ($request->filled('academic_grade')) {
            $query->where('academic_grade', $request->academic_grade);
        }
        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Sort descending by id
        $profiles = $query->orderBy('id', 'asc')->get();

        $rows = [];
        $index = 1;
        foreach ($profiles as $item) {
            $facultyName = $item->faculty ? ($facultyTranslationMap[$item->faculty->facultyname] ?? $item->faculty->facultyname) : '-';
            $deptName = $item->department ? ($departmentTranslationMap[$item->department->deptname] ?? $item->department->deptname) : '-';
            $academicGrade = $academicGrades[$item->academic_grade] ?? $item->academic_grade ?? '-';
            $qualification = $qualifications[$item->qualification] ?? $item->qualification ?? '-';
            $status = $statusMap[$item->status] ?? $item->status ?? '-';

            $rows[] = [
                $toPashtoNumbers($index),                     // Serial number
                $item->name,
                $item->father_name ?? '-',
                $item->course ?? '-',
                $qualification,
                $toPashtoNumbers($item->academic_grade_entrence_date ?? '-'),
                $academicGrade,
                $toPashtoNumbers($item->promotion_date ?? '-'),
                $facultyName,
                $deptName,
                $item->domestic_international === 'domestic' ? 'داخلي' : ($item->domestic_international === 'international' ? 'بهرني' : '-'),
                $status,
                $item->code_no ?? '-',
            ];
            $index++;
        }

        $columns = [
            'نمبر', 'نوم', 'پلار نوم', 'تحصیلي رشته', 'تعلمی رتبه', 'شموليت نيټه', 'علمی رتبه', 'ترفیع نیټه',
            'پوهنځې', 'ډیپارټمنټ',  'داخلی/خارجی',  'اوسنئ حالت', 'کوډ نمبر'
        ];

        // Report generation date (Gregorian format with Pashto digits)
        date_default_timezone_set('Asia/Kabul');
        $reportDate = $toPashtoNumbers(now()->toDateString());

        $html = view('pdf.lecturer-profiles', [
            'columns' => $columns,
            'rows' => $rows,
            'date' => $reportDate,
            'filterSummary' => 'د استادانو پروفایل راپور | Lecturer Profiles Report',
        ])->render();

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
        return response($mpdf->Output('lecturer-profiles.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}