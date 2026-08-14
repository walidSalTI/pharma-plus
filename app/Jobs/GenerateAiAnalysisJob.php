<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiReportAnalysis;
use App\Services\QwenPromptService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateAiAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public AiReportAnalysis $analysisRecord
    ) {}

    public function handle(ReportService $reportService, QwenPromptService $llmPromptService): void
    {
        try {
            $pharmacyId = $this->analysisRecord->pharmacy_id;
            $startDate = Carbon::parse($this->analysisRecord->start_date)->startOfDay();
            $endDate = Carbon::parse($this->analysisRecord->end_date)->endOfDay();

            // 1. تجميع البيانات الهامة من الـ ReportService
            $financials = $reportService->financialSummary($pharmacyId, $startDate, $endDate);
            $topMedications = $reportService->topProfitableMedications($pharmacyId, $startDate, $endDate, 5)->toArray();
            $expiringSummary = $reportService->expiringInventory($pharmacyId, $startDate, 30);
            $slowMovingCount = $reportService->slowMovingStock($pharmacyId, $startDate, $endDate)->count();

            // 2. تجميع الـ Payload لتحديث الـ Snapshot في قاعدة البيانات
            $payload = [
                'financials' => $financials,
                'top_medications' => $topMedications,
                'expiring_summary' => $expiringSummary,
                'slow_moving_count' => $slowMovingCount,
            ];

            $this->analysisRecord->update([
                'input_snapshot' => $payload,
                'status' => 'processing', // تحديث الحالة إلى جاري المعالجة
            ]);

            // 3. إرسال البيانات للـ QwenPromptService باستخدام الدالة الجديدة
            $aiResult = $llmPromptService->analyzeFinancialReport(
                $financials,
                $topMedications,
                $expiringSummary,
                $slowMovingCount
            );

            // 4. حفظ النتيجة وتحديث الحالة إلى مكتمل
            $this->analysisRecord->update([
                'ai_insights' => $aiResult,
                'status' => 'completed',
            ]);

        } catch (Throwable $e) {
            // في حال حدوث أي خطأ أثناء الاتصال أو الـ Parsing
            $this->analysisRecord->update([
                'status' => 'failed',
                'ai_insights' => [
                    'error' => $e->getMessage(),
                ],
            ]);

            // إعادة رمي الخطأ لكي يسجل في الـ Queue Worker Logs إذا أردت
            throw $e;
        }
    }
}
