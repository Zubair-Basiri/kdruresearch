<?php

namespace App\Support;

class TitleNormalizer
{
    /**
     * Normalize a title for exact comparison.
     */
    public function normalizeExact(string $title): string
    {
        $title = trim($title);

        // Normalize Arabic-script character variants
        $title = str_replace(
            ['ي', 'ة', 'أ', 'إ', 'آ', 'ؤ', 'ئ', 'ۀ', 'ى', 'ك'],
            ['ی', 'ه', 'ا', 'ا', 'ا', 'و', 'ی', 'ه', 'ی', 'ک'],
            $title
        );

        // Remove Arabic diacritics
        $title = preg_replace('/[\x{064B}-\x{0652}]/u', '', $title);

        // Remove tatweel
        $title = str_replace('ـ', '', $title);

        // Normalize digits: Persian/Arabic → Latin
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $latinDigits   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $title = str_replace($persianDigits, $latinDigits, $title);

        // Lowercase Latin characters
        $title = mb_strtolower($title, 'UTF-8');

        // Remove punctuation: keep only letters, numbers, spaces
        $title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $title);

        // Collapse multiple spaces and trim
        $title = preg_replace('/\s+/', ' ', trim($title));

        return $title;
    }

    /**
     * Normalize language string to a canonical code.
     *
     * @param string|null $language
     * @return string|null  'en', 'ps', 'fa', 'ar', or the original if unknown
     */
    public function normalizeLanguage(?string $language): ?string
    {
        if ($language === null) {
            return null;
        }

        $lang = strtolower(trim($language));

        // English variants
        if (in_array($lang, ['en', 'english', 'eng'])) {
            return 'en';
        }

        // Pashto variants
        if (in_array($lang, ['ps', 'pashto', 'پښتو'])) {
            return 'ps';
        }

        // Persian/Dari variants
        if (in_array($lang, ['fa', 'persian', 'farsi', 'dari', 'دری', 'فارسی'])) {
            return 'fa';
        }

        // Arabic variants
        if (in_array($lang, ['ar', 'arabic', 'عربي', 'العربية'])) {
            return 'ar';
        }

        // Unknown – return as is
        return $lang;
    }

    /**
     * Tokenize a title into words (for fuzzy similarity).
     */
    public function tokenize(string $text): array
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', trim($clean));
        return array_filter($words, fn($w) => mb_strlen($w) > 1);
    }

    /**
     * Compute Jaccard similarity between two token sets.
     */
    public function jaccardSimilarity(array $tokensA, array $tokensB): float
    {
        $intersection = count(array_intersect($tokensA, $tokensB));
        $union = count(array_unique(array_merge($tokensA, $tokensB)));
        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Levenshtein similarity as percentage.
     */
    public function levenshteinSimilarity(string $a, string $b): float
    {
        $distance = levenshtein($a, $b);
        $maxLen = max(mb_strlen($a), mb_strlen($b));
        if ($maxLen === 0) {
            return 100.0;
        }
        return (1 - $distance / $maxLen) * 100;
    }
}