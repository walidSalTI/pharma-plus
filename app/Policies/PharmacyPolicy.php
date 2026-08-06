<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pharmacy;
use App\Models\User;

class PharmacyPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        $pharmacist = $user->pharmacist;

        if (! $pharmacist) {
            return false;
        }

        return null;
    }

    public function manage(User $user, Pharmacy $pharmacy): bool
    {
        return $this->checkPermission($user, $pharmacy, 'pharmacy_manage');
    }

    public function manageInventory(User $user, Pharmacy $pharmacy): bool
    {
        return $this->checkPermission($user, $pharmacy, 'inventory_manage');
    }

    public function manageOperatingHours(User $user, Pharmacy $pharmacy): bool
    {
        return $this->checkPermission($user, $pharmacy, 'operating_hours_manage');
    }

    public function processOrders(User $user, Pharmacy $pharmacy): bool
    {
        return $this->checkPermission($user, $pharmacy, 'orders_process');
    }

    public function viewOwnOrders(User $user, Pharmacy $pharmacy): bool
    {
        return $this->checkPermission($user, $pharmacy, 'orders_view_own');
    }

    public function viewDashboard(User $user, Pharmacy $pharmacy): bool
    {
        $pharmacist = $user->pharmacist;

        if (! $pharmacist) {
            return false;
        }

        if ($pharmacy->pharmacist_id === $pharmacist->id) {
            return true;
        }

        return $pharmacist->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->exists();
    }

    private function checkPermission(User $user, Pharmacy $pharmacy, string $column): bool
    {
        $pharmacist = $user->pharmacist;

        if ($pharmacy->pharmacist_id === $pharmacist->id) {
            return true;
        }

        return $pharmacist->staffPharmacies()
            ->where('pharmacy_id', $pharmacy->id)
            ->wherePivot($column, true)
            ->exists();
    }
}
