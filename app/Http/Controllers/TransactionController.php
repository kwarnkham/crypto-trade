<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Extract;
use App\Models\Transaction;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'token_address' => ['sometimes', 'required'],
            'transactionable_type' => ['sometimes', 'required'],
        ]);
        $query = Transaction::query()->with(['transactionable' => function (MorphTo $morphTo) {
            $morphTo->morphWith([
                Deposit::class => ['user'],
                Withdraw::class => ['user'],
                Extract::class
            ]);
        }])->filter($filters)->latest('id');
        return response()->json($query->paginate($request->per_page ?? 10));
    }

    public function userTransactions(Request $request)
    {
        $filters = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date']
        ]);

        $deposits = Deposit::query()->with(['transaction'])->where([
            ['updated_at', '>=', $filters['from']],
            ['updated_at', '<=', $filters['to']],
        ])->latest('updated_at')->get();

        $withdraws = Withdraw::query()->with(['transaction'])->where([
            ['updated_at', '>=', $filters['from']],
            ['updated_at', '<=', $filters['to']],
        ])->latest('updated_at')->get();

        return response()->json([
            'withdraws' => $withdraws,
            'deposits' => $deposits
        ]);
    }
}
