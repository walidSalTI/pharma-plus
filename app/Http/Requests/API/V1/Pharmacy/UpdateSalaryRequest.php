<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'string', 'exists:users,id'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'base_amount' => ['nullable', 'numeric', 'min:0.01'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'salary_period' => ['nullable', 'string', 'max:10'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:cash,card,bank_transfer,apps'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('user_id') && ! $this->filled('recipient_name')) {
                $user = User::find($this->user_id);
                if ($user) {
                    $this->merge([
                        'recipient_name' => trim(($user->f_name ?? '').' '.($user->l_name ?? '')),
                    ]);
                }
            }
        });
    }

    public function bodyParameters(): array
    {
        return [
            'user_id' => ['description' => 'Updated user UUID (nullable)'],
            'recipient_name' => ['description' => 'Updated recipient name'],
            'base_amount' => ['description' => 'Updated base salary amount (auto from pivot if user_id changes)'],
            'bonus' => ['description' => 'Updated bonus / incentives'],
            'deductions' => ['description' => 'Updated deductions / penalties'],
            'salary_period' => ['description' => 'Updated salary period (e.g., 2026-07)'],
            'paid_at' => ['description' => 'Updated payment date (Y-m-d)'],
            'payment_method' => ['description' => 'Updated payment method'],
            'notes' => ['description' => 'Updated notes'],
        ];
    }
}
