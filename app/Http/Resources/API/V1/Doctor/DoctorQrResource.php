<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorQrResource extends JsonResource
{
    /**
     * Transform the QR data into an array.
     *
     * Returns the doctor_id and the static TOTP secret key.
     * The frontend uses the secret key to generate 6-digit codes
     * locally via the standard TOTP algorithm instead of hitting
     * the server for a live OTP every time.
     *
     * @return array{doctor_id: string, secret_key: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'doctor_id' => $this->resource['doctor_id'],
            'secret_key' => $this->resource['secret_key'],
        ];
    }
}
