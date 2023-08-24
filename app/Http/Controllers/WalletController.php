<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\Tron;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function store(Request $request)
    {
        $wallet = Wallet::generate();

        return response()->json(['wallet' => $wallet]);
    }

    public function index(Request $request)
    {
        $query = Wallet::query();

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public  function activate(Wallet $wallet)
    {
        if (Tron::isActivated('TBnGvsr6VV6DdjD6qmKka8ei5RXaomcoKw')) $wallet->update(['activated_at' => now()]);
        return response()->json(['wallet' => $wallet]);
    }
}
