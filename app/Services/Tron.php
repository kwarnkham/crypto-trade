<?php

namespace App\Services;

use App\Enums\ResponseStatus;
use App\Utility\Conversion;
use App\Utility\Formatter;
use Illuminate\Support\Facades\Http;
use App\Utility\Keccak;
use App\Utility\Utils;
use Elliptic\EC;
use InvalidArgumentException;
use kornrunner\Secp256k1;
use kornrunner\Signature\Signature;
use Cache;

class Tron
{
    const DIGITS = 10 ** 6;
    public static function signTransaction(array $transaction, string $privateKey, string $message = null): array
    {
        if (isset($transaction['Error']))
            abort(ResponseStatus::BAD_REQUEST->value, $transaction['Error']);


        if (isset($transaction['signature'])) {
            abort(ResponseStatus::BAD_REQUEST->value, 'Transaction is already signed');
        }

        if (!is_null($message)) {
            $transaction['raw_data']['data'] = bin2hex($message);
        }

        $secp = new Secp256k1();
        /** @var Signature $sign */
        $sign = $secp->sign($transaction['txID'], $privateKey, ['canonical' => false]);

        $signature =  $sign->toHex() . bin2hex(implode('', array_map('chr', [$sign->getRecoveryParam()])));

        $transaction['signature'] = [$signature];

        return $transaction;
    }

    public static function broadcastTransaction(array $signedTransaction)
    {
        static::cacheStore('broadcastTransaction');

        if (!array_key_exists('signature', $signedTransaction) || !is_array($signedTransaction['signature'])) {
            abort(ResponseStatus::BAD_REQUEST->value, 'Transaction is not signed');
        }
        return Http::tron()->withBody(json_encode($signedTransaction))
            ->post("/wallet/broadcasttransaction")->object();
    }

    public static function sendUSDT(string $to, float $amount, string $privateKey, string $from)
    {
        static::cacheStore('sendUSDT');

        $toFormat = Formatter::toAddressFormat(Conversion::base58check2HexString($to));

        try {
            $amount = Utils::toMinUnitByDecimals($amount, 6);
        } catch (InvalidArgumentException $e) {
            abort(ResponseStatus::BAD_REQUEST->value, $e->getMessage());
        }
        $numberFormat = Formatter::toIntegerFormat($amount);

        $transaction = Http::tron()->post("/wallet/triggersmartcontract", [
            "contract_address" => Conversion::base58check2HexString(config('app')['trc20_address']),
            "function_selector" => "transfer(address,uint256)",
            "parameter" => "{$toFormat}{$numberFormat}",
            "owner_address" => Conversion::base58check2HexString($from),
            "fee_limit" => config('app')['min_trx_for_transaction'] * Tron::DIGITS,
            "call_value" => 0,
        ])->json()['transaction'];

        $signed = static::signTransaction($transaction, $privateKey);

        return static::broadcastTransaction($signed);
    }

    public static function sendTRX(string $to, float $amount, string $privateKey, string $from)
    {
        $transaction = static::createTransaction($from, $to, $amount);

        $signed = static::signTransaction($transaction, $privateKey);

        return static::broadcastTransaction($signed);
    }

    public static function createTransaction(string $ownerAddress, string $toAddress, float $amount)
    {
        static::cacheStore('createTransaction');

        return Http::tron()->post("/wallet/createtransaction", [
            'owner_address' => $ownerAddress,
            'to_address' => $toAddress,
            'amount' => $amount * Tron::DIGITS,
            'visible' => true
        ])->json();
    }

    public static function getTransactionError(string $txID)
    {
        $txInfo = static::getTransactionInfoById($txID);
        return [
            'contractResult' => hex2bin($txInfo->contractResult[0]),
            'txInfo' => get_object_vars($txInfo)
        ];
    }

    public static function getTransactionInfoById(string $txID)
    {
        static::cacheStore('getTransactionInfoById');

        return Http::tron()->post("/wallet/gettransactioninfobyid", [
            "value" => $txID,
        ])->object();
    }

