<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agent = $this->agent;
        return [
            'ip' => ['ip', 'required'],
            'name' => ['required', Rule::unique('agents', 'name')->ignoreModel($agent)],
            'remark' => [''],
            'aes_key' => ['sometimes', 'required']
        ];
    }
}
