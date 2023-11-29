<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Enums\DepositStatus;
use App\Enums\AgentStatus;
use App\Enums\ResponseStatus;
use App\Models\Wallet;
use App\Models\Agent;

class StoreDepositRequest extends FormRequest
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
                    //let this only check the same amount deposit
                    if ($user->getActiveDeposit($this->amount) != null) {
                        $validator->errors()->add(
                            'deposit',
                            'User already has a deposit with same amount'
                        );
                        return;
                    }

                    //here we check if user alreay have 3 unfinished deposits
                    if ($user->deposits()->whereIn('status', [DepositStatus::PENDING->value, DepositStatus::CONFIRMED->value])->count() >= 3) {
                        $validator->errors()->add(
                            'deposit',
                            'User already has 3 unfinished deposits'
                        );
                        return;
                    }
                }

                // Check avaliable wallet exists
                $wallet = Wallet::findAvailable($this->amount);
                abort_if($wallet == null, ResponseStatus::BAD_REQUEST->value, 'There is no avaliable wallet to handle deposit.');
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
