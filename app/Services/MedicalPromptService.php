<?php

declare(strict_types=1);

namespace App\Services;

class MedicalPromptService
{
    /**
     * Build batch prompt for drug-disease interactions
     *
     * @param  array  $items  List of ['id' => int, 'drug' => string, 'disease' => string]
     * @return array{system: string, user: string}
     */
    public function buildDrugDiseaseBatchPrompt(array $items): array
    {
        $systemPrompt = "You are an expert clinical pharmacist. Evaluate the provided list of drug-disease interactions.
Rating rules: 0 = Safe / No Risk, 1 = Moderate Risk, 2 = Severe Risk.
CRITICAL RULE: Patients with Hypertension taking NSAIDs MUST ALWAYS be rated strictly as 2.
Return STRICT JSON format containing an array under key 'results':
{
  \"results\": [
     {\"id\": 1, \"severity_rating\": 2, \"clinical_explanation\": \"[Detailed explanation in Arabic]\"}
  ]
}";

        $formattedList = [];
        foreach ($items as $item) {
            $formattedList[] = "ID: {$item['id']} | Drug: '{$item['drug']}' | Disease: '{$item['disease']}'";
        }

        return [
            'system' => $systemPrompt,
            'user' => "Evaluate interactions:\n".implode("\n", $formattedList),
        ];
    }

    /**
     * Build batch prompt for drug-drug interactions
     *
     * @param  array  $items  List of ['id' => int, 'drug1' => string, 'drug2' => string]
     * @return array{system: string, user: string}
     */
    public function buildDrugDrugBatchPrompt(array $items): array
    {
        $systemPrompt = "You are an expert clinical pharmacist. Evaluate the provided list of drug-drug interactions.
Rating rules: 0 = Safe / No Risk, 1 = Moderate Risk, 2 = Severe Risk.
CRITICAL RULE: Combining anticoagulants/antiplatelets like Warfarin and Aspirin MUST ALWAYS be rated strictly as 2.
Return STRICT JSON format containing an array under key 'results':
{
  \"results\": [
     {\"id\": 10, \"severity_rating\": 2, \"clinical_explanation\": \"[Detailed explanation in Arabic]\"}
  ]
}";

        $formattedList = [];
        foreach ($items as $item) {
            $formattedList[] = "ID: {$item['id']} | Drug 1: '{$item['drug1']}' | Drug 2: '{$item['drug2']}'";
        }

        return [
            'system' => $systemPrompt,
            'user' => "Evaluate drug-drug interactions:\n".implode("\n", $formattedList),
        ];
    }
}
