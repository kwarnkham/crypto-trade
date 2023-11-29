<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Enums\DepositStatus;
use App\Enums\AgentStatus;
use App\Models\Wallet;
use App\Models\Agent;

class StoreDepositRequest extends FormRequest
{
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
                    //let this only check the same amount deposit
                    if ($user->getActiveDeposit($this->amount) != null) {
                        $validator->errors()->add(
                            'pending_deposit',
                            'User already has a deposit with same amount'
                        );
                    }

                    //here we check if user alreay have 3 unfinished deposits
                    if ($user->deposits()->whereIn('status', [DepositStatus::PENDING->value, DepositStatus::CONFIRMED->value])->count() >= 3) {
                        $validator->errors()->add(
                            'deposit_count',
                            'User already has 3 unfinished deposits'
                        );
                    }
                }

                // Check avaliable wallet exists
                $wallet = Wallet::findAvailable($this->amount);
                if ($wallet == null) {
                    $validator->errors()->add(
                        'wallet',
                        'There is no avaliable wallet to handle deposit.'
                    );
                }
                $this->merge(['wallet' => $wallet]);
            }
        ];
    }

    public function rules(): array
    {
        return [
            'code' => ['required'],
            'name' => ['required'],
            'amount' => ['required', 'numeric', 'gte:0.000001'],
            'agent_transaction_id' => ['required', 'unique:extracts,agent_transaction_id'],
        ];
    }
}
