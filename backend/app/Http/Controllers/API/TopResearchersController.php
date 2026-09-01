<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Hekmatinasser\Verta\Verta;
use Mpdf\Mpdf;
use App\Models\AcademicPaper;

class TopResearchersController extends Controller
{
    /**
     * Configuration for the weighted score formula.
     * Order must exactly match columns L to AH in Excel.
     */
    protected $config = [
        // Weights for columns L to AH (23 values)
        'weights' => [11, 30, 12, 80, 40, 15, 5, 60, 11, 90, 70, 60, 50, 40, 22, 3, 2, 1, 0.5, 0, 0, 0, 22],

        // Column keys (used internally) – in same order as Excel
        'columns' => [
            'non_indexed_national_conference',
            'total_indexed_conference',
            'total_book_chapters',
            'books_published_academic',
            'books_published_non_academic',
            'non_indexed_international_conference',
            'total_international_poster',
            'book_translations',
            'national_publications',
            'q1_indexed',
            'q2_indexed',
            'q3_indexed',
            'q4_indexed',
            'other_recognized_index',
            'internationally_peer_reviewed',
            'first_author',
            'second_third_author',
            'fourth_fifth_author',
            'other_author',
            'research_funding_sources',
            'h_index',
            'total_citations',
            'digital_course_development'
        ],

        // Indices (0‑based) for the special bonus columns
        'bonus' => [
            'lookup_column' => 20, // h_index (AF)
            'if_column22'   => 21, // total_citations (AG)
            'if_column18'   => 17, // fourth_fifth_author (AC)
        ],
    ];

    public function index(Request $request)
    {
        // Fetch all researchers with their metrics.
        $researchers = $this->fetchResearchersWithMetrics();

        // Compute weighted score for each researcher
        foreach ($researchers as &$r) {
            $r['weighted_score'] = $this->calculateWeightedScore($r, $this->config);
        }

        // Assign ranks
        $this->assignRanks($researchers);

        // Assign faculty ranks
        $this->assignFacultyRanks($researchers);

        // Transform to the format expected by the Vue component
        $formatted = array_map(function ($r) {
            return [
                'id' => $r['lecturer_id'],
                'Researcher Name' => $r['lecturername'],
                'Faculty'         => $r['facultyname'],
                'Department'      => $r['deptname'],
                'Academic Grade'  => $r['grade'],

                // All 23 metrics – keys must match the Vue fields array
                'Non-indexed National Conference Proceedings' => $r['non_indexed_national_conference'],
                'Total Indexed Conference Proceedings'       => $r['total_indexed_conference'],
                'Total Book Chapters'                         => $r['total_book_chapters'],
                'Books Published (Academic)'                  => $r['books_published_academic'],
                'Books Published (Non-academic)'              => $r['books_published_non_academic'],
                'Non-indexed International Conference Proceedings' => $r['non_indexed_international_conference'],
                'Total International Conference Poster Presentation' => $r['total_international_poster'],
                'Book Translations'                            => $r['book_translations'],
                'National Publications'                        => $r['national_publications'],
                'Q1 Indexed'                                   => $r['q1_indexed'],
                'Q2 Indexed'                                   => $r['q2_indexed'],
                'Q3 Indexed'                                   => $r['q3_indexed'],
                'Q4 Indexed'                                   => $r['q4_indexed'],
                'Other Recognized Index'                       => $r['other_recognized_index'],
                'Internationally Peer Reviewed Journals'       => $r['internationally_peer_reviewed'],
                '1st Author Position'                          => $r['first_author'],
                '2nd and 3rd Author Position'                  => $r['second_third_author'],
                '4th and 5th Author Position'                  => $r['fourth_fifth_author'],
                'Other Author Position'                        => $r['other_author'],
                'Research Funding Sources'                     => $r['research_funding_sources'],
                'H index'                                      => $r['h_index'],
                'Total Citations'                              => $r['total_citations'],
                'Digital Course Development'                   => $r['digital_course_development'],

                // Computed fields
                'Weighted Score' => $r['weighted_score'] ?? '-',
                'Rank'           => $r['rank'] ?? '-',
                'Faculty Rank'   => $r['faculty_rank'] ?? '-',
            ];
        }, $researchers);

        return response()->json([
            'researchers' => $formatted
        ]);
    }

