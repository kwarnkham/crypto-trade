<?php

namespace App\Http\Requests;

use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return $this->agent != null && $this->agent->status != AgentStatus::RESTRICTED;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'agent' => Agent::current($this)
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                $from = User::where('code', $this->from)->first();
                if (
                    $this->amount >
                    $from->balance - $from->withdrawingAmount()
                )
                    $validator->errors()->add(
                        'transfer',
                        'User does not have enough balance'
                    );
            }
        ];
    }

    public function rules(): array
    {
        $agent = Agent::current($this);
        return [
            'from' => ['required', Rule::exists('users', 'code')->where('agent_id', $agent->id)],
            'to' => ['required', Rule::exists('users', 'code')->where('agent_id', $agent->id)],
            'amount' => ['required', 'numeric', 'gt:1'],
            'agent_transaction_id' => ['required', 'unique:extracts,agent_transaction_id']
        ];
    }
}
