<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\Admin
 *
 * @property int $id
 * @property string $name
 * @property string $password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin query()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Admin whereUpdatedAt($value)
 */
	class Admin extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Agent
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string|null $remark
 * @property int $status
 * @property string $ip
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|Agent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Agent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Agent query()
 * @method static \Illuminate\Database\Eloquent\Builder|Agent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Agent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Agent whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Agent whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Agent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Agent whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Agent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Agent whereUpdatedAt($value)
 */
	class Agent extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Deposit
 *
 * @property int $id
 * @property int $user_id
 * @property int $wallet_id
 * @property int|null $transaction_id
 * @property int $amount
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereWalletId($value)
 */
	class Deposit extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Transaction
 *
 * @property int $id
 * @property string $from
 * @property string $to
 * @property string $transaction_id
 * @property string $token_address
 * @property string $block_timestamp
 * @property int $value
 * @property string $type
 * @property int|null $fee
 * @property mixed|null $receipt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereBlockTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereReceipt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTokenAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereValue($value)
 */
	class Transaction extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $balance
 * @property int $agent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Agent $agent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Deposit> $deposits
 * @property-read int|null $deposits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Withdraw> $withdraws
 * @property-read int|null $withdraws_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Wallet
 *
 * @property int $id
 * @property int $balance
 * @property string|null $activated_at
 * @property string $base58_check
 * @property string $public_key
 * @property string $hex_address
 * @property string $base64
 * @property mixed $private_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Deposit> $deposits
 * @property-read int|null $deposits_count
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet query()
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereActivatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereBase58Check($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereBase64($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereHexAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet wherePrivateKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Wallet whereUpdatedAt($value)
 */
	class Wallet extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Withdraw
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $from
 * @property string $to
 * @property float $amount
 * @property float|null $fee
 * @property int $status
 * @property int|null $transaction_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw query()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereUserId($value)
 */
	class Withdraw extends \Eloquent {}
}