    public static function getSolidityTransactionInfoById(string $txID)
    {
        static::cacheStore('getSolidityTransactionInfoById');

        return Http::tron()->post("/walletsolidity/gettransactioninfobyid", [
            "value" => $txID,
        ])->object();
    }

    public static function getSolidityTransactionById(string $txID)
    {
        static::cacheStore('getSolidityTransactionById');

        return Http::tron()->post("/walletsolidity/gettransactionbyid", [
            "value" => $txID,
        ])->object();
    }

    public static function generateAddressLocally()
    {
        $ec = new EC('secp256k1');
        $key = $ec->genKeyPair();
        $private = $key->getPrivate("hex");
        $public = $key->getPublic(false, "hex");
        $hash  = Keccak::hash(hex2bin(substr($public, 2)), 256);
        $hexAddress = "41" . substr($hash, -40);

        return [
            "private_key" => $private,
            "public_key" => $public,
            "hex_address" => $hexAddress,
            "base58_check" => Conversion::hexString2Base58check($hexAddress),
            "base64" => Conversion::hexString2Base64($hexAddress)
        ];
    }

    public static function freezeBalance(string $ownerAddress, string $resource, int $frozenBalance): array
    {
        static::cacheStore('freezeBalance');

        return Http::tron()->post("/wallet/freezebalancev2", [
            'owner_address' => $ownerAddress,
            'resource' => $resource,
            'frozen_balance' => $frozenBalance,
            'visible' => true
        ])->json();
    }


    public static function withdrawExpireUnfreeze(string $ownerAddress): array
    {
        static::cacheStore('withdrawExpireUnfreeze');

        return Http::tron()->post("/wallet/withdrawexpireunfreeze", [
            'owner_address' => $ownerAddress,
            'visible' => true
        ])->json();
    }

    public static function cancelAllUnfreezeV2(string $ownerAddress): array
    {
        static::cacheStore('cancelAllUnfreezeV2');

        return Http::tron()->post("/wallet/cancelallunfreezev2", [
            'owner_address' => $ownerAddress,
            'visible' => true
        ])->json();
    }

    public static function getAccountResource(string $address)
    {

        static::cacheStore('getAccountResource');

        return Http::tron2()->post('/wallet/getaccountresource', [
            'address' => $address,
            'visible' => true
        ])->json();
    }

    public static function getAccountInfoByAddress(string $address)
    {
        static::cacheStore("getAccountInfoByAddress");

        return Http::tron2()->get("/v1/accounts/$address")->object();
    }

    public static function isActivated(string $address)
    {
        return count(static::getAccountInfoByAddress($address)->data) > 0;
    }

    public static function validateAddress(string $address)
    {
        static::cacheStore('validateAddress');
        return Http::tron()->post("/wallet/validateaddress", ["address" => $address])->object();
    }

    public static function unfreezeBalance(string $ownerAddress, string $resource, int $unfreezeBalance)
    {
        static::cacheStore('unfreezeBalance');
        return Http::tron()->post("/wallet/unfreezebalancev2", [
            'owner_address' => $ownerAddress,
            'unfreeze_balance' => $unfreezeBalance,
            'resource' => $resource,
            'visible' => true
        ])->json();
    }


    public static function getTRC20TransactionInfoByAccountAddress(string $address, $options = null)
    {
        static::cacheStore('getTRC20TransactionInfoByAccountAddress');
        return Http::tron2()->get("/v1/accounts/$address/transactions/trc20", $options)->object();
    }

    public static function getTransactionInfoByAccountAddress(string $address, $options = null)
    {
        static::cacheStore('getTransactionInfoByAccountAddress');
        return Http::tron2()->get("/v1/accounts/$address/transactions", $options)->object();
    }

    public static function getTransactionInfoByContractAddress(string $contractAddress)
    {
        static::cacheStore('getTransactionInfoByContractAddress');
        return Http::tron2()->get("/v1/contracts/$contractAddress/transactions")->object();
    }

    public static function cacheStore(string $api_function) {
        $records =  (array)json_decode((string) Cache::get('api-records'));

        if (array_key_exists($api_function, $records)) {
            $records[$api_function]++;
        }else{
            $records[$api_function] = 1;
        }

        Cache::forever('api-records', json_encode( $records));
    }
}
