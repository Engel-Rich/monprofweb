<?php

namespace App\Http\Controllers;

use App\DTO\CreateTransactionDto;
use App\DTO\TransactionPostDto;
use App\DTO\TransactionUpdateDto;
use App\Models\Transaction;
use App\DTO\MundiPayRequestDTO;
use App\Enums\TransactionType;
use App\Http\Requests\PaymentCallbackRequest;
use App\Jobs\ProcessWebhook;
// use App\Models\Paiements;
// use App\Models\User;
use App\Services\Payments\PaymentFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //code...
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public static function store(TransactionPostDto $request)
    {

        try {
            $mundiPayRequestDTO = new MundiPayRequestDTO([
                'amount' => $request->amount,
                'subscription_id' => $request->subscription_id,
                'country_code' => "237",
                'phone_number' => $request->phone_number,
            ]);
            $transaction = Transaction::create($request->toArray());  
            $reference = Str::replaceEnd('MPP-', '',$transaction->reference);           
            $createTransactionRequest =  CreateTransactionDto::fromArray([
                "userId"=>$request->user_id,
                'type'=>TransactionType::DEPOSIT, 
                'sense'=> $request->sens,
                'amount'  => $request->amount,     
                'phoneNumber'=> $request->phone_number,      
                "countryCode"=> '237',
                'reference'=> $reference, //$transaction->reference,                                 
            ]);                        
           try {
             $strategy = PaymentFactory::make('CAMPAY');
            $response = $strategy->processPayment($createTransactionRequest);
            $transaction->update([
                'transaction_id'=>$response->transactionId                
            ]);
           } catch (\Throwable $th) {
            Log::error($th->getMessage());
           }
            $transaction->refresh();        
            return $transaction;
        } catch (\Throwable $th) {
            Log::error("Payment : " . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionUpdateDto $request, Transaction $transaction)
    {
        $transaction->update($request->toArray());
        return response()->json(['message' => 'Transaction status updated successfully', 'transaction' => $transaction]);
    }

   

    public function validatePaymentCallback(PaymentCallbackRequest $request)
    {
        try {
             ProcessWebhook::dispatch($request->all())->delay(now()->addSecond(1));
            return response()->json(['message' => 'Processing started'], 200);

         } catch (\Throwable $th) {
            Log::error("Payment Callback : " . $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
