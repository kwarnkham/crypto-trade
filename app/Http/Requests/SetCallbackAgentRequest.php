<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetCallbackAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deposit_callback' => ['sometimes', 'required', 'url'],
            'withdraw_callback' => ['sometimes', 'required', 'url'],
            'extract_callback' => ['sometimes', 'required', 'url'],
        ];
    }
}
