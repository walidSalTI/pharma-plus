<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Log;

class LlamaApiService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct(
        private MedicalPromptService $promptService
    ) {
        $this->apiKey = config('services.groq.api_key');
        $this->baseUrl = 'https://api.groq.com/openai/v1/chat/completions';
        $this->model = 'llama-3.3-70b-versatile';
    }

    /**
     * Evaluate a batch of drug-disease interactions via Groq.
     *
     * @param array $items List of ['id' => int, 'drug' => string, 'disease' => string]
     * @return array|null Array of ['id' => int, 'severity_rating' => int, 'clinical_explanation' => string] or null on failure
     */
    public function evaluateDrugDiseaseBatch(array $items): ?array
    {
        if (empty($items)) {
            return [];
        }

        $prompts = $this->promptService->buildDrugDiseaseBatchPrompt($items);

        return $this->sendBatchRequest($prompts['system'], $prompts['user']);
    }

    /**
     * Evaluate a batch of drug-drug interactions via Groq.
     *
     * @param array $items List of ['id' => int, 'drug1' => string, 'drug2' => string]
     * @return array|null Array of ['id' => int, 'severity_rating' => int, 'clinical_explanation' => string] or null on failure
     */
    public function evaluateDrugDrugBatch(array $items): ?array
    {
        if (empty($items)) {
            return [];
        }

        $prompts = $this->promptService->buildDrugDrugBatchPrompt($items);

        return $this->sendBatchRequest($prompts['system'], $prompts['user']);
    }

    /**
     * Send a batch request to Groq and parse the response.
     *
     * The AI returns: { "results": [ { "id": ..., "severity_rating": ..., "clinical_explanation": "..." } ] }
     */
    private function sendBatchRequest(string $sysPrompt, string $userPrompt): ?array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->withoutVerifying()
                ->timeout(10)
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'temperature' => 0.1,
                    'max_tokens' => 2000,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $sysPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('Llama Batch API request failed: ' . $response->body());

                return null;
            }

            $body = $response->json();

            // Groq returns content inside choices[0].message.content as a JSON string
            $content = $body['choices'][0]['message']['content'] ?? null;

            if ($content === null) {
                Log::warning('Llama API response missing content');

                return null;
            }

            $parsed = json_decode($content, true);

            // Expect { "results": [...] }
            return $parsed['results'] ?? null;
        } catch (\Exception $e) {
            Log::error('Exception in LlamaApiService Batch: ' . $e->getMessage());

            return null;
        }
    }
}
/*
response example 
{
  "id": "chatcmpl-a1b2c3d4",
  "object": "chat.completion",
  "created": 1722345600,
  "model": "llama-3.3-70b-versatile",
  "choices": [
    {
      "index": 0,
      "message": {
        "role": "assistant",
        "content": "{\n  \"results\": [\n    {\n      \"id\": 15,\n      \"severity_rating\": 2,\n      \"clinical_explanation\": \"قد يؤدي استخدام الإيبوبروفين إلى تقليل فاعلية أدوية الضغط وزيادة احتباس السائل والصوديوم في الجسم.\"\n    },\n    {\n      \"id\": 28,\n      \"severity_rating\": 0,\n      \"clinical_explanation\": \"استخدام الميتفورمين مع مرض السكري من النوع الثاني آمن ويُعتبر الخط العلاجي الأول.\"\n    }\n  ]\n}"
      },
      "finish_reason": "stop"
    }
  ],
  "usage": {
    "prompt_tokens": 180,
    "completion_tokens": 120,
    "total_tokens": 300
  }
}*/