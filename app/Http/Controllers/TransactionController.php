<?php

namespace App\Http\Controllers;

use App\DTO\TransactionPostDto;
use App\DTO\TransactionUpdateDto;
use App\Models\Transaction;
use App\DTO\MundiPayRequestDTO;
use App\Http\Requests\PaymentCallbackRequest;
use App\Models\Paiements;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
                'payment_token' => $request->payment_token,
                'country_code' => "237",
                'phone_number' => $request->phone_number,
            ]);
            $response =  \App\Services\MundiPayService::requestPaymentIntent($mundiPayRequestDTO);
            $request->transaction_id = $response->transactionId;
            $request->payment_token = $response->paymentToken;
            $transaction = Transaction::create($request->toArray());
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

    public function validatePayment(Transaction $transaction, PaymentCallbackRequest $request)
    {

        if ($transaction->status === "paid" || $transaction->status === "SUCCESS") {
            // send notification to user
            $paiement = Paiements::where('transaction_id', $transaction->id)->first();
            if ($paiement) {
                $paiementController = new \App\Http\Controllers\Web\PaiementsController();
                $paiementController->validatePayment($request->merge(['paiement' => $paiement->id]));
            }
        }
        $user = User::find($transaction->user_id);
        $token = $user->fcm_token;
        if ($token != null) {
            $notifPaymentSuccess = new \App\Services\PushNotifictaionService(
                $transaction->status == "SUCCESS" ?
                    "Votre paiement de " . $transaction->amount . " a été validé avec succès.\n Monprof vous remercie " :
                    "Votre paiement de " . $transaction->amount . " a échoué.\n Monprof vous remercie; Motif : " . ($request?->raison_reject ?? "Indéterminé"),
                $transaction->status == "SUCCESS" ? 'Paiement Validé Monprof' : 'Paiement Échoué Monprof'
            );
            $notifPaymentSuccess->sendNotificationToToken(
                $token,
                even_type: "PAYMENT_STATUS",
                data: [
                    'amount' => $transaction->amount,
                    'transaction_id' => $transaction->id,
                    'status' => $transaction->status
                ]
            );
        }
    }

    public function validatePaymentCallback(PaymentCallbackRequest $request)
    {
        try {
            $transaction = Transaction::where('transaction_id', $request->transaction_id)->first();
            if (!$transaction) {
                return response()->json(['status' => false, 'data' => null, "error" => "Transaction not found"], 404);
            }
            $updateData = new TransactionUpdateDto([
                'status' => $request->status == "paid" ? "SUCCESS" : ($request->status === "unpaid" ? "FAILED" : $transaction->status),
                'raison_reject' => $request?->raison_reject ?? null,
            ]);
            if ($request->status === "unpaid" && $request->raison_reject) {
                $updateData->raison_reject = $request->raison_reject;
                $updateData->status = "FAILED";
            }
            $transaction->update($updateData->toArray());
            $this->validatePayment($transaction, $request);
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
