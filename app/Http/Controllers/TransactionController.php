<?php

namespace App\Http\Controllers;

use App\DTO\CreateTransactionDto;
use App\DTO\TransactionPostDto;
use App\DTO\TransactionUpdateDto;
use App\Models\PayementServices;
use App\Models\PaymentProvider;
use App\Models\Transaction;
// use App\Models\Paiements;
// use App\Models\User;
use App\Services\Payments\PaymentFactory;
use App\Services\Payments\TransactionAuditService;
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
        $transaction = static::createPendingTransaction($request);

        return static::initiateWithProvider($transaction, $request);
    }

    /**
     * Crée la ligne locale sans appeler le fournisseur.
     *
     * Séparer cette étape permet à l'appelant d'enregistrer les entités liées
     * (paiement…) dans la même transaction SQL, avant que le fournisseur ne
     * puisse notifier le succès.
     */
    public static function createPendingTransaction(TransactionPostDto $request): Transaction
    {
        try {
            $paymentService = filled($request->service_id)
                ? PayementServices::query()->with('provider')->findOrFail($request->service_id)
                : null;
            $provider = $paymentService?->provider ?? PaymentProvider::active()->firstOrFail();

            if (! $provider->is_active || ($paymentService && ! $paymentService->is_active)) {
                throw new \RuntimeException('Le service ou le fournisseur de paiement sélectionné est inactif.');
            }

            return Transaction::create([
                ...$request->toArray(),
                'payment_provider_id' => $provider->id,
            ]);
        } catch (\Throwable $th) {
            Log::error('Payment : '.$th->getMessage());
            throw $th;
        }
    }

    /**
     * Déclenche le paiement auprès du fournisseur et stocke sa référence.
     */
    public static function initiateWithProvider(Transaction $transaction, TransactionPostDto $request): Transaction
    {
        $transaction->loadMissing(['provider', 'paymentService']);
        $provider = $transaction->provider ?? PaymentProvider::active()->firstOrFail();
        $providerServiceId = $transaction->paymentService?->subscription_id ?? $request->subscription_id;
        $reference = (string) $transaction->reference;

        $createTransactionRequest = CreateTransactionDto::fromArray([
            'userId' => $request->user_id,
            'type' => 'DEPOSIT',
            'sense' => $request->sens,
            'amount' => $request->amount,
            'phoneNumber' => $request->phone_number,
            'countryCode' => '237',
            'reference' => $reference, //$transaction->reference,
            'providerServiceId' => filled($providerServiceId) ? (string) $providerServiceId : null,
        ]);

        try {
            $strategy = PaymentFactory::make($provider);
            $response = $strategy->processPayment($createTransactionRequest);

            if ($response->isFailed() || ! $response->providerReference) {
                throw new \RuntimeException(
                    $response->error ?? 'Payment initiation failed with provider '.$strategy->getProviderName()
                );
            }

            $updates = ['provider_reference' => $response->providerReference];

            // MundiPay renvoie un pay_token distinct de la référence : sans lui
            // impossible de rejouer ou de tracer le paiement côté fournisseur.
            if (filled($response->paymentIntent)) {
                $updates['payment_token'] = $response->paymentIntent;
            }

            $transaction->update($updates);
            app(TransactionAuditService::class)->record(
                transaction: $transaction->refresh(),
                event: 'provider.payment_initiated',
                source: 'initiation',
                payload: $response->paymentResponseModel?->data,
                providerCode: $provider->code,
            );
        } catch (\Throwable $th) {
            Log::error('Payment initiation failed: '.$th->getMessage());
            $transaction->update([
                'status' => 'FAILED',
                'raison_reject' => Str::limit($th->getMessage(), 250),
            ]);
            app(TransactionAuditService::class)->record(
                transaction: $transaction->refresh(),
                event: 'provider.payment_initiation_failed',
                source: 'initiation',
                payload: ['error' => $th->getMessage()],
                providerCode: $provider->code,
            );
            throw $th;
        }

        return $transaction->refresh();
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
