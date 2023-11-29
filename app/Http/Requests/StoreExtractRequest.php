<?php

namespace App\Http\Requests;

use App\Enums\AgentStatus;
use App\Enums\ExtractType;
use App\Models\Agent;
use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Str;

class StoreExtractRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        $agent = Agent::current($this);
        return$agent != null && $agent->status != AgentStatus::RESTRICTED;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $wallet = Wallet::find($this->wallet_id);

                if ($this->to == $wallet->base58_check){
                    $validator->errors()->add(
                        'extract',
                        'Please choose different wallet to extract.'
                    );
                    return;
                }

                // Check wallet address is valid
                if (!(Str::startsWith($this->to, 'T') && Wallet::validate($this->to))) {
                    $validator->errors()->add(
                        'extract',
                        'Wallet is invalid'
                    );
                    return;
                }

                if ($this->type == ExtractType::USDT->value){
                    if ($this->amount > $wallet->balance) {
                        $validator->errors()->add(
                            'extract',
                            'Not enough USDT'
                        );
                        return;
                    }
                } else if ($this->type == ExtractType::TRX->value){
                    if ($this->amount > $wallet->trx) {
                        $validator->errors()->add(
                            'extract',
                            'Not enough TRX'
                        );
                        return;
                    }
                }

            }
        ];
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gte:0.000001'],
            'type' => ['required', Rule::in(ExtractType::toArray())],
            'to' => ['required', 'string'],
            'wallet_id' => ['required', Rule::exists('wallets', 'id')],
            'agent_transaction_id' => ['required', 'unique:extracts,agent_transaction_id']
        ];
    }
}