    /**
     * Fetch all researchers with their 23 metrics.
     * This method aggregates data from academic_papers and lecturers.
     */
    private function fetchResearchersWithMetrics()
    {
        // Subquery that joins papers with lecturers, faculties, departments
        // We'll use a single query with conditional SUMs based on the Excel criteria.

        $subQuery = DB::table('academic_papers')
            ->join('lecturers', 'academic_papers.lecturer_id', '=', 'lecturers.id')
            ->join('faculties', 'lecturers.faculty_id', '=', 'faculties.id')
            ->join('departments', 'lecturers.department_id', '=', 'departments.id')
            ->where('academic_papers.status', 'Published')  // Critical: only published papers
            ->select(
                'lecturers.id as lecturer_id',
                'lecturers.lecturername',
                'faculties.facultyname',
                'departments.deptname',
                'lecturers.grade',

                // 1. Non-indexed National Conference
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'Non-Indexed  National (Conference Proceedings)' 
                                AND academic_papers.collaboration = 'National' THEN 1 ELSE 0 END) as non_indexed_national_conference"),

                // 2. Total Indexed Conference (International collaboration)
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'Indexed (Conference Proceedings)'
                                AND academic_papers.collaboration LIKE '%International%' THEN 1 ELSE 0 END) as total_indexed_conference"),

                // 3. Total Book Chapters
                DB::raw("SUM(CASE WHEN academic_papers.publication = 'Book Chapter' THEN 1 ELSE 0 END) as total_book_chapters"),

                // 4. Books Published (Academic)
                DB::raw("SUM(CASE WHEN academic_papers.publication = 'Book (Authored) Academic' THEN 1 ELSE 0 END) as books_published_academic"),

                // 5. Books Published (Non-academic)
                DB::raw("SUM(CASE WHEN academic_papers.publication = 'Book (Authored) Non-academic' THEN 1 ELSE 0 END) as books_published_non_academic"),

