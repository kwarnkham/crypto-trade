<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => $this->name,
            'trx' => $this->key,
            'staked_for_energy' => $this->status,
            'staked_for_bandwidth' => $this->ip,
            'energy' => $this->energy,
            'bandwidth' => $this->bandwidth,
            'activated_at' => $this->activated_at,
            'base58_check' => $this->base58_check,
        ];
    }
}
