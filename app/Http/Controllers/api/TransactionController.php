<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Paiements;
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
                'transactions.phone_number as phone_number_payment'
            )

            // 2. Jointure interne (Inner Join)
            ->join('transactions', 'paiements.transaction_id', '=', 'transactions.id')

            // 3. Eager load de la catégorie (si c'est une relation définie dans le modèle Paiements)
            ->with('categorie')

            ->where('paiements.user_id', $userId)
            ->get();

        return $transactions;
    }
}
