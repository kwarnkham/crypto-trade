<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes'],
            'wallet_id' => ['sometimes'],
        ];
    }
}
