<?php

namespace App\Http\Controllers\API;

use App\Transaction;
use App\DetailTransaction;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
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
            $transaction = new Transaction();
            $transaction->customer_id = $request->customer_id['id'];
            $transaction->user_id = $user->id;
            $transaction->amount = 0;
            $transaction->save();

            $amount = 0;
            foreach ($request->detail as $row) {
                if (!is_null($row['laundry_price'])) {
                    $subtotal = $row['laundry_price']['price'] * $row['qty'];
                    if ($row['laundry_price']['unit_type'] == 'Kilogram') {
                        $subtotal = ($row['laundry_price']['price'] * $row['qty']) / 1000;
                    }
                    $start_date = Carbon::now();
                    $end_at = Carbon::now()->addHours($row['laundry_price']['service']);
                    if ($row['laundry_price']['service_type'] == 'Hari') {
                        $end_date = Carbon::now()->addDays($row['laundry_price']['service']);
                    }
                    DetailTransaction::create([
                        'transaction_id' => $transaction->id,
                        'laundry_price_id' => $row['laundry_price']['id'],
                        'laundry_type_id' => $row['laundry_price']['laundry_type_id'],
                        'start_date' => $start_date->format('Y-m-d H:i:s'),
                        'end_date' => $end_date->format('Y-m-d H:i:s'),
                        'qty' => $row['qty'],
                        'price' => $row['laundry_price']['price'],
                        'subtotal' => $subtotal
                    ]);
                    $amount += $subtotal;
                }
            }
            $transaction->update(['amount' => $amount]);
            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
    public function edit($id)
    {
        $transaction = Transaction::with(['customer', 'payment', 'detail', 'detail.product'])->find($id);
        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    public function completeItem(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:detail_transactions,id'
        ]);

        $transaction = DetailTransaction::with(['transaction.customer'])->find($request->id);
        $transaction->update(['status' => 1]);
        $transaction->transaction->customer()->update(
            ['point' => $transaction->transaction->customer->point + 1]
        );
        return response()->json(['status' => 'success']);
    }
}
