<?php

namespace App\Http\Controllers;

use App\Models\BalanceLog;
use Illuminate\Http\Request;

class BalanceLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'user_id' => ['sometimes', 'integer']
        ]);

        $query = BalanceLog::query()->filter($filters);

        return response()->json([
            'data' => $query->paginate($request->per_page ?? 10)
        ]);
    }
}
