<?php

declare(strict_types=1);

use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('pharmacy.{pharmacyId}', function (User $user, string $pharmacyId): false|array {
    $pharmacist = $user->pharmacist;

    if (! $pharmacist) {
        return false;
    }

    $pharmacy = Pharmacy::find($pharmacyId);

    if (! $pharmacy) {
        return false;
    }

    if ($pharmacy->pharmacist_id === $pharmacist->id) {
        return ['id' => $pharmacist->id, 'name' => $user->f_name.' '.$user->l_name];
    }

    $isStaff = $pharmacist->staffPharmacies()
        ->where('pharmacy_id', $pharmacy->id)
        ->wherePivot('orders_process', true)
        ->exists();

    if ($isStaff) {
        return ['id' => $pharmacist->id, 'name' => $user->f_name.' '.$user->l_name];
    }

    return false;
});
