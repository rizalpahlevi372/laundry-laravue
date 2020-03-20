<?php

namespace App\Http\Controllers\API;

use App\Transaction;
use App\DetailTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'customer_id' => 'required',
            'detail' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $user = $request->user();
            $transaction = Transaction::create([
                'customer_id' => $request->customer_id,
                'user_id' => $request->user_id,
                'amount' => 0
            ]);
            foreach ($request->detail as $row) {
                if (!is_null($row['laundry_price'])) {
                    $subtotal = $row['laundry_price']['price'] * $row['qty'];
                    if ($row['laundry_price']['unit_type'] == 'Kilogram') {
                        $subtotal = $row['laundry_price']['price'] * ($row['qty'] / 1000);
                        DetailTransaction::create([
                            'transaction_id' => $transaction->id,
                            'laundry_price_id' => $row['laundry_price']['id'],
                            'laundry_type_id' => $row['laundry_price']['laundry_type_id'],
                            'qty' => $row['qty'],
                            'price' => $row['laundry_price']['price'],
                            'subtotal' => $subtotal
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
}
