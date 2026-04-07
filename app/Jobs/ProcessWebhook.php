<?php

namespace App\Jobs;

use App\DTO\TransactionUpdateDto;
use App\Models\Paiements;
use App\Models\PayementServices;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $paymentCallbackRequest;

    /**
     * Create a new job instance.
     */
    public function __construct(array $paymentCallbackRequest)
    {
        $this->paymentCallbackRequest = $paymentCallbackRequest;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
     try {
            $request = $this->paymentCallbackRequest;
            $reference = $request['reference']; 
            $externalReference = 'MPP-'.$request['external_reference'];
            
            $id = $reference==null? $request['transaction_id']: $reference;

            $transaction = Transaction::where('transaction_id', $id)
            ->orWhere('reference', $externalReference)
            ->first();
            if (!$transaction) {
               Log::error('Transaction not found', ['reference' => $externalReference]);
            }
            $status = $request['status'] == "paid" ? "SUCCESS" : ($request['status'] === "unpaid" ? "FAILED" : $transaction->status);
            
            if($transaction->status==$status){
                Log::info(response()->json(['status' => true, 'data' => $transaction, "error" => null], 200));
            }
            $updateData = new TransactionUpdateDto([
                'status' => $status,                
                'metadatas' => json_encode($request),
            ]);
            if ($updateData->status === "FAILED" && $request['raison_reject']) {
                $updateData->raison_reject = $request['raison_reject'];
                $updateData->status = "FAILED";
            }
            $transaction->update($updateData->toArray());
            $transaction->refresh();
            $this->validatePayment($transaction, $request);
        } catch (\Throwable $th) {
            Log::error("Payment Callback : " . $th->getMessage());
        }
    }


   private   function validatePayment(Transaction $transaction, array $request)
    {

        if ($transaction->status === "SUCCESS") {
            // send notification to user
            $paiement = Paiements::where('transaction_id', $transaction->id)->first();
            if ($paiement) {
                app(PayementServices::class)->validatePayment(
                    [
                    ...$request,
                    'paiement' => $paiement->id
                ]
                );
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
                    'status' => $transaction->status,
                    'raison_reject' => $request?->raison_reject ?? null,
                ]
            );
        }
    }
}
