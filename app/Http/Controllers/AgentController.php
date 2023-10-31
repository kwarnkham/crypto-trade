<?php

namespace App\Http\Controllers;

use App\Enums\AgentStatus;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'unique:agents,name']
        ]);

        [$agent, $key] = Agent::make($request->name);

        return response()->json(['agent' => $agent, 'key' => $key]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'required'],
            'name' => ['sometimes', 'required']
        ]);
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

    public function setCallback(Request $request)
    {
        $data = $request->validate([
            'deposit_callback' => ['sometimes', 'required', 'url'],
            'withdraw_callback' => ['sometimes', 'required', 'url'],
        ]);
        $agent = Agent::current($request);
        $agent->update($data);

        return response()->json([
            'agent' => $agent
        ]);
    }

    public function update(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'ip' => ['ip', 'required'],
            'name' => ['required', Rule::unique('agents', 'name')->ignoreModel($agent)],
            'remark' => ['']
        ]);

        $data['ip'] = $request->ip;

        $agent->update($data);

        return response()->json([
            'agent' => $agent
        ]);
    }
}
