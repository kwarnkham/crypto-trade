<?php

namespace App\Http\Requests;

use App\Enums\ResponseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UnstakeWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                $wallet = $this->wallet;
                $response =  $wallet->unfreezeBalance($this->amount, $this->type);

                if (($response->result ?? false) != true) abort(ResponseStatus::BAD_REQUEST->value, 'Tron network error');
            }
        ];
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:ENERGY,BANDWIDTH'],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'integer',
                'lte:' . ($this->type == 'ENERGY' ? $this->wallet->staked_for_energy : $this->wallet->staked_for_bandwidth)
            ]
        ];
    }
}
