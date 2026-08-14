<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EpidemicAnalysisService
{
    private readonly string $apiKey;

    private readonly string $baseUrl;

    private readonly string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.groq.api_key');
        $this->baseUrl = 'https://api.groq.com/openai/v1/chat/completions';
        $this->model = 'llama-3.3-70b-versatile';
    }

    /**
     * Analyze top drug usages to detect potential disease outbreaks.
     *
     * @param  array  $topUsages  List of ['resolved_usage' => string, 'search_count' => int]
     * @return array|null Parsed JSON insights or null on failure
     */
    public function analyzeEpidemicDemand(array $topUsages): ?array
    {
        if ($topUsages === []) {
            return null;
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($topUsages);

        return $this->sendRequest($systemPrompt, $userPrompt);
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert Epidemiologist and Clinical Pharmacologist analyzing weekly medication search telemetry to detect potential disease outbreaks.

Strict Rules for Analysis:
1. CLUSTER & COMBINE SIMILAR INDICATIONS:
   If two or more usages target the exact same infectious or communicable clinical condition (e.g., "Amoxicillin + Clavulanate for Resistant Infection" and "Cephalosporin for Bacterial Infection" both treat "Bacterial Pharyngitis / Influenza", or multiple "Antiviral for Hepatitis / Viral Infection"), you MUST COMBINE their search counts into a single aggregate total.

2. DOMINANT USAGE EVALUATION & SPIKE DETECTION:
   - Treat conditions like Viral Hepatitis, Respiratory Infections, Bacterial Infections, and Viral Diseases explicitly as INFECTIOUS/COMMUNICABLE.
   - Declare an EPIDEMIC WARNING if a single infectious disease/condition reaches 15 or more combined searches AND represents a distinct infectious spike in the area, EVEN IF non-infectious chronic usages (like hypertension or diabetes) constitute a large portion of overall traffic.
   - Do NOT require an infectious condition to exceed 50% of ALL total searches (which include chronic/general medications) to be considered an epidemic. If an infectious condition hits 15+ searches, escalate it immediately.

3. EVENLY DISTRIBUTED DEMAND (NO PANIC / NO FALSE ALARM):
   - Only declare "Normal" status if search counts for infectious diseases are genuinely low (below 15), scattered across unrelated mild conditions, or consist purely of baseline chronic medication searches without any infectious spike reaching 15+ searches.

4. EXCLUDE NON-INFECTIOUS / CHRONIC CONDITIONS:
   - Completely ignore chronic, non-communicable conditions (e.g., Hypertension, Diabetes, Hyperlipidemia, Maintenance Care) when evaluating if an infectious disease is spiking.

5. MINIMUM VOLUME THRESHOLD:
   - Do NOT issue an epidemic warning if the total combined search count for a specific infectious condition is LESS THAN 15. Treat total counts below 15 as statistically insignificant baseline traffic.

OUTPUT FORMAT:
You MUST respond with a valid JSON object in pure Modern Standard Arabic (no English words) matching this exact schema:
{
  "has_epidemic_warning": true/false,
  "detected_disease": "اسم المرض المستهدف باللغة العربية الفصحى أو null",
  "threat_level": "High / Medium / Low / Normal",
  "combined_demand_score": 0,
  "clinical_summary": "شرح علمي محدد ومختصر باللغة العربية الفصحى يوضح هل تم تجميع استطبابات متماثلة وكيف أدت للنتيجة",
  "actionable_pharmacy_advice": [
    "توصية 1 باللغة العربية الفصحى لإدارة المخزون",
    "توصية 2 باللغة العربية الفصحى للممارسة السريرية"
  ]
}
PROMPT;
    }

    private function buildUserPrompt(array $topUsages): string
    {
        $jsonContext = json_encode($topUsages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return "Analyze the following top 5 drug search usages collected within a 10km radius over the past week:\n\n{$jsonContext}";
    }

    private function sendRequest(string $systemPrompt, string $userPrompt): ?array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->withoutVerifying()
                ->timeout(15)
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'temperature' => 0.1,
                    'max_tokens' => 1500,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Epidemic Llama API request failed: ' . $response->body());

                return null;
            }

            $body = $response->json();
            $content = $body['choices'][0]['message']['content'] ?? null;

            if ($content === null) {
                Log::warning('Epidemic Llama API response missing content');

                return null;
            }

            return json_decode((string) $content, true);
        } catch (Exception $e) {
            Log::error('Exception in EpidemicAnalysisService: ' . $e->getMessage());

            return null;
        }
    }
}
