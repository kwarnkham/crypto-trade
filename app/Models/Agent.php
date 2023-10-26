<?php

namespace App\Models;

use App\Enums\AgentStatus;
use App\Traits\StatusFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Agent extends Model
{
    use HasFactory, StatusFilterable;

    protected $guarded = ['id'];
    protected $hidden = ['key'];

    protected $casts = [
        'key' => 'encrypted',
    ];

    public static function verify(Request $request)
    {
        $name = $request->header('x-agent');
        $jwt = $request->header('x-api-key');
        $ip = $request->ip();


        if (!$name || !$jwt || !$ip) {
            return 'Cannot find key and agent';
        }

        $agent = Agent::where(['name' => $name, 'status' => AgentStatus::NORMAL->value])->first();

        if (!$agent) {
            return 'No valid agent fournd';
        }

        if ($agent->ip != $ip && $agent->ip != "*") {
            Log::info("Request IP is " . $ip);
            Log::info("Whitelisted IP is" . $agent->ip);
            return 'Invalid IP';
        }

        try {
            $decoded = JWT::decode($jwt, new Key($agent->key, 'HS256'));
        } catch (\Throwable $th) {
            return 'Invalid JWT';
        }

        if ($agent->key != $decoded->key) {
            return 'Invalid Key';
        }
        return true;
    }

    public function jwtKey()
    {
        return JWT::encode(['key' => $this->key], $this->key, 'HS256', null, ['alg' => 'HS256', 'typ' => 'JWT']);
    }

    public static function make($name, $ip = "0.0.0.0", $remark = null): array
    {
        $key = Str::random(64);
        $agent = Agent::create([
            'name' => $name,
            'ip' => $ip,
            'key' => $key,
            'remark' => $remark,
            'status' => AgentStatus::NORMAL->value
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
        $this->update(['key' => $key]);
        return $key;
    }

    public static function cacheIp()
    {
        return Cache::put('allowed_origins', Agent::query()->get(['id', 'ip'])->toArray());
    }
}
