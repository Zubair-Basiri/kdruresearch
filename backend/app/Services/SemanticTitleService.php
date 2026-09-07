<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SemanticTitleService
{
    protected $provider;
    protected $apiKey;
    protected $apiUrl;
    protected $model;

    public function __construct()
    {
        $this->provider = config('jina.provider', 'jina');
        $this->apiKey = config('jina.api_key');
        $this->apiUrl = config('jina.api_url', 'https://api.jina.ai/v1/embeddings');
        $this->model = config('jina.embedding_model', 'text-embedding-3-small');
    }

    /**
     * Compare the semantic similarity of two titles.
     * Returns a float between 0 and 100.
     *
     * @param string $titleA
     * @param string $titleB
     * @return float|null  null on failure
     */
    public function compare(string $titleA, string $titleB): ?float
    {
        if (!$this->apiKey) {
            Log::warning('SemanticTitleService: No API key configured.');
            return null;
        }

        $cacheKey = 'semantic_compare_' . md5($titleA . '|' . $titleB);
        return Cache::remember($cacheKey, 3600, function () use ($titleA, $titleB) {
            return $this->performComparison($titleA, $titleB);
        });
    }

    protected function performComparison(string $titleA, string $titleB): ?float
    {
        try {
            $method = 'compareWith' . ucfirst($this->provider);
            if (method_exists($this, $method)) {
                return $this->$method($titleA, $titleB);
            }
            Log::warning("SemanticTitleService: Provider {$this->provider} not supported.");
            return null;
        } catch (\Exception $e) {
            Log::error('SemanticTitleService error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Example with OpenAI chat completion.
     * You can replace with any AI service.
     */
    protected function compareWithOpenai(string $titleA, string $titleB): ?float
    {
        $prompt = <<<PROMPT
Compare the following two academic paper titles and return a similarity score from 0 to 100.
A score of 100 means they represent exactly the same research topic or claim.
A score of 0 means completely unrelated.
Consider synonyms and cross‑language equivalence when evaluating.
Title A: "$titleA"
Title B: "$titleB"
Return ONLY a number between 0 and 100, nothing else.
PROMPT;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl ?: 'https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that compares academic titles semantically.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.0,
            'max_tokens' => 10,
        ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            $score = (float) preg_replace('/[^0-9.]/', '', $content);
            return min(100, max(0, $score));
        }

        Log::warning('SemanticTitleService: OpenAI call failed.', ['status' => $response->status()]);
        return null;
    }

    /**
     * Fallback: You can add other providers (Google, LibreTranslate, etc.).
     */
    protected function compareWithGoogletranslate(string $titleA, string $titleB): ?float
    {
        // Implement if needed
        return null;
    }
}