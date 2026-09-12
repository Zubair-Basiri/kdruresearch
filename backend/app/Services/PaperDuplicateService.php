<?php

namespace App\Services;

use App\Models\SubmittedPaper;
use App\Models\AcademicPaper;
use App\Support\TitleNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PaperDuplicateService
{
    protected TitleNormalizer $normalizer;
    protected int $sameLanguageThreshold;
    protected int $crossLanguageThreshold;
    protected int $translationThreshold;
    protected int $maxJinaCandidates;
    protected array $authorPosMapping = [];
    protected int $embeddingCacheTtl;
    protected int $translationCacheTtl;

    public function __construct(TitleNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
        $this->sameLanguageThreshold = config('jina.same_language_threshold', 90);
        $this->crossLanguageThreshold = config('jina.cross_language_threshold', 85);
        $this->translationThreshold = config('jina.translation_threshold', 75);
        $this->maxJinaCandidates = config('jina.max_candidates', 20);
        $this->embeddingCacheTtl = config('jina.cache_ttl.embedding', 86400);
        $this->translationCacheTtl = config('jina.cache_ttl.translation', 3600);
        $this->buildAuthorPositionMapping();
    }

    // ---------- MAIN ENTRY POINT ----------
    public function findDuplicate(
    string $title,
    ?string $authorPosition,
    ?string $language,
    ?int $excludeId = null,
    ?string $excludeTable = null,
    ?array $tablesToCheck = null
): ?array {
    Log::info('DUPLICATE CHECK START', [
        'title' => $title,
        'author_position' => $authorPosition,
        'language' => $language,
        'exclude_id' => $excludeId,
        'exclude_table' => $excludeTable,
        'tables_to_check' => $tablesToCheck,
    ]);

    $normalizedLanguage = $this->normalizer->normalizeLanguage($language);
    $normalizedAuthorPosition = $this->normalizeAuthorPosition($authorPosition);

    $rawPositions = $normalizedAuthorPosition !== null
        ? ($this->authorPosMapping[$normalizedAuthorPosition] ?? [])
        : [null];

    $candidates = $this->fetchCandidates($rawPositions, $excludeId, $excludeTable, $tablesToCheck);

    if (empty($candidates)) {
        Log::info('No candidates found, skipping duplicate check.');
        return null;
    }

    $incomingNormalized = $this->normalizer->normalizeExact($title);

    // 1. Exact match
    foreach ($candidates as $candidate) {
        $existingNormalized = $this->normalizer->normalizeExact($candidate['title']);
        if ($existingNormalized === $incomingNormalized) {
            Log::info('EXACT DUPLICATE FOUND', ['candidate' => $candidate]);
            return $this->buildResult('exact', 100, $candidate);
        }
    }

    // 2. Same‑language lexical
    $sameLangCandidates = array_filter($candidates, function ($c) use ($normalizedLanguage) {
        $cLang = $this->normalizer->normalizeLanguage($c['language'] ?? null);
        return $cLang !== null && $cLang === $normalizedLanguage;
    });

    if (!empty($sameLangCandidates)) {
        $result = $this->checkSameLanguage($incomingNormalized, $title, $sameLangCandidates);
        if ($result) {
            Log::info('SAME LANGUAGE DUPLICATE FOUND', ['result' => $result]);
            return $result;
        }
    }

    // 3. Cross‑language
    $crossLangCandidates = array_filter($candidates, function ($c) use ($normalizedLanguage) {
        $cLang = $this->normalizer->normalizeLanguage($c['language'] ?? null);
        return $cLang === null || $cLang !== $normalizedLanguage;
    });

    if (!empty($crossLangCandidates)) {
        $result = $this->checkCrossLanguage($title, $crossLangCandidates, $normalizedLanguage);
        if ($result) {
            Log::info('CROSS LANGUAGE DUPLICATE FOUND', ['result' => $result]);
            return $result;
        }
    }

    Log::info('No duplicate found.');
    return null;
    }

    public function isSubstantiallyDifferent(string $oldTitle, string $newTitle, float $threshold = 0.6): bool
{
    $oldNorm = $this->normalizer->normalizeExact($oldTitle);
    $newNorm = $this->normalizer->normalizeExact($newTitle);

    if ($oldNorm === $newNorm) {
        return false; // identical after normalization
    }

    $oldTokens = $this->normalizer->tokenize($oldNorm);
    $newTokens = $this->normalizer->tokenize($newNorm);

    $similarity = $this->normalizer->jaccardSimilarity($oldTokens, $newTokens);

    // If similarity is below the threshold, the change is substantial → run the duplicate check.
    // If similarity is at/above the threshold, it's just a refinement → skip.
    return $similarity < $threshold;
}

    // ---------- CROSS‑LANGUAGE ORCHESTRATOR ----------
    protected function checkCrossLanguage(string $incomingRaw, array $candidates, ?string $normalizedLanguage): ?array
    {
        $jinaResult = $this->checkCrossLanguageJina($incomingRaw, $candidates);
        if ($jinaResult) {
            Log::info('JINA DUPLICATE FOUND');
            return $jinaResult;
        }

        if ($normalizedLanguage !== null) {
            Log::info('Jina did not find duplicate, trying translation fallback.');
            return $this->checkCrossLanguageTranslation($incomingRaw, $candidates, $normalizedLanguage);
        }

        return null;
    }

    // ---------- JINA CHECK ----------
    protected function checkCrossLanguageJina(string $incomingRaw, array $candidates): ?array
    {
        $apiKey = config('jina.api_key');
        if (empty($apiKey)) {
            Log::warning('Jina API key missing – skipping cross‑language semantic check.');
            return null;
        }

        $limited = array_slice($candidates, 0, $this->maxJinaCandidates);
        $texts = [$incomingRaw];
        foreach ($limited as $c) {
            $texts[] = $c['title'];
        }

        $embeddings = $this->getJinaEmbeddings($texts);
        if ($embeddings === null) {
            Log::warning('Jina embeddings failed, skipping Jina check.');
            return null;
        }

        $incomingVec = $embeddings[0];
        $best = null;
        $bestScore = 0;

        $threshold = (float) $this->crossLanguageThreshold;
        if (str_word_count($incomingRaw) < 6) {
            $threshold = 65;
        }

        for ($i = 1; $i < count($embeddings); $i++) {
            $cos = $this->cosineSimilarity($incomingVec, $embeddings[$i]);
            $score = $cos * 100;
            $candidate = $limited[$i - 1];
            Log::info('JINA SIMILARITY', [
                'candidate_id' => $candidate['id'],
                'candidate_title' => $candidate['title'],
                'similarity' => $score,
                'threshold' => $threshold,
            ]);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        if ($best && $bestScore >= $threshold) {
            return $this->buildResult('cross_language', $bestScore, $best);
        }

        Log::info('Jina similarity below threshold, best score: ' . $bestScore);
        return null;
    }

    // ---------- TRANSLATION FALLBACK ----------
    protected function checkCrossLanguageTranslation(string $incomingRaw, array $candidates, ?string $incomingLang): ?array
    {
        if ($incomingLang === null) {
            Log::info('Incoming language is null, skipping translation check.');
            return null;
        }

        $limitedCandidates = array_slice($candidates, 0, 10);

        $incomingEnglish = $this->translateToEnglish($incomingRaw, $incomingLang);
        if ($incomingEnglish === null) {
            Log::info('Translation failed for incoming title, skipping translation check.');
            return null;
        }

        $incomingNormalized = $this->normalizer->normalizeExact($incomingEnglish);
        $incomingTokens = $this->normalizer->tokenize($incomingNormalized);

        $bestScore = 0;
        $bestCandidate = null;

        foreach ($limitedCandidates as $candidate) {
            $candidateLang = $this->normalizer->normalizeLanguage($candidate['language'] ?? '');
            if ($candidateLang === $incomingLang) {
                continue;
            }

            $candidateEnglish = $this->translateToEnglish($candidate['title'], $candidateLang);
            if ($candidateEnglish === null) {
                continue;
            }

            $candNormalized = $this->normalizer->normalizeExact($candidateEnglish);
            $candTokens = $this->normalizer->tokenize($candNormalized);

            $jaccard = $this->normalizer->jaccardSimilarity($incomingTokens, $candTokens) * 100;
            $lev = $this->normalizer->levenshteinSimilarity($incomingNormalized, $candNormalized);
            $score = max($jaccard, $lev);

            Log::info('TRANSLATION COMPARISON', [
                'incoming_raw' => $incomingRaw,
                'incoming_en' => $incomingEnglish,
                'candidate_raw' => $candidate['title'],
                'candidate_en' => $candidateEnglish,
                'score' => $score,
                'threshold' => $this->translationThreshold,
            ]);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $candidate;
            }
        }

        if ($bestCandidate && $bestScore >= $this->translationThreshold) {
            return $this->buildResult('cross_language_translation', $bestScore, $bestCandidate);
        }

        Log::info('Translation comparison best score: ' . $bestScore . ' below threshold.');
        return null;
    }

    // ---------- TRANSLATION HELPERS ----------
    protected function translateToEnglish(string $text, ?string $sourceLang): ?string
    {
        if ($sourceLang === 'en') {
            return $text;
        }

        $cacheKey = 'translation_en_' . md5($text);
        try {
            $cached = Cache::get($cacheKey);
            if ($cached !== null && !$this->isTranslationError($cached)) {
                return $cached;
            }
        } catch (\Exception $e) {
            // ignore
        }

        $translated = $this->translateWithMyMemory($text, 'en');
        if ($translated !== null && !$this->isTranslationError($translated)) {
            Cache::put($cacheKey, $translated, $this->translationCacheTtl);
            return $translated;
        }

        $translated = $this->translateWithLibre($text, 'en');
        if ($translated !== null && !$this->isTranslationError($translated)) {
            Cache::put($cacheKey, $translated, $this->translationCacheTtl);
            return $translated;
        }

        Log::warning('Translation failed for text: ' . $text);
        return null;
    }

    protected function isTranslationError(string $text): bool
    {
        $errorPatterns = [
            'INVALID SOURCE LANGUAGE', 'invalid source language', 'error',
            'too many requests', 'API key not found', 'rate limit',
            'MYMEMORY WARNING', 'NO RESPONSE',
        ];
        foreach ($errorPatterns as $pattern) {
            if (stripos($text, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function translateWithMyMemory(string $text, string $target): ?string
    {
        if (!config('jina.translation.my_memory.enabled', true)) {
            return null;
        }
        try {
            $url = config('jina.translation.my_memory.url', 'https://api.mymemory.translated.net/get');
            $timeout = config('jina.translation.my_memory.timeout', 6);
            $response = Http::timeout($timeout)->get($url, [
                'q' => $text,
                'langpair' => 'auto|' . $target,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $translated = $data['responseData']['translatedText'] ?? null;
                if ($translated && $translated !== $text && !$this->isTranslationError($translated)) {
                    return $translated;
                }
                if (isset($data['responseStatus']) && $data['responseStatus'] !== 200) {
                    Log::warning('MyMemory error', ['status' => $data['responseStatus'], 'message' => $data['responseDetails'] ?? '']);
                }
            } else {
                Log::warning('MyMemory HTTP error', ['status' => $response->status()]);
            }
        } catch (\Exception $e) {
            Log::error('MyMemory exception: ' . $e->getMessage());
        }
        return null;
    }

    protected function translateWithLibre(string $text, string $target): ?string
    {
        if (!config('jina.translation.libre_translate.enabled', true)) {
            return null;
        }
        try {
            $url = config('jina.translation.libre_translate.url', 'https://libretranslate.com/translate');
            $timeout = config('jina.translation.libre_translate.timeout', 5);
            $response = Http::timeout($timeout)->post($url, [
                'q' => $text,
                'source' => 'auto',
                'target' => $target,
                'format' => 'text',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $translated = $data['translatedText'] ?? null;
                if ($translated && !$this->isTranslationError($translated)) {
                    return $translated;
                }
            }
            Log::warning('LibreTranslate failed', ['status' => $response->status()]);
        } catch (\Exception $e) {
            Log::error('LibreTranslate exception: ' . $e->getMessage());
        }
        return null;
    }

    // ---------- SAME LANGUAGE CHECK ----------
    protected function checkSameLanguage(string $incomingNormalized, string $incomingRaw, array $candidates): ?array
    {
        $incomingTokens = $this->normalizer->tokenize($incomingNormalized);

        foreach ($candidates as $candidate) {
            $existingNormalized = $this->normalizer->normalizeExact($candidate['title']);
            $existingTokens = $this->normalizer->tokenize($existingNormalized);

            $jaccard = $this->normalizer->jaccardSimilarity($incomingTokens, $existingTokens);
            $lev = $this->normalizer->levenshteinSimilarity($incomingNormalized, $existingNormalized);
            $score = max($jaccard * 100, $lev);

            Log::info('SAME LANGUAGE SIMILARITY', [
                'candidate_id' => $candidate['id'],
                'candidate_title' => $candidate['title'],
                'score' => $score,
                'threshold' => $this->sameLanguageThreshold,
            ]);

            if ($score >= $this->sameLanguageThreshold) {
                return $this->buildResult('same_language', $score, $candidate);
            }
        }

        return null;
    }

    // ---------- CANDIDATE FETCHING ----------
    protected function fetchCandidates(
    array $rawPositions,
    ?int $excludeId,
    ?string $excludeTable,
    ?array $tablesToCheck = null
): array {
    $universityId = currentUniversityId();

    // Decide which tables to scan
    $checkSubmitted = $tablesToCheck === null || in_array('submitted', $tablesToCheck, true);
    $checkAcademic  = $tablesToCheck === null || in_array('academic', $tablesToCheck, true);

    Log::info('Fetching candidates', [
        'rawPositions' => $rawPositions,
        'universityId' => $universityId,
        'check_submitted' => $checkSubmitted,
        'check_academic' => $checkAcademic,
    ]);

    $candidates = [];

    // ---------- Submitted papers ----------
    if ($checkSubmitted) {
        $submittedQuery = SubmittedPaper::query();
        if (empty($rawPositions) || (count($rawPositions) === 1 && $rawPositions[0] === null)) {
            if (in_array(null, $rawPositions, true)) {
                $submittedQuery->whereNull('author_position');
            } else {
                $submittedQuery->whereRaw('1 = 0');
            }
        } else {
            $submittedQuery->whereIn('author_position', $rawPositions);
        }
        if ($excludeId !== null && $excludeTable === 'submitted') {
            $submittedQuery->where('id', '!=', $excludeId);
        }
        if ($universityId) {
            $submittedQuery->whereHas('lecturer', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }
        $submitted = $submittedQuery->get(['id', 'title', 'author_position', 'language'])->toArray();
        foreach ($submitted as $row) {
            $candidates[] = array_merge($row, ['table' => 'submitted_papers']);
        }
    }

    // ---------- Academic papers ----------
    if ($checkAcademic) {
        $academicQuery = AcademicPaper::withoutTrashed();
        if (empty($rawPositions) || (count($rawPositions) === 1 && $rawPositions[0] === null)) {
            if (in_array(null, $rawPositions, true)) {
                $academicQuery->whereNull('author_position');
            } else {
                $academicQuery->whereRaw('1 = 0');
            }
        } else {
            $academicQuery->whereIn('author_position', $rawPositions);
        }
        if ($excludeId !== null && $excludeTable === 'academic') {
            $academicQuery->where('id', '!=', $excludeId);
        }
        if ($universityId) {
            $academicQuery->whereHas('lecturer', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }
        $academic = $academicQuery->get(['id', 'title', 'author_position', 'language'])->toArray();
        foreach ($academic as $row) {
            $candidates[] = array_merge($row, ['table' => 'academic_papers']);
        }
    }

    Log::info('Fetched candidates', ['count' => count($candidates)]);
    return $candidates;
    }

    // ---------- AUTHOR POSITION NORMALIZATION ----------
    protected function normalizeAuthorPosition(?string $pos): ?string
    {
        if ($pos === null) {
            return null;
        }
        $pos = strtolower(trim($pos));
        $pos = preg_replace('/(st|nd|rd|th)$/i', '', $pos);
        $map = [
            'first' => '1', 'second' => '2', 'third' => '3', 'fourth' => '4', 'fifth' => '5',
            'sixth' => '6', 'seventh' => '7', 'eighth' => '8', 'ninth' => '9', 'tenth' => '10',
            'لومړی' => '1', 'دویم' => '2', 'دریم' => '3', 'څلورم' => '4', 'پنځم' => '5',
            'شپږم' => '6', 'اووم' => '7', 'اتم' => '8', 'نهم' => '9', 'لسم' => '10',
        ];
        $pos = str_replace(array_keys($map), array_values($map), $pos);
        return preg_replace('/\D+/', '', $pos) ?: null;
    }

    // ---------- AUTHOR POSITION MAPPING ----------
    protected function buildAuthorPositionMapping(): void
    {
        $positions = SubmittedPaper::distinct()->pluck('author_position')
            ->merge(AcademicPaper::withoutTrashed()->distinct()->pluck('author_position'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $mapping = [];
        foreach ($positions as $raw) {
            $normalized = $this->normalizeAuthorPosition($raw);
            if ($normalized !== null) {
                $mapping[$normalized][] = $raw;
            }
        }
        foreach ($mapping as $norm => &$list) {
            $list = array_unique($list);
        }
        $this->authorPosMapping = $mapping;
    }

    // ---------- JINA EMBEDDING HELPERS ----------
    protected function getJinaEmbeddings(array $texts): ?array
    {
        $apiKey = config('jina.api_key');
        $model = config('jina.embedding_model', 'jina-embeddings-v5-text-small');
        $url = config('jina.api_url', 'https://api.jina.ai/v1/embeddings');
        $timeout = config('jina.timeout', 10);

        $cachePrefix = 'jina_embedding_';
        $embeddings = [];
        $missingIndices = [];

        foreach ($texts as $idx => $text) {
            $key = $cachePrefix . md5($model . '|' . $text);
            try {
                $cached = Cache::get($key);
                if ($cached !== null) {
                    $embeddings[$idx] = $cached;
                } else {
                    $missingIndices[] = $idx;
                }
            } catch (\Exception $e) {
                $missingIndices[] = $idx;
            }
        }

        if (!empty($missingIndices)) {
            $missingTexts = array_map(fn($idx) => $texts[$idx], $missingIndices);
            $newEmbeddings = $this->fetchJinaBatch($missingTexts, $model, $url, $timeout, $apiKey);
            if ($newEmbeddings === null) {
                return null;
            }
            foreach ($missingIndices as $pos => $idx) {
                $embeddings[$idx] = $newEmbeddings[$pos];
                $key = $cachePrefix . md5($model . '|' . $texts[$idx]);
                try {
                    Cache::put($key, $newEmbeddings[$pos], $this->embeddingCacheTtl);
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }

        $ordered = [];
        for ($i = 0; $i < count($texts); $i++) {
            if (!isset($embeddings[$i])) {
                return null;
            }
            $ordered[] = $embeddings[$i];
        }
        return $ordered;
    }

    protected function fetchJinaBatch(array $texts, string $model, string $url, int $timeout, string $apiKey): ?array
    {
        if (empty($texts)) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($timeout)->post($url, [
                'model' => $model,
                'input' => $texts,
                'encoding_format' => 'float',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $embeddings = [];
                foreach ($data['data'] as $item) {
                    $embeddings[] = $item['embedding'];
                }
                if (count($embeddings) !== count($texts)) {
                    Log::error('Jina returned unexpected number of embeddings', [
                        'expected' => count($texts),
                        'received' => count($embeddings)
                    ]);
                    return null;
                }
                return $embeddings;
            } else {
                Log::error('Jina API error', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Exception $e) {
            Log::error('Jina API exception', ['message' => $e->getMessage()]);
        }
        return null;
    }

    // ---------- COSINE SIMILARITY ----------
    protected function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0;
        $normA = 0;
        $normB = 0;
        $count = count($a);
        if ($count !== count($b)) {
            Log::warning('Vector dimension mismatch', ['dim_a' => $count, 'dim_b' => count($b)]);
            return 0.0;
        }
        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }
        $sim = $dot / (sqrt($normA) * sqrt($normB));
        return max(-1.0, min(1.0, $sim));
    }

    // ---------- RESULT BUILDER ----------
    protected function buildResult(string $type, float $similarity, array $candidate): array
    {
        return [
            'duplicate' => true,
            'duplicate_type' => $type,
            'similarity' => round($similarity, 1),
            'matched_paper_id' => $candidate['id'],
            'matched_table' => $candidate['table'],
            'matched_title' => $candidate['title'],
            'matched_author_position' => $candidate['author_position'],
        ];
    }
}