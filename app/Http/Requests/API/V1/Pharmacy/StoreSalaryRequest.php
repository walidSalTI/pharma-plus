<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'string', 'exists:users,id'],
            'recipient_name' => ['required_if:user_id,null', 'nullable', 'string', 'max:255'],
            'base_amount' => ['required_if:user_id,null', 'nullable', 'numeric', 'min:0.01'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'salary_period' => ['required', 'string', 'max:10'],
            'paid_at' => ['required', 'date'],
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
            'user_id' => ['description' => 'UUID of the user receiving the salary (nullable for trainees/temporary staff)'],
            'recipient_name' => ['description' => 'Name of the salary recipient (auto-filled from user if user_id provided)'],
            'base_amount' => ['description' => 'Base salary amount (auto from pivot if user_id provided; required manually if user_id null)'],
            'bonus' => ['description' => 'Bonus / incentives (default: 0)'],
            'deductions' => ['description' => 'Deductions / penalties (default: 0)'],
            'salary_period' => ['description' => 'Salary period identifier (e.g., 2026-07)'],
            'paid_at' => ['description' => 'Date the salary was paid (Y-m-d)'],
            'payment_method' => ['description' => 'Payment method: cash, card, bank_transfer, or apps (default: cash)'],
            'notes' => ['description' => 'Notes (e.g., reason for bonus or deduction)'],
        ];
    }
}
