<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

class QwenPromptService
{
    public function __construct(protected QwenApiService $qwenApiService) {}

    /**
     * تحليل التقرير المالي واستخراج المرئيات بالذكاء الاصطناعي
     */
    public function analyzeFinancialReport(
        array $financials,
        array $topMedications,
        array $expiringSummary,
        int $slowMovingCount
    ): array {
        // 1. استخراج المصاريف والتشغيليات بأمان
        $losses = $financials['operational_losses'] ?? [];

        // 2. بناء الـ Payload المنسق بنصوص دقيقة لمنع أخطاء الخانات والقيم الصفرية
        $payload = [
            'sales' => number_format((float) ($financials['gross_sales'] ?? 0)),
            'returns' => number_format((float) ($financials['returns_amount'] ?? 0)),
            'net_revenue' => number_format((float) ($financials['net_revenue'] ?? 0)),
            'gross_profit' => number_format((float) ($financials['gross_profit'] ?? 0)),
            'expenses' => number_format((float) ($losses['expenses'] ?? 0)),
            'salaries' => number_format((float) ($losses['salaries'] ?? 0)),
            'damaged_cost' => number_format((float) ($losses['damaged_cost'] ?? 0)),
            'expired_loss' => number_format((float) ($financials['expired_inventory_loss']['value'] ?? 0)),
            'net_profit' => number_format((float) ($financials['net_profit'] ?? 0)),
            'slow_moving_items_count' => $slowMovingCount,
        ];

        // 🔴 تسجيل اللوج للتأكد من وصول الأرقام الحقيقية في سجلات لارافيل
        Log::info('Qwen Payload Sent:', $payload);

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // 3. بناء الـ System Prompt المحسّن
        $systemPrompt = <<<PROMPT
You are an expert financial analyst for Syrian retail pharmacies.
Analyze the provided JSON financial data and output ONLY a valid JSON response in Arabic without any markdown formatting or code blocks.

RULES:
1. CURRENCY: Always use "ليرة سورية" or "ل.س". NEVER use USD, SAR, dollars, or any other currency.
2. USE EXACT NUMBERS: You MUST include the exact numbers from DATA in executive_summary, key_findings, recommendations, and alerts. DO NOT speak in general terms.
3. LOGIC:
   - "net_revenue" means net sales revenue (صافي الإيرادات).
   - If net_profit > 0, financial_health_score MUST be "ممتاز" or "جيد". NEVER use "حرِج".
   - "returns" means customer returned items (مردودات مبيعات).
4. PRACTICAL ADVICE: Recommendations must be realistic for a retail pharmacy (e.g., discount slow-moving stock, improve FEFO rotation). DO NOT mention product development or manufacturing.

DATA:
{$jsonPayload}

OUTPUT JSON SCHEMA:
{
  "executive_summary": "ملخص تنفيذي يذكر صافي الإيرادات وصافي الربح بالأرقام المحددة في DATA",
  "financial_health_score": "ممتاز",
  "key_findings": ["نقطة 1 تتضمن رقم المبيعات ورقم المردودات من DATA", "نقطة 2 تتضمن الأرباح والمصاريف بالأرقام من DATA"],
  "actionable_recommendations": ["توصية عملية لعمل حسومات على المواد الراكدة", "توصية لاتّباع نظام FEFO للحد من التالف"],
  "inventory_risk_alerts": ["تنبيه يذكر قيمة التالف بالأرقام من DATA"]
}
PROMPT;

        return $this->qwenApiService->generateJsonResponse($systemPrompt, [], 0.0);
    }
}
