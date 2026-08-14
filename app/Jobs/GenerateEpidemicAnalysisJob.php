<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiReportAnalysis;
use App\Models\SearchTelemetry;
use App\Services\EpidemicAnalysisService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateEpidemicAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public AiReportAnalysis $analysisRecord
    ) {}

    public function handle(EpidemicAnalysisService $epidemicService): void
    {
        try {
            $this->analysisRecord->update(['status' => 'processing']);

            // 1. استخراج إحداثيات الصيدلية وتأكيد وجودها
            $pharmacy = $this->analysisRecord->pharmacy;
            $pharmacyLat = $pharmacy->latitude;
            $pharmacyLng = $pharmacy->longitude;

            $startDate = Carbon::now()->subDays(7);
            $radiusKm = 10;

            // 2. تجميع أعلى 5 استخدامات ضمن نطاق 10 كم خلال الأسبوع الماضي (صندوق إحداثيات متوافق مع SQLite)
            $query = SearchTelemetry::query()
                ->select('resolved_usage', DB::raw('COUNT(*) as search_count'))
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('resolved_usage');

            if ($pharmacyLat !== null && $pharmacyLng !== null) {
                $latDelta = $radiusKm / 111.0;
                $lngDelta = $radiusKm / (111.0 * max(cos(deg2rad((float) $pharmacyLat)), 0.01));

                $query->whereBetween('latitude', [(float) $pharmacyLat - $latDelta, (float) $pharmacyLat + $latDelta])
                    ->whereBetween('longitude', [(float) $pharmacyLng - $lngDelta, (float) $pharmacyLng + $lngDelta]);
            }

            $topUsages = $query->groupBy('resolved_usage')
                ->orderByDesc('search_count')
                ->limit(5)
                ->get()
                ->toArray();

            // حالة عدم وجود بيانات بحث كافية
            if ($topUsages === []) {
                $this->analysisRecord->update([
                    'status' => 'completed',
                    'ai_insights' => [
                        'has_epidemic_warning' => false,
                        'message' => 'لا توجد بيانات بحث كافية ضمن شعاع 10 كم خلال الأسبوع الماضي للتحقق من وجود جائحة.',
                        'top_usages' => [],
                    ],
                ]);

                return;
            }

            // 3. تحليل البيانات بواسطة EpidemicAnalysisService (Llama 3.3 via Groq)
            $aiResult = $epidemicService->analyzeEpidemicDemand($topUsages);

            if ($aiResult === null) {
                $this->analysisRecord->update([
                    'status' => 'failed',
                    'ai_insights' => ['error' => 'فشلت الخدمة الذكية في تحليل بيانات الأوبئة.'],
                ]);

                return;
            }

            // 4. حفظ النتائج بنجاح
            $this->analysisRecord->update([
                'input_snapshot' => ['top_usages' => $topUsages, 'radius_km' => $radiusKm],
                'ai_insights' => $aiResult,
                'status' => 'completed',
            ]);

        } catch (Throwable $e) {
            Log::error('Epidemic Analysis Job Failed: '.$e->getMessage(), [
                'analysis_id' => $this->analysisRecord->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->analysisRecord->update([
                'status' => 'failed',
                'ai_insights' => ['error' => 'حدث خطأ غير متوقع أثناء معالجة تقرير الجائحة.'],
            ]);

            throw $e;
        }
    }
}
