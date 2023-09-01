<?php

namespace App\Models;

use App\Enums\AgentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['key'];

    public static function verify(Request $request)
    {
        $name = $request->header('x-agent');
        $key = $request->header('x-api-key');
        $ip = $request->ip();
        if (!$name || !$key || !$ip) {
            return 'Cannot find key and agent';
        }
        $agent = Agent::where('name', $name)->first();
        if (!$agent || !Hash::check($key, $agent->key) || $agent->status == AgentStatus::RESTRICTED->value) {
            return 'RESTRICTED agent or invalid key';
        }
        if ($agent->ip != $ip && $agent->ip != "*") {
            return 'Invalid IP';
        }
        return true;
    }

    public static function make($name, $ip = "*", $remark = null): array
    {
        $key = Str::random(64);
        $agent = Agent::create([
            'name' => $name,
            'ip' => $ip,
            'key' => bcrypt($key),
            'remark' => $remark,
            'status'=> AgentStatus::NORMAL->value
        ]);
        return [$agent, $key];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function current(Request $request)
    {
        return Agent::where('name', $request->header('x-agent'))->first();
    }

    public function resetKey()
    {
        $key = Str::random(64);
        $this->update(['key'=> bcrypt($key)]);
        return $key;
    }
}
