<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Medication;
use Illuminate\Database\Eloquent\Collection;

class AlternativeMappingEngine
{
    public function findAlternatives(string $medicationId): Collection
    {
        // 1. جلب الدواء الأساسي مع مواده الفعالة
        $source = Medication::with('activeIngredients')->find($medicationId);

        if (! $source || $source->activeIngredients->isEmpty()) {
            return new Collection;
        }

        // 2. استخراج الـ IDs الفريدة للمواد الفعالة (أخذ الـ unique لمنع التكرار الجرعي) 🎯
        $sourceIngredientIds = $source->activeIngredients->pluck('id')->unique()->toArray();
        $sourceIngredientCount = count($sourceIngredientIds); // في حالتنا هنا سيصبح العدد 1 بدلاً من 4

        // 3. البحث باستخدام Eloquent عن الأدوية التي تحتوي على نفس المواد الفعالة
        return Medication::with(['activeIngredients', 'manufacture'])
            ->where('id', '!=', $medicationId)
            ->whereHas('activeIngredients', function ($query) use ($sourceIngredientIds) {
                $query->whereIn('active_ingredients.id', $sourceIngredientIds);
            })
            ->get()
            ->filter(function (Medication $candidate) use ($sourceIngredientIds, $sourceIngredientCount): bool {
                // استخراج المواد الفعالة الفريدة للمنتج المرشح أيضاً
                $candidateIngredientsIds = $candidate->activeIngredients->pluck('id')->unique()->toArray();
                $candidateIngredientsCount = count($candidateIngredientsIds);

                // أ- التأكد من أن عدد المواد الفعالة "الفريدة" متطابق تماماً
                if ($candidateIngredientsCount !== $sourceIngredientCount) {
                    return false;
                }

                // ب- التأكد من تطابق المادة الفعالة بالهوية (عدم وجود اختلاف بالـ IDs)
                return array_diff($candidateIngredientsIds, $sourceIngredientIds) === [];
            })
            ->values();
    }
}
