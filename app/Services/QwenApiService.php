<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QwenApiService
{
    protected string $baseUrl;

    protected string $model;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.qwen.url', 'http://127.0.0.1:11434/api/generate');
        $this->model = config('services.qwen.model', 'qwen2.5:3b');
        $this->timeout = (int) config('services.qwen.timeout', 60);
    }

    /**
     * Send System Prompt and Payload to Qwen LLM and receive parsed JSON response.
     *
     *
     * @throws Exception
     */
    public function generateJsonResponse(string $systemPrompt, array $payloadData, float $temperature = 0.0): array
    {
        $userPrompt = "Here is the operational JSON data to analyze:\n".json_encode($payloadData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            $response = Http::timeout($this->timeout)->post($this->baseUrl, [
                'model' => $this->model,
                'prompt' => $systemPrompt."\n\n".$userPrompt,
                'format' => 'json',
                'stream' => false,
                'options' => [
                    'temperature' => $temperature,
                ],
            ]);

            if ($response->failed()) {
                Log::error('Qwen API Error Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception("Qwen LLM request failed with status: {$response->status()}");
            }

            $rawOutput = $response->json('response');

            if (empty($rawOutput)) {
                throw new Exception('Empty response received from Qwen LLM.');
            }

            $parsedData = json_decode((string) $rawOutput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Qwen JSON Parsing Error', [
                    'raw' => $rawOutput,
                    'json_error' => json_last_error_msg(),
                ]);
                throw new Exception('Failed to parse LLM response into valid JSON: '.json_last_error_msg());
            }

            return $parsedData;

        } catch (Exception $e) {
            Log::channel('single')->error('QwenApiService Exception: '.$e->getMessage());
            throw $e;
        }
    }
}
