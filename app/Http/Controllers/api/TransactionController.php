<?php

namespace App\Http\Controllers\api;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Paiements;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $transactions = Paiements::query()
            // 1. Spécifiez les colonnes pour éviter que l'ID de la transaction n'écrase celui du paiement
            ->select(
                'paiements.*',
                'transactions.id as local_transaction_id',
                'transactions.status as trans_status',
                'transactions.reference',
                'transactions.provider_reference',
                'transactions.payment_token',
                'transactions.phone_number as phone_number_payment',
                'transactions.base_amount as transaction_base_amount',
                'transactions.service_fee',
                'transactions.amount as transaction_amount',
                'transactions.conclusion_method'
            )

            // 2. Jointure interne (Inner Join)
            ->join('transactions', 'paiements.transaction_id', '=', 'transactions.id')

            // 3. Eager load de la catégorie (si c'est une relation définie dans le modèle Paiements)
            ->with('categorie')

            ->where('paiements.user_id', $userId)
            ->get();

        return $transactions;
    }

    public function status(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless((int) $transaction->user_id === (int) $request->user()->id, 404);

        $transaction->load('paiement');
        $status = strtoupper((string) $transaction->status);
        $isSuccessful = $status === TransactionStatus::SUCCESS->value
            && (bool) $transaction->paiement?->status;
        $isFailed = in_array($status, [
            TransactionStatus::FAILED->value,
            TransactionStatus::CANCELLED->value,
            TransactionStatus::ERROR->value,
        ], true);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'status' => $isSuccessful ? TransactionStatus::SUCCESS->value : $status,
                'is_final' => $isSuccessful || $isFailed,
                'is_successful' => $isSuccessful,
                'failure_reason' => $isFailed
                    ? ($transaction->raison_reject ?: 'Le fournisseur a refusé le paiement.')
                    : null,
                'payment_id' => $transaction->paiement?->id,
                'payment_validated_at' => $transaction->paiement?->paiement_date?->toIso8601String(),
                'base_amount' => $transaction->base_amount,
                'service_fee' => $transaction->service_fee,
                'amount' => $transaction->amount,
                'conclusion_method' => $transaction->conclusion_method,
            ],
        ]);
    }
}
