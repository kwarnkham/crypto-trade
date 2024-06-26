<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\Wallet;

class StoreWithdrawRequest extends FormRequest
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
                $user = $this->agent->users()->where('code', $this->code)->first();

                if ($user != null) {
                    // Check wallet address is valid
                    if (!(Str::startsWith($this->to, 'T') && Wallet::validate($this->to))) {
                        $validator->errors()->add(
                            'withdraw',
                            'Wallet is invalid'
                        );
                        return;
                    }

                }
            }
        ];
    }

    public function rules(): array
    {
        $fee = 1;
        return [
            'code' => ['required', Rule::exists('users', 'code')->where('agent_id', $this->agent->id)],
            'amount' => ['required', 'numeric', 'gt:' . $fee],
            'to' => ['required', 'string', 'unique:wallets,base58_check'],
            'agent_transaction_id' => ['required', 'unique:withdraws,agent_transaction_id']
        ];
    }

    public function messages(): array
    {
        return [
            'to.unique' => 'The wallet is invalid. Please check again.'
        ];
    }
}
