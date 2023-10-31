<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\AgentResource;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'name' => $this->name,
            'balance' => $this->balance,
            'agent_id' => $this->agent_id,
            'agent' => new AgentResource($this->agent),
        ];
    }
}
