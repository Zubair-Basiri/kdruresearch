<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Analytics\ResearchForecastingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResearchForecastingController extends Controller
{
    protected ResearchForecastingService $forecastingService;

    public function __construct(ResearchForecastingService $forecastingService)
    {
        $this->forecastingService = $forecastingService;
    }

    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId($request);

        $horizon = $request->input('forecast_horizon', 3);
        $horizon = $this->forecastingService->validateHorizon($horizon);
        $target = $request->input('target', 'publications');

        $startYear = $request->input('start_year');
        $endYear = $request->input('end_year');

        $yearsRange = $this->getAvailableYears($filters, $universityId);
        if (!$startYear) $startYear = $yearsRange['min'];
        if (!$endYear) $endYear = $yearsRange['max'];

        $historicalData = $this->getHistoricalData($filters, $universityId, $startYear, $endYear, $target);
        $forecastResult = $this->forecastingService->forecast($historicalData, $horizon);

        if (!$forecastResult['success']) {
            return response()->json([
                'scope' => [
                    'university_id' => $universityId,
                    'is_ministry' => Auth::user() && Auth::user()->role === 'ministry_authority',
                ],
                'historical_period' => [
                    'start_year' => $startYear,
                    'end_year' => $endYear,
                ],
                'forecast' => [
                    'horizon' => $horizon,
                    'target' => $target,
                    'method' => 'none',
                    'message' => $forecastResult['message'],
                ],
                'historical' => $historicalData,
                'forecast_values' => [],
                'intervals' => [],
                'summary' => null,
                'faculties' => [],
                'departments' => [],
                'research_areas' => [],
                'key_findings' => ['Insufficient historical data for forecasting.'],
                'methodology' => $this->getMethodology('none'),
            ], 200);
        }

        $currentOutput = end($historicalData);
        $nextYearForecast = $forecastResult['forecast'][0]['value'] ?? 0;
        $growthRate = $currentOutput > 0 ? round(($nextYearForecast - $currentOutput) / $currentOutput * 100, 1) : 0;

        $facultyForecast = $this->getFacultyForecast($filters, $universityId, $horizon, $target);
        $departmentForecast = $this->getDepartmentForecast($filters, $universityId, $horizon, $target);
        $researchAreaForecast = $this->getResearchAreaForecast($filters, $universityId, $horizon, $target);
        $keyFindings = $this->generateKeyFindings($forecastResult, $historicalData, $facultyForecast, $researchAreaForecast);

        return response()->json([
            'scope' => [
                'university_id' => $universityId,
                'is_ministry' => Auth::user() && Auth::user()->role === 'ministry_authority',
            ],
            'historical_period' => [
                'start_year' => $startYear,
                'end_year' => $endYear,
            ],
            'forecast' => [
                'horizon' => $horizon,
                'target' => $target,
                'method' => $forecastResult['method'],
                'r_squared' => $forecastResult['r_squared'] ?? null,
                'reliability' => $this->forecastingService->getReliability(
                    $forecastResult['r_squared'] ?? 0,
                    count($historicalData)
                ),
            ],
            'summary' => [
                'current_output' => $currentOutput,
                'next_year_forecast' => $nextYearForecast,
                'growth_rate' => $growthRate,
            ],
            'historical' => $historicalData,
            'forecast_values' => $forecastResult['forecast'],
            'intervals' => $forecastResult['intervals'] ?? [],
            'faculties' => $facultyForecast,
            'departments' => $departmentForecast,
            'research_areas' => $researchAreaForecast,
            'key_findings' => $keyFindings,
            'methodology' => $this->getMethodology($forecastResult['method']),
        ]);
    }

    // ---------- Helper Methods ----------

    protected function parseFilters(Request $request)
    {
        return [
            'university' => $request->input('university'),
            'faculty' => $request->input('faculty'),
            'department' => $request->input('department'),
            'researcher' => $request->input('researcher'),
            'year' => $request->input('year'),
            'grade' => $request->input('grade'),
            'publication_type' => $request->input('publication_type'),
            'indexed' => $request->input('indexed'),
            'language' => $request->input('language'),
            'research_area' => $request->input('research_area'),
            'collaboration_type' => $request->input('collaboration_type'),
        ];
    }

    protected function getEffectiveUniversityId(Request $request)
    {
        $user = Auth::user();
        if (!$user) return null;

        if ($user->role === 'ministry_authority') {
            return $request->input('university') ? (int) $request->input('university') : null;
        }
        return $user->university_id;
    }

    protected function getAvailableYears($filters, $universityId)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

        $this->applyFiltersToQuery($query, $filters, $universityId);

        $years = $query->select('academic_papers.year')
            ->distinct()
            ->orderBy('academic_papers.year', 'asc')
            ->pluck('year')
            ->toArray();

        return [
            'min' => $years[0] ?? null,
            'max' => $years[count($years) - 1] ?? null,
        ];
    }

    protected function getHistoricalData($filters, $universityId, $startYear, $endYear, $target)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

        // Apply all filters (including faculty, department, researcher)
        $this->applyFiltersToQuery($query, $filters, $universityId);

        if ($startYear) {
            $query->where('academic_papers.year', '>=', $startYear);
        }
        if ($endYear) {
            $query->where('academic_papers.year', '<=', $endYear);
        }

        // Target-specific aggregation
        $selectColumn = 'academic_papers.year';
        switch ($target) {
            case 'q1':
                $selectColumn .= ', SUM(CASE WHEN academic_papers.indexed = "Q1" THEN 1 ELSE 0 END) as count';
                break;
            case 'q2':
                $selectColumn .= ', SUM(CASE WHEN academic_papers.indexed = "Q2" THEN 1 ELSE 0 END) as count';
                break;
            case 'q3':
                $selectColumn .= ', SUM(CASE WHEN academic_papers.indexed = "Q3" THEN 1 ELSE 0 END) as count';
                break;
            case 'q4':
                $selectColumn .= ', SUM(CASE WHEN academic_papers.indexed = "Q4" THEN 1 ELSE 0 END) as count';
                break;
            case 'books':
                $selectColumn .= ', SUM(CASE WHEN academic_papers.publication LIKE "%Book%" THEN 1 ELSE 0 END) as count';
                break;
            default: // publications
                $selectColumn .= ', COUNT(*) as count';
                break;
        }

        $results = $query->selectRaw($selectColumn)
            ->groupBy('academic_papers.year')
            ->orderBy('academic_papers.year', 'asc')
            ->get();

        $data = [];
        foreach ($results as $row) {
            $data[(int) $row->year] = (int) $row->count;
        }

        if ($startYear && $endYear) {
            for ($year = (int) $startYear; $year <= (int) $endYear; $year++) {
                if (!isset($data[$year])) {
                    $data[$year] = 0;
                }
            }
            ksort($data);
        }

        return $data;
    }

    /**
     * Apply common filters to a query builder.
     */
    private function applyFiltersToQuery($query, $filters, $universityId)
    {
        if ($universityId) {
            $query->where('faculties.university_id', $universityId);
        } elseif (Auth::user() && Auth::user()->role === 'ministry_authority' && $filters['university']) {
            $query->where('faculties.university_id', $filters['university']);
        }

        if ($filters['faculty']) {
            $query->where('faculties.id', $filters['faculty']);
        }
        if ($filters['department']) {
            $query->where('departments.id', $filters['department']);
        }
        if ($filters['researcher']) {
            $query->where('lecturers.id', $filters['researcher']);
        }
        if ($filters['publication_type']) {
            $query->where('academic_papers.publication', $filters['publication_type']);
        }
        if ($filters['indexed']) {
            $query->where('academic_papers.indexed', $filters['indexed']);
        }
        if ($filters['language']) {
            $query->where('academic_papers.language', $filters['language']);
        }
        if ($filters['grade']) {
            $query->where('lecturers.grade', $filters['grade']);
        }
        if ($filters['collaboration_type']) {
            $query->where('academic_papers.collaboration', $filters['collaboration_type']);
        }
        // Note: 'year' is handled separately in getHistoricalData for range.
        // 'research_area' is not applied at the paper level; it's used for aggregation in getResearchAreaForecast.
    }

    protected function getFacultyForecast($filters, $universityId, $horizon, $target)
    {
        $faculties = DB::table('faculties')
            ->select('id', 'facultyname')
            ->when($universityId, function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            })
            ->get();

        $result = [];
        foreach ($faculties as $fac) {
            $facFilters = $filters;
            $facFilters['faculty'] = $fac->id;

            $historical = $this->getHistoricalData($facFilters, $universityId, null, null, $target);
            if (count($historical) < 3) {
                $result[] = [
                    'faculty' => $fac->facultyname,
                    'historical' => $historical,
                    'forecast' => [],
                    'error' => 'Insufficient data',
                ];
                continue;
            }

            $forecastResult = $this->forecastingService->forecast($historical, $horizon);
            if (!$forecastResult['success']) {
                $result[] = [
                    'faculty' => $fac->facultyname,
                    'historical' => $historical,
                    'forecast' => [],
                    'error' => $forecastResult['message'],
                ];
                continue;
            }

            $current = end($historical);
            $next = $forecastResult['forecast'][0]['value'] ?? 0;
            $growth = $current > 0 ? round(($next - $current) / $current * 100, 1) : 0;

            $result[] = [
                'faculty' => $fac->facultyname,
                'historical' => $historical,
                'forecast' => $forecastResult['forecast'],
                'current' => $current,
                'next_year' => $next,
                'growth' => $growth,
            ];
        }

        usort($result, function ($a, $b) {
            return ($b['next_year'] ?? 0) - ($a['next_year'] ?? 0);
        });

        return $result;
    }

    protected function getDepartmentForecast($filters, $universityId, $horizon, $target)
    {
        $departments = DB::table('departments')
            ->select('id', 'deptname')
            ->when($universityId, function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            })
            ->get();

        $result = [];
        foreach ($departments as $dept) {
            $deptFilters = $filters;
            $deptFilters['department'] = $dept->id;

            $historical = $this->getHistoricalData($deptFilters, $universityId, null, null, $target);
            if (count($historical) < 3) {
                continue;
            }

            $forecastResult = $this->forecastingService->forecast($historical, $horizon);
            if (!$forecastResult['success']) {
                continue;
            }

            $current = end($historical);
            $next = $forecastResult['forecast'][0]['value'] ?? 0;
            $growth = $current > 0 ? round(($next - $current) / $current * 100, 1) : 0;

            $result[] = [
                'department' => $dept->deptname,
                'historical' => $historical,
                'forecast' => $forecastResult['forecast'],
                'current' => $current,
                'next_year' => $next,
                'growth' => $growth,
            ];
        }

        usort($result, function ($a, $b) {
            return ($b['next_year'] ?? 0) - ($a['next_year'] ?? 0);
        });

        return $result;
    }

    protected function getResearchAreaForecast($filters, $universityId, $horizon, $target)
    {
        $query = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->leftJoin('departments', 'lecturers.department_id', '=', 'departments.id')
            ->whereNull('academic_papers.deleted_at')
            ->where('academic_papers.status', 'Published');

        $this->applyFiltersToQuery($query, $filters, $universityId);

        $papers = $query->select('academic_papers.year', 'lecturers.specialized_area')->get();

        $areaData = [];
        foreach ($papers as $paper) {
            $areas = json_decode($paper->specialized_area, true) ?: ['Not Specified'];
            foreach ($areas as $area) {
                $area = trim($area);
                if (empty($area)) continue;
                $area = ucwords(strtolower($area));
                if (!isset($areaData[$area])) {
                    $areaData[$area] = [];
                }
                $year = $paper->year;
                if ($year) {
                    $areaData[$area][$year] = ($areaData[$area][$year] ?? 0) + 1;
                }
            }
        }

        $result = [];
        foreach ($areaData as $area => $yearCounts) {
            ksort($yearCounts);
            $years = array_keys($yearCounts);
            if (count($years) < 3) continue;

            $historical = [];
            $minYear = min($years);
            $maxYear = max($years);
            for ($y = $minYear; $y <= $maxYear; $y++) {
                $historical[$y] = $yearCounts[$y] ?? 0;
            }

            $forecastResult = $this->forecastingService->forecast($historical, $horizon);
            if (!$forecastResult['success']) continue;

            $current = end($historical);
            $next = $forecastResult['forecast'][0]['value'] ?? 0;
            $growth = $current > 0 ? round(($next - $current) / $current * 100, 1) : 0;

            $result[] = [
                'area' => $area,
                'historical' => $historical,
                'forecast' => $forecastResult['forecast'],
                'current' => $current,
                'next_year' => $next,
                'growth' => $growth,
            ];
        }

        usort($result, function ($a, $b) {
            return ($b['next_year'] ?? 0) - ($a['next_year'] ?? 0);
        });

        return $result;
    }

    protected function generateKeyFindings($forecastResult, $historicalData, $facultyForecast, $researchAreaForecast)
    {
        $findings = [];

        if (!$forecastResult['success']) {
            $findings[] = 'Insufficient historical data to generate reliable forecasts.';
            return $findings;
        }

        $current = end($historicalData);
        $next = $forecastResult['forecast'][0]['value'] ?? 0;
        $growth = $current > 0 ? round(($next - $current) / $current * 100, 1) : 0;

        $findings[] = "Publication output has been forecasted to reach approximately {$next} in the next year.";
        if ($growth > 0) {
            $findings[] = "This represents a growth of {$growth}% compared with the latest historical year ({$current}).";
        } elseif ($growth < 0) {
            $findings[] = "This represents a decline of " . abs($growth) . "% compared with the latest historical year.";
        }

        if (!empty($facultyForecast)) {
            $top = $facultyForecast[0];
            $findings[] = "{$top['faculty']} shows the strongest projected growth among faculties with sufficient historical data.";
        }

        if (!empty($researchAreaForecast)) {
            $topArea = $researchAreaForecast[0];
            $findings[] = "{$topArea['area']} shows a sustained upward publication trend.";
        }

        return $findings;
    }

    protected function getMethodology($method)
    {
        return [
            'method' => $method,
            'historical_period' => 'Determined by available data',
            'forecast_horizon' => 'User-specified (1-5 years)',
            'algorithm' => $method === 'linear_regression' ? 'Linear regression (ordinary least squares)' : 'None',
            'reliability' => 'Based on R-squared and historical data length',
            'limitations' => 'Forecasts are estimates based on historical patterns. Actual future results may differ because of funding, policy, staffing, and other external factors.',
        ];
    }

    public function previewPdf(Request $request)
    {
        // Reuse the index logic
        $filters = $this->parseFilters($request);
        $universityId = $this->getEffectiveUniversityId($request);
        $horizon = $request->input('forecast_horizon', 3);
        $horizon = $this->forecastingService->validateHorizon($horizon);
        $target = $request->input('target', 'publications');

        $startYear = $request->input('start_year');
        $endYear = $request->input('end_year');

        $yearsRange = $this->getAvailableYears($filters, $universityId);
        if (!$startYear) $startYear = $yearsRange['min'];
        if (!$endYear) $endYear = $yearsRange['max'];

        $historicalData = $this->getHistoricalData($filters, $universityId, $startYear, $endYear, $target);
        $forecastResult = $this->forecastingService->forecast($historicalData, $horizon);

        if (!$forecastResult['success']) {
            return response("Insufficient historical data", 404);
        }

        $currentOutput = end($historicalData);
        $nextYearForecast = $forecastResult['forecast'][0]['value'] ?? 0;
        $growthRate = $currentOutput > 0 ? round(($nextYearForecast - $currentOutput) / $currentOutput * 100, 1) : 0;

        $facultyForecast = $this->getFacultyForecast($filters, $universityId, $horizon, $target);
        $researchAreaForecast = $this->getResearchAreaForecast($filters, $universityId, $horizon, $target);
        $keyFindings = $this->generateKeyFindings($forecastResult, $historicalData, $facultyForecast, $researchAreaForecast);

        $date = now()->format('Y-m-d');

        $html = view('pdf.research-forecasting', [
            'historical_period' => [
                'start_year' => $startYear,
                'end_year' => $endYear,
            ],
            'forecast' => [
                'horizon' => $horizon,
                'target' => $target,
                'method' => $forecastResult['method'],
                'r_squared' => $forecastResult['r_squared'] ?? null,
                'reliability' => $this->forecastingService->getReliability(
                    $forecastResult['r_squared'] ?? 0,
                    count($historicalData)
                ),
            ],
            'summary' => [
                'current_output' => $currentOutput,
                'next_year_forecast' => $nextYearForecast,
                'growth_rate' => $growthRate,
            ],
            'historical' => $historicalData,
            'forecast_values' => $forecastResult['forecast'],
            'intervals' => $forecastResult['intervals'] ?? [],
            'faculties' => $facultyForecast,
            'research_areas' => $researchAreaForecast,
            'key_findings' => $keyFindings,
            'methodology' => $this->getMethodology($forecastResult['method']),
            'filters' => $filters,
            'date' => $date,
            'is_ministry' => Auth::user() && Auth::user()->role === 'ministry_authority',
            'university_id' => $universityId,
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
            'directionality' => 'ltr',
        ]);

        $mpdf->WriteHTML($html);
        return response($mpdf->Output('research-forecasting.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}