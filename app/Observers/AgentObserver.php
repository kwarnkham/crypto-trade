<?php

namespace App\Observers;

use App\Models\Agent;
use Illuminate\Support\Facades\Cache;

class AgentObserver
{
    /**
     * Handle the Agent "created" event.
     */
    public function created(Agent $agent): void
    {
        $allowedOrigins = (array)Cache::get('allowed_origins', []);
        array_push($allowedOrigins, [
            'id' => $agent->id,
            'ip' => $agent->ip
        ]);
        Cache::put('allowed_origins', $allowedOrigins);
    }

    /**
     * Handle the Agent "updated" event.
     */
    public function updated(Agent $agent): void
    {
        $allowedOrigins = (array)Cache::get('allowed_origins', []);
        if (count($allowedOrigins) < 1) return;
        foreach ($allowedOrigins as &$value) {
            if ($value['id'] == $agent->id) {
                $value['ip'] = $agent->ip;
                break;
            }
        }
        Cache::put('allowed_origins', $allowedOrigins);
    }

    /**
     * Handle the Agent "deleted" event.
     */
    public function deleted(Agent $agent): void
    {
        $allowedOrigins = (array)Cache::get('allowed_origins', []);
        if (count($allowedOrigins) < 1) return;
        Cache::put('allowed_origins', array_filter($allowedOrigins, function ($value) use ($agent) {
            return $value['id'] != $agent->id;
        }));
    }

    /**
     * Handle the Agent "restored" event.
     */
    public function restored(Agent $agent): void
    {
        $allowedOrigins = (array)Cache::get('allowed_origins', []);
        array_push($allowedOrigins, [
            'id' => $agent->id,
            'ip' => $agent->ip
        ]);
        Cache::put('allowed_origins', $allowedOrigins);
    }

    /**
     * Handle the Agent "force deleted" event.
     */
    public function forceDeleted(Agent $agent): void
    {
        $allowedOrigins = (array)Cache::get('allowed_origins', []);
        if (count($allowedOrigins) < 1) return;
        Cache::put('allowed_origins', array_filter($allowedOrigins, function ($value) use ($agent) {
            return $value['id'] != $agent->id;
        }));
    }
}
