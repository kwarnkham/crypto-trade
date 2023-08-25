<?php

namespace App\Services;

use App\Utility\Conversion;
use App\Utility\Formatter;
use Illuminate\Support\Facades\Http;
use App\Utility\Keccak;
use App\Utility\Utils;
use Elliptic\EC;
use Exception;
use InvalidArgumentException;
use kornrunner\Secp256k1;
use kornrunner\Signature\Signature;

class Tron
{

    public static function getHeader()
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'TRON-PRO-API-KEY' => 'cad0a351-7759-4133-90c5-9e733846c896'
        ];
    }

    // public  static function foo()
    // {
    //     $transaction = static::signTransaction(static::freezeBalance('TDqVegmPEb3juuAV4vZYNS5AWUbvTUFH3y', 'ENERGY', 1000000));

    //     return static::broadcastTransaction($transaction);
    // }

    /**
     * Sign the transaction, the api has the risk of leaking the private key,
     * please make sure to call the api in a secure environment
     *
     */
    public static function signTransaction(array $transaction, string $message = null): array
    {
        $privateKey = '40f1a63332a869f3c1ab4c07c1dba94d0fbc019dc88ef796bb1b147c0e15795e';

        if (isset($transaction['Error']))
            throw new Exception($transaction['Error']);


        if (isset($transaction['signature'])) {
            throw new Exception('Transaction is already signed');
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
            throw new Exception('Transaction is not signed');
        }
        // https://developers.tron.network/reference/account-info-by-address
        return Http::withHeaders(static::getHeader())->withBody(json_encode($signedTransaction))
            ->post(config('app')['tron_api_url'] . "/wallet/broadcasttransaction")->throw()->object();
    }

    public static function sendUSDT(string $to /* hex address */, int $amount)
    {
        // https://developers.tron.network/reference/triggersmartcontract

        $toFormat = Formatter::toAddressFormat($to);

        try {
            $amount = Utils::toMinUnitByDecimals($amount, 6);
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage());
        }
        $numberFormat = Formatter::toIntegerFormat($amount);

        $transaction = Http::withHeaders(static::getHeader())->post(config('app')['tron_api_url'] . "/wallet/triggersmartcontract", [
            "contract_address" => config('app')['trc20_address_hex'],
            "function_selector" => "transfer(address,uint256)",
            "parameter" => "{$toFormat}{$numberFormat}",
            "owner_address" => "412A6B12B7C076E978F66BB97DEF94B7CA84A05432",
            "fee_limit" => 100000000,
            "call_value" => 0,
        ])->throw()->json()['transaction'];

        $signed = static::signTransaction($transaction);

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
        //https://developers.tron.network/reference/transaction-info-by-id
        return Http::withHeaders(static::getHeader())->post(config('app')['tron_api_url'] . "/wallet/gettransactioninfobyid", [
            "value" => $txID,
        ])->throw()->object();
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
        // https://developers.tron.network/reference/freezebalancev2-1
        return Http::withHeaders(static::getHeader())->post(config('app')['tron_api_url'] . "/wallet/freezebalancev2", [
            'owner_address' => $ownerAddress,
            'resource' => $resource,
            'frozen_balance' => $frozenBalance,
            'visible' => true
        ])->throw()->json();
    }

    public static function getAccountInfoByAddress(string $address)
    {
        // https://developers.tron.network/reference/account-info-by-address
        return Http::withHeaders(static::getHeader())->get(config('app')['tron_api_url'] . "/v1/accounts/$address")->throw()->object();
    }

    public static function isActivated(string $address)
    {
        return count(static::getAccountInfoByAddress($address)->data) > 0;
    }

    public static function validateAddress(string $address)
    {
        // https://developers.tron.network/reference/walletvalidateaddress
        return Http::withHeaders(static::getHeader())->post(config('app')['tron_api_url'] . "/wallet/validateaddress", ["address" => $address])->throw()->object();
    }

    // public static function getTransactionInfoByAccountAddress(string $address)
    // {
    //     // https://developers.tron.network/reference/transaction-information-by-account-address
    //     return Http::withHeaders(static::getHeader())->get(config('app')['tron_api_url']."/v1/accounts/$address/transactions")->throw()->object();
    // }

    public static function getTRC20TransactionInfoByAccountAddress(string $address, $options = null)
    {
        // https://developers.tron.network/reference/trc20-transaction-information-by-account-address
        return Http::withHeaders(static::getHeader())->get(config('app')['tron_api_url'] . "/v1/accounts/$address/transactions/trc20", $options)->throw()->object();
    }

    public static function getTransactionInfoByContractAddress(string $contractAddress)
    {
        // https://developers.tron.network/reference/testinput
        return Http::withHeaders(static::getHeader())->get(config('app')['tron_api_url'] . "/v1/contracts/$contractAddress/transactions")->throw()->object();
    }

    // public static function getEventsByTransactionId(string $transactionID)
    // {
    //     // https://developers.tron.network/reference/events-by-transaction-id
    //     return Http::withHeaders(static::getHeader())->get(config('app')['tron_api_url']."/v1/transactions/$transactionID/events")->throw()->object();
    // }

    // public static function getEventsByContractAddress(string $contractAddress)
    // {
    //     //https://developers.tron.network/reference/events-by-contract-address
    //     return Http::withHeaders(static::getHeader())->get(config('app')['tron_api_url']."/v1/contracts/$contractAddress/events")->throw()->object();
    // }

    // public static function GetTransactionById(string $transactionId)
    // {
    //     //https://developers.tron.network/reference/walletgettransactionbyid
    //     return Http::withHeaders(static::getHeader())->post(config('app')['tron_api_url']."/wallet/gettransactionbyid", [
    //         "value" => $transactionId,
    //     ])->throw()->object();
    // }
}
