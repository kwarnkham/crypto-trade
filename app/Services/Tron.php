<?php

namespace App\Services;

use App\Enums\ResponseStatus;
use App\Models\Wallet;
use App\Utility\Conversion;
use App\Utility\Formatter;
use Illuminate\Support\Facades\Http;
use App\Utility\Keccak;
use App\Utility\Utils;
use Elliptic\EC;
use InvalidArgumentException;
use kornrunner\Secp256k1;
use kornrunner\Signature\Signature;

class Tron
{
    const DIGITS = 10 ** 6;
    public static function signTransaction(array $transaction, Wallet $wallet, string $message = null): array
    {
        $privateKey = $wallet->private_key;

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
        if (!array_key_exists('signature', $signedTransaction) || !is_array($signedTransaction['signature'])) {
            abort(ResponseStatus::BAD_REQUEST->value, 'Transaction is not signed');
        }
        return Http::tron()->withBody(json_encode($signedTransaction))
            ->post("/wallet/broadcasttransaction")->object();
    }

    public static function sendUSDT(string $to, int $amount, Wallet $wallet)
    {
        $amount /= Tron::DIGITS;
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
            "owner_address" => "412A6B12B7C076E978F66BB97DEF94B7CA84A05432",
            "fee_limit" => 100 * Tron::DIGITS,
            "call_value" => 0,
        ])->json()['transaction'];

        $signed = static::signTransaction($transaction, $wallet);

        return static::broadcastTransaction($signed);
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
        return Http::tron()->post("/wallet/gettransactioninfobyid", [
            "value" => $txID,
        ])->object();
    }

    public static function getSolidityTransactionInfoById(string $txID)
    {
        return Http::tron()->post("/walletsolidity/gettransactioninfobyid", [
            "value" => $txID,
        ])->object();
    }

    public static function getSolidityTransactionById(string $txID)
    {
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
        return Http::tron()->post("/wallet/freezebalancev2", [
            'owner_address' => $ownerAddress,
            'resource' => $resource,
            'frozen_balance' => $frozenBalance,
            'visible' => true
        ])->json();
    }


    public static function withdrawExpireUnfreeze(string $ownerAddress): array
    {
        return Http::tron()->post("/wallet/withdrawexpireunfreeze", [
            'owner_address' => $ownerAddress,
            'visible' => true
        ])->json();
    }

    // /wallet/withdrawexpireunfreeze

    public static function getAccountResource(string $address)
    {
        return Http::tron2()->post('/wallet/getaccountresource', [
            'address' => $address,
            'visible' => true
        ])->body();
    }

    public static function getAccountInfoByAddress(string $address)
    {
        return Http::tron()->get("/v1/accounts/$address")->object();
    }

    public static function isActivated(string $address)
    {
        return count(static::getAccountInfoByAddress($address)->data) > 0;
    }

    public static function validateAddress(string $address)
    {
        return Http::tron()->post("/wallet/validateaddress", ["address" => $address])->object();
    }

    public static function unfreezeBalance(string $ownerAddress, string $resource, int $unfreezeBalance)
    {
        return Http::tron()->post("/wallet/unfreezebalancev2", [
            'owner_address' => $ownerAddress,
            'unfreeze_balance' => $unfreezeBalance,
            'resource' => $resource,
            'visible' => true
        ])->json();
    }


    public static function getTRC20TransactionInfoByAccountAddress(string $address, $options = null)
    {
        return Http::tron2()->get("/v1/accounts/$address/transactions/trc20", $options)->object();
    }

    public static function getTransactionInfoByAccountAddress(string $address, $options = null)
    {
        return Http::tron2()->get("/v1/accounts/$address/transactions", $options)->object();
    }

    public static function getTransactionInfoByContractAddress(string $contractAddress)
    {
        return Http::tron2()->get("/v1/contracts/$contractAddress/transactions")->object();
    }
}
