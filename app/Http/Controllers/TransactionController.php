<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Withdraw;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query()->latest('id');
        return response()->json($query->paginate($request->per_page ?? 10));
    }

    public function userTransactions(Request $request)
    {
        $filters = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date']
        ]);

        $deposits = Deposit::query()->where([
            ['updated_at', '>=', $filters['from']],
            ['updated_at', '<=', $filters['to']],
        ])->latest('updated_at')->get();

        $withdraws = Withdraw::query()->where([
            ['updated_at', '>=', $filters['from']],
            ['updated_at', '<=', $filters['to']],
        ])->latest('updated_at')->get();

        return response()->json([
            'withdraws' => $withdraws,
            'deposits' => $deposits
        ]);
    }
}
