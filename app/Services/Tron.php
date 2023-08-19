<?php

namespace App\Services;

use App\Utility\Conversion;
use Illuminate\Support\Facades\Http;
use App\Utility\Keccak;
use Elliptic\EC;

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

    public static function getAccountInfoByAddress(string $address)
    {
        // https://developers.tron.network/reference/account-info-by-address
        return Http::withHeaders(static::getHeader())->get("https://api.shasta.trongrid.io/v1/accounts/$address")->throw()->object();
    }

    public static function isActivated(string $address)
    {
        return count(static::getAccountInfoByAddress($address)->data) > 0;
    }

    public static function validateAddress(string $address)
    {
        // https://developers.tron.network/reference/walletvalidateaddress
        return Http::withHeaders(static::getHeader())->post("https://api.shasta.trongrid.io/wallet/validateaddress", ["address" => $address])->throw()->object();
    }

    // public static function getTransactionInfoByAccountAddress(string $address)
    // {
    //     // https://developers.tron.network/reference/transaction-information-by-account-address
    //     return Http::withHeaders(static::getHeader())->get("https://api.shasta.trongrid.io/v1/accounts/$address/transactions")->throw()->object();
    // }

    public static function getTRC20TransactionInfoByAccountAddress(string $address, $options = null)
    {
        // https://developers.tron.network/reference/trc20-transaction-information-by-account-address
        return Http::withHeaders(static::getHeader())->get("https://api.shasta.trongrid.io/v1/accounts/$address/transactions/trc20", $options)->throw()->object();
    }

    public static function getTransactionInfoByContractAddress(string $contractAddress)
    {
        // https://developers.tron.network/reference/testinput
        return Http::withHeaders(static::getHeader())->get("https://api.shasta.trongrid.io/v1/contracts/$contractAddress/transactions")->throw()->object();
    }

    // public static function getEventsByTransactionId(string $transactionID)
    // {
    //     // https://developers.tron.network/reference/events-by-transaction-id
    //     return Http::withHeaders(static::getHeader())->get("https://api.shasta.trongrid.io/v1/transactions/$transactionID/events")->throw()->object();
    // }

    // public static function getEventsByContractAddress(string $contractAddress)
    // {
    //     //https://developers.tron.network/reference/events-by-contract-address
    //     return Http::withHeaders(static::getHeader())->get("https://api.shasta.trongrid.io/v1/contracts/$contractAddress/events")->throw()->object();
    // }

    // public static function GetTransactionById(string $transactionId)
    // {
    //     //https://developers.tron.network/reference/walletgettransactionbyid
    //     return Http::withHeaders(static::getHeader())->post("https://api.shasta.trongrid.io/wallet/gettransactionbyid", [
    //         "value" => $transactionId,
    //     ])->throw()->object();
    // }

    public static function GetTransactionInfoById(string $transactionId)
    {
        //https://developers.tron.network/reference/transaction-info-by-id
        return Http::withHeaders(static::getHeader())->post("https://api.shasta.trongrid.io/wallet/gettransactioninfobyid", [
            "value" => $transactionId,
        ])->throw()->object();
    }
}
