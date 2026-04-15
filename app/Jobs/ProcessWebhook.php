<?php

namespace App\Jobs;

use App\DTO\TransactionUpdateDto;
use App\DTO\WebhookHandlingDTO;
use App\Models\Paiements;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaiementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WebhookHandlingDTO $dto;

    /**
     * Create a new job instance.
     */
    public function __construct(WebhookHandlingDTO $dto)
    {
        $this->dto = $dto;
    }

    /**
     * Execute the job.
     */


    public function handle(): void
    {
        Log::info("Started Handling webhook");

        $id = $this->dto->id;
        $externalReference = $this->dto->externalReference;

        // Log::info("transaction_id: $id, external reference: $externalReference");

        $transaction = Transaction::where('transaction_id', $id)
            ->orWhere('reference', $externalReference)
            ->first();

        if (!$transaction) {
            Log::error('Transaction not found');
            return;
        }
        Log::error('Transaction  found');
        $status = match ($this->dto->status) {
            'SUCCESSFUL', 'paid' => 'SUCCESS',
            'FAILED', 'unpaid' => 'FAILED',
            default => $transaction->status,
        };

        if ($transaction->status === $status) {
            Log::info("No status change " . $status);
            $this->validatePayment($transaction, $this->dto);

            return;
        }

        $data = [
            'status' => $status,
            'metadatas' => json_encode([
                "webhook_data" => $this->dto->toString()
            ]),
        ];

        if ($status === "FAILED" && $this->dto->raisonReject) {
            $data['raison_reject'] = $this->dto->raisonReject;
        }

        $transaction->update($data);
        $transaction->refresh();

        Log::info("Webhook processed successfully");

        $this->validatePayment($transaction, $this->dto);
    }

    private   function validatePayment(Transaction $transaction, WebhookHandlingDTO $request)
    {

        if ($transaction->status === "SUCCESS") {
            // send notification to user
            Log::info("Success transactions ".$transaction->id);

            $paiement = Paiements::where('transaction_id', $transaction->id)->first();
            Log::info("Paiement id :".$paiement?->id);
           if($paiement){
             if ($paiement) {
                app(PaiementService::class)->validePayment(
                    [
                        ...$request->toArray(),
                        'paiement' => $paiement->id
                    ]
                );
            }
           }else{
            Log::info("No paiement fund for this tarsanctions ");
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
