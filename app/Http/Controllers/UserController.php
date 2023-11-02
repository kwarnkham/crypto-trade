<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function find(Request $request, User $user)
    {
        $agent = Agent::current($request);
        abort_unless($user->agent_id == $agent->id, ResponseStatus::NOT_FOUND->value);
        return response()->json(['user' => $user]);
    }

    public function index(Request $request)
    {
        $agent = Agent::current($request);
        $query = User::query()->where('agent_id', $agent->id);
        return response()->json(['data' => $query->paginate($request->per_page ?? 10)]);
    }
}
