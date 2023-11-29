<?php

namespace App\Http\Controllers;

use App\Enums\AgentStatus;
use App\Http\Requests\FilterAgentRequest;
use App\Http\Requests\SetCallbackAgentRequest;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Models\Agent;

class AgentController extends Controller
{
    public function store(StoreAgentRequest $request)
    {
        [$agent, $key] = Agent::make($request->name);

        return response()->json(['agent' => $agent, 'key' => $key]);
    }

    public function index(FilterAgentRequest $request)
    {
        $filters = $request->all();
        $query = Agent::query()->filter($filters);
        return response()->json($query->paginate($request->per_page ?? 10));
    }

    public function toggleStatus(Agent $agent)
    {
        $agent->update(['status' => AgentStatus::NORMAL->value == $agent->status ? AgentStatus::RESTRICTED->value : AgentStatus::NORMAL->value]);
        return response()->json([
            'agent' => $agent
        ]);
    }

    public function resetKey(Agent $agent)
    {
        return response()->json([
            'key' => $agent->resetKey()
        ]);
    }

    public function setCallback(SetCallbackAgentRequest $request)
    {
        $data = $request->all();
        $agent = Agent::current($request);
        $agent->update($data);

        return response()->json([
            'agent' => $agent
        ]);
    }

    public function update(UpdateAgentRequest $request, Agent $agent)
    {
        $data = $request->all();

        $agent->update($data);

        return response()->json([
            'agent' => $agent
        ]);
    }
}