                // 6. Non-indexed International Conference
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'Non-Indexed (Conference Proceedings)'
                                AND academic_papers.collaboration = 'International' THEN 1 ELSE 0 END) as non_indexed_international_conference"),

                // 7. Total International Conference Poster Presentation
                DB::raw("SUM(CASE WHEN academic_papers.publication = 'Conference Presentation (Abstract/Poster)'
                                AND academic_papers.collaboration IN ('National','International') THEN 1 ELSE 0 END) as total_international_poster"),

                // 8. Book Translations
                DB::raw("SUM(CASE WHEN academic_papers.publication = 'Translation Work' THEN 1 ELSE 0 END) as book_translations"),

                // 9. National Publications
                DB::raw("SUM(CASE WHEN academic_papers.collaboration = 'National'
                                AND academic_papers.indexed = 'Non-Indexed (peer reviewed National)' THEN 1 ELSE 0 END) as national_publications"),

                // 10. Q1 Indexed (International)
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'Q1'
                                AND academic_papers.collaboration LIKE '%International%' THEN 1 ELSE 0 END) as q1_indexed"),

                // 11. Q2 Indexed (International)
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'Q2'
                                AND academic_papers.collaboration LIKE '%International%' THEN 1 ELSE 0 END) as q2_indexed"),

                // 12. Q3 Indexed (International)
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'Q3'
                                AND academic_papers.collaboration LIKE '%International%' THEN 1 ELSE 0 END) as q3_indexed"),

                // 13. Q4 Indexed (International)
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'Q4'
                                AND academic_papers.collaboration LIKE '%International%' THEN 1 ELSE 0 END) as q4_indexed"),

                // 14. Other Recognized Index (ORI)
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'ORI'
                                AND academic_papers.collaboration LIKE '%International%' THEN 1 ELSE 0 END) as other_recognized_index"),

                // 15. Internationally Peer Reviewed Journals
                DB::raw("SUM(CASE WHEN academic_papers.indexed = 'Non-Indexed (peer reviewed)'
                                AND academic_papers.collaboration LIKE '%International%' THEN 1 ELSE 0 END) as internationally_peer_reviewed"),

                // 16. 1st Author Position
                DB::raw("SUM(CASE WHEN academic_papers.author_position = '1st' THEN 1 ELSE 0 END) as first_author"),

                // 17. 2nd and 3rd Author Position
                DB::raw("SUM(CASE WHEN academic_papers.author_position IN ('2nd','3rd') THEN 1 ELSE 0 END) as second_third_author"),

                // 18. 4th and 5th Author Position
                DB::raw("SUM(CASE WHEN academic_papers.author_position IN ('4th','5th') THEN 1 ELSE 0 END) as fourth_fifth_author"),

                // 19. Other Author Position (>5th)
                DB::raw("SUM(CASE WHEN academic_papers.author_position NOT IN ('1st','2nd','3rd','4th','5th') THEN 1 ELSE 0 END) as other_author"),

                // 20. Research Funding Sources (any of the listed funding types)
                DB::raw("SUM(CASE WHEN academic_papers.funding IN ('University (Internal)','National Government/Local','International Donor (UN, World Bank, ADB, USAID, etc.)','International Academic/Research Grant','Private/Industry') 
                                THEN 1 ELSE 0 END) as research_funding_sources"),

                // 21. H-index – you need to store or compute this per researcher
                DB::raw("0 as h_index"),

                // 22. Total Citations
                DB::raw("SUM(academic_papers.citation) as total_citations"),

                // 23. Digital Course Development – sum from another table or column
                DB::raw("0 as digital_course_development")     // replace with actual sum
            )
            ->groupBy('lecturers.id', 'lecturers.lecturername', 'faculties.facultyname', 'departments.deptname', 'lecturers.grade');

        $universityId = currentUniversityId();
        if ($universityId) {
            $subQuery->where('faculties.university_id', $universityId);
        }

        $results = $subQuery->get();

        // Convert to array and ensure numeric fields are floats
        return $results->map(function ($row) {
            $arr = (array) $row;
            foreach ($arr as $key => $value) {
                if (is_numeric($value)) {
                    $arr[$key] = (float) $value;
                }
            }
            return $arr;
        })->toArray();
    }

    /**
     * Calculate weighted score for a single researcher using the exact Excel formula.
     */
    private function calculateWeightedScore($researcher, $config)
    {
        // Extract the 23 metric values in correct order
        $values = [];
        foreach ($config['columns'] as $col) {
            $values[] = $researcher[$col] ?? 0;
        }

        // If all values are zero, return null (no score)
        if (array_sum($values) == 0) {
            return null;
        }

        // 1. SUMPRODUCT with weights
        $sumProduct = 0;
        foreach ($values as $i => $val) {
            $sumProduct += $val * $config['weights'][$i];
        }

        // 2. LOOKUP bonus based on h_index (column 21 = AF)
        $hIndex = $values[$config['bonus']['lookup_column']];
        $lookupKeys   = [1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
        $lookupScores = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $lookupBonus = 0;
        for ($i = count($lookupKeys)-1; $i >= 0; $i--) {
            if ($hIndex >= $lookupKeys[$i]) {
                $lookupBonus = $lookupScores[$i];
                break;
            }
        }

        // 3. IF bonus based on citations (AG) and fourth_fifth_author (AC)
        $citations   = $values[$config['bonus']['if_column22']];
        $fourthFifth = $values[$config['bonus']['if_column18']];

        if ($citations <= 9) {
            $ifBonus = 0.1;
        } else {
            // Excel ROUNDUP away from zero for negative numbers
            $roundUp = $this->excelRoundUp(($fourthFifth - 9) / 10, 0);
            $ifBonus = 0.1 * (1 + $roundUp);
        }

        $totalScore = $sumProduct + (3 * $lookupBonus) + $ifBonus;
        return round($totalScore, 2);
    }

    /**
     * Mimic Excel's ROUNDUP function (always rounds away from zero).
     */
    private function excelRoundUp($value, $precision = 0)
    {
        $factor = pow(10, $precision);
        if ($value >= 0) {
            return ceil($value * $factor) / $factor;
        } else {
            return floor($value * $factor) / $factor; // floor for negative numbers (more negative)
        }
    }

    /**
     * Assign ranks using RANK.EQ logic (same rank for ties, then skip ranks).
     */
    /**
 * Assign ranks using RANK.EQ logic (same rank for ties, then skip ranks).
 */
    private function assignRanks(&$researchers)
    {
        // Sort by weighted_score descending, nulls last
        usort($researchers, function ($a, $b) {
            $scoreA = $a['weighted_score'] ?? -INF;
            $scoreB = $b['weighted_score'] ?? -INF;
            return $scoreB <=> $scoreA;
        });

        $rank = 1;
        $prevScore = null;
        $tieCount = 0;

        foreach ($researchers as &$r) {
            if ($r['weighted_score'] === null) {
                $r['rank'] = '-';
                continue;
            }

            if ($prevScore === null) {
                // First researcher with a valid score
                $r['rank'] = $rank;
                $prevScore = $r['weighted_score'];
            } else {
                if ($r['weighted_score'] == $prevScore) {
                    // Tie: same rank as previous
                    $r['rank'] = $rank;
                    $tieCount++;
                } else {
                    // New score: rank increases by number of ties + 1
                    $rank += $tieCount + 1;
                    $tieCount = 0;
                    $r['rank'] = $rank;
                    $prevScore = $r['weighted_score'];
                }
            }
        }
    }

    public function previewPdf(Request $request)
    {
        // Get parameters from the request (mirroring the Vue component)
        $search = $request->query('search', '');
        $topField = $request->query('topField', 'All');
        $selectedFields = $request->query('selectedFields', '');
        if (is_string($selectedFields) && !empty($selectedFields)) {
            $selectedFields = explode(',', $selectedFields);
        } else {
            $selectedFields = [];
        }

        // Fetch and compute researchers data (same as index method)
        $researchers = $this->fetchResearchersWithMetrics();
        foreach ($researchers as &$r) {
            $r['weighted_score'] = $this->calculateWeightedScore($r, $this->config);
        }
        $this->assignRanks($researchers);
        $this->assignFacultyRanks($researchers);

        // Transform to the format used by the frontend (same keys as index)
        $formatted = array_map(function ($r) {
            return [
                'Researcher Name' => $r['lecturername'],
                'Faculty'         => $r['facultyname'],
                'Department'      => $r['deptname'],
                'Academic Grade'  => $r['grade'],
                'Non-indexed National Conference Proceedings' => $r['non_indexed_national_conference'],
                'Total Indexed Conference Proceedings'       => $r['total_indexed_conference'],
                'Total Book Chapters'                         => $r['total_book_chapters'],
                'Books Published (Academic)'                  => $r['books_published_academic'],
                'Books Published (Non-academic)'              => $r['books_published_non_academic'],
                'Non-indexed International Conference Proceedings' => $r['non_indexed_international_conference'],
                'Total International Conference Poster Presentation' => $r['total_international_poster'],
                'Book Translations'                            => $r['book_translations'],
                'National Publications'                        => $r['national_publications'],
                'Q1 Indexed'                                   => $r['q1_indexed'],
                'Q2 Indexed'                                   => $r['q2_indexed'],
                'Q3 Indexed'                                   => $r['q3_indexed'],
                'Q4 Indexed'                                   => $r['q4_indexed'],
                'Other Recognized Index'                       => $r['other_recognized_index'],
                'Internationally Peer Reviewed Journals'       => $r['internationally_peer_reviewed'],
                '1st Author Position'                          => $r['first_author'],
                '2nd and 3rd Author Position'                  => $r['second_third_author'],
                '4th and 5th Author Position'                  => $r['fourth_fifth_author'],
                'Other Author Position'                        => $r['other_author'],
                'Research Funding Sources'                     => $r['research_funding_sources'],
                'H index'                                      => $r['h_index'],
                'Total Citations'                              => $r['total_citations'],
                'Digital Course Development'                   => $r['digital_course_development'],
                'Weighted Score'                               => $r['weighted_score'] ?? '-',
                'Rank'                                         => $r['rank'] ?? '-',
                'Faculty Rank'                                 => $r['faculty_rank'] ?? '-',
            ];
        }, $researchers);

        // --- NEW: Handle "Top in Faculty" ---
        if ($topField === 'Top in Faculty') {
            $topCount = (int) ($request->query('topInFacultyCount', 3));
            $selectedFaculty = $request->query('selectedFaculty', 'All');

            if ($selectedFaculty !== 'All') {
                $formatted = array_filter($formatted, function ($item) use ($selectedFaculty) {
                    return $item['Faculty'] === $selectedFaculty;
                });
                $formatted = array_values($formatted);
            }

            $facultyGroups = [];
            foreach ($formatted as $researcher) {
                $faculty = $researcher['Faculty'] ?? 'Unknown';
                if (!isset($facultyGroups[$faculty])) {
                    $facultyGroups[$faculty] = [];
                }
                $facultyGroups[$faculty][] = $researcher;
            }

            $result = [];
            foreach ($facultyGroups as $faculty => $members) {
                usort($members, function ($a, $b) {
                    $sa = $a['Weighted Score'] !== '-' ? (float) $a['Weighted Score'] : -INF;
                    $sb = $b['Weighted Score'] !== '-' ? (float) $b['Weighted Score'] : -INF;
                    return $sb <=> $sa;
                });
                $top = array_slice($members, 0, $topCount);
                $result = array_merge($result, $top);
            }
            usort($result, function ($a, $b) {
                $fa = $a['Faculty'] ?? '';
                $fb = $b['Faculty'] ?? '';
                if ($fa !== $fb) return strcmp($fa, $fb);
                return $a['Rank'] - $b['Rank'];
            });
            $formatted = $result;
        }
        // --- End of new block ---

        // Apply search filter
        if (!empty($search)) {
            $formatted = array_filter($formatted, function ($item) use ($search) {
                return stripos($item['Researcher Name'], $search) !== false;
            });
            $formatted = array_values($formatted);
        }

        // Build columns
        if ($topField === 'All' || $topField === 'Top in Faculty') {
            $columns = [
                'Researcher Name', 'Faculty', 'Department', 'Academic Grade',
                'Non-indexed National Conference Proceedings',
                'Total Indexed Conference Proceedings',
                'Total Book Chapters',
                'Books Published (Academic)',
                'Books Published (Non-academic)',
                'Non-indexed International Conference Proceedings',
                'Total International Conference Poster Presentation',
                'Book Translations',
                'National Publications',
                'Q1 Indexed',
                'Q2 Indexed',
                'Q3 Indexed',
                'Q4 Indexed',
                'Other Recognized Index',
                'Internationally Peer Reviewed Journals',
                '1st Author Position',
                '2nd and 3rd Author Position',
                '4th and 5th Author Position',
                'Other Author Position',
                'Research Funding Sources',
                'H index',
                'Total Citations',
                'Digital Course Development',
                'Weighted Score',
                'Rank',
                'Faculty Rank'
            ];
            // For Top in Faculty, we already handled sorting; for All, we sort by overall Rank
            if ($topField === 'All') {
                usort($formatted, function ($a, $b) {
                    return ($a['Rank'] === '-' ? 9999 : $a['Rank']) - ($b['Rank'] === '-' ? 9999 : $b['Rank']);
                });
            }
        } else {
            // Metric-specific view
            if (empty($selectedFields)) {
                $columns = ['Researcher Name', 'Rank'];
            } else {
                $columns = $selectedFields;
                if (!in_array('Rank', $columns)) {
                    $columns[] = 'Rank';
                }
            }
            $formatted = array_filter($formatted, function ($item) use ($topField) {
                return ($item[$topField] ?? 0) > 0;
            });
            $formatted = array_values($formatted);
            usort($formatted, function ($a, $b) use ($topField) {
                return ($b[$topField] ?? 0) - ($a[$topField] ?? 0);
            });
            foreach ($formatted as $idx => &$item) {
                $item['Rank'] = $idx + 1;
            }
        }

        // Prepare data rows for the table
        $rows = [];
        foreach ($formatted as $item) {
            $row = [];
            foreach ($columns as $col) {
                $row[$col] = $item[$col] ?? (is_numeric($item[$col]) ? 0 : '-');
            }
            $rows[] = $row;
        }

        // Hijri Shamsi date (Pashto numerals)
        date_default_timezone_set('Asia/Kabul');
        $verta = new Verta();
        $shamsi = $verta->format('Y-m-d');
        $pashtoDate = $this->toPashtoNumbers($shamsi);

        // Render view
        $html = view('pdf.top-researchers', [
            'columns'      => $columns,
            'rows'         => $rows,
            'date'         => $pashtoDate,
            'topField'     => $topField,
            'search'       => $search,
            'selectedCount' => count($selectedFields),
        ])->render();

        // mPDF configuration (Bahij Nazanin, A4 landscape)
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
        return response($mpdf->Output('top-researchers.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    // Helper to convert numbers to Pashto digits
    private function toPashtoNumbers($string)
    {
        $western = ['0','1','2','3','4','5','6','7','8','9'];
        $pashto  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return str_replace($western, $pashto, $string);
    }

    public function getResearcherPapers($id)
    {
        $papers = AcademicPaper::where('lecturer_id', $id)
            ->select('id', 'title', 'year', 'publication', 'indexed', 'citation')
            ->orderBy('year', 'desc')
            ->get();
        
        return response()->json($papers);
    }

    /**
 * Assign faculty-level ranks using RANK.EQ logic.
 */
    private function assignFacultyRanks(&$researchers)
    {
        // Group by faculty
        $facultyGroups = [];
        foreach ($researchers as &$r) {
            $faculty = $r['facultyname'] ?? null;
            if (!$faculty) continue;
            $facultyGroups[$faculty][] = &$r;
        }

        foreach ($facultyGroups as $faculty => &$group) {
            // Sort by weighted_score descending, nulls last
            usort($group, function ($a, $b) {
                $scoreA = $a['weighted_score'] ?? -INF;
                $scoreB = $b['weighted_score'] ?? -INF;
                return $scoreB <=> $scoreA;
            });

            $rank = 1;
            $prevScore = null;
            $tieCount = 0;

            foreach ($group as &$r) {
                if ($r['weighted_score'] === null) {
                    $r['faculty_rank'] = '-';
                    continue;
                }

                if ($prevScore === null) {
                    $r['faculty_rank'] = $rank;
                    $prevScore = $r['weighted_score'];
                } else {
                    if ($r['weighted_score'] == $prevScore) {
                        $r['faculty_rank'] = $rank;
                        $tieCount++;
                    } else {
                        $rank += $tieCount + 1;
                        $tieCount = 0;
                        $r['faculty_rank'] = $rank;
                        $prevScore = $r['weighted_score'];
                    }
                }
            }
        }

        // Ensure all researchers have faculty_rank set
        foreach ($researchers as &$r) {
            if (!isset($r['faculty_rank'])) {
                $r['faculty_rank'] = '-';
            }
        }
    }
}