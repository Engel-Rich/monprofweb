<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\API\StorePaymentRequest;
// use App\Models\AppMessage;
use App\Models\Categorie;
use App\Models\Paiements;
use App\Services\PushNotifictaionService;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaiementsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StorePaymentRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = $request->user();
            $categorie = Categorie::findOrFail($validated['categorie_id']);
            $paymentService = $request->paymentService();
            $data = [
                'categorie_id' => $categorie->id,
                'nombre_de_code' => $validated['nombre_de_code'],
                'numero_payeur' => $validated['numero_payeur'],
                'numero_client' => $validated['numero_client'],
            ];
            $data['user_id'] = $user->id;
            $data['montant'] = $categorie->prix * $validated['nombre_de_code'];
            $uuid = (string) Str::uuid();
            $reference = 'MPP-'.$uuid; // strtoupper(substr(sha1(time()), 0, 10)) . rand(1000, 9999);

            // montant + 2,5 % de frais, arrondi au multiple de 5 supérieur :
            // les opérateurs mobile money camerounais refusent tout autre montant.
            $montantAvecFrais = $data['montant'] + ($data['montant'] * 2.5 / 100);
            $montantAPayer = (int) (ceil($montantAvecFrais / 5) * 5);

            $transactionPostDto = new \App\DTO\TransactionPostDto([
                'reference' => $reference,
                'amount' => (string) $montantAPayer,
                'phone_number' => $data['numero_payeur'],
                'status' => 'PENDING',
                'sens' => 'IN',
                'user_id' => $user->id,
                'service_id' => (string) $paymentService->id,
                'subscription_id' => filled($paymentService->subscription_id)
                    ? (string) $paymentService->subscription_id
                    : null,
            ]);

            // Le paiement doit exister avant que le fournisseur ne puisse
            // notifier le succès : sinon la finalisation ne trouve pas la ligne
            // et la transaction reste bloquée en PENDING alors qu'elle est payée.
            [$trx, $paiment] = DB::transaction(function () use ($transactionPostDto, $data) {
                $trx = TransactionController::createPendingTransaction($transactionPostDto);
                $data['transaction_id'] = $trx->id;

                return [$trx, Paiements::create($data)];
            });

            $trx = TransactionController::initiateWithProvider($trx, $transactionPostDto);

            Log::debug('Transaction initiée', ['transaction' => $trx->id, 'paiement' => $paiment->id]);

            // transaction Post DTO
            $token = $user->fcm_token;
            if ($validated['nombre_de_code'] === 1) {
                $notifOneCode = new PushNotifictaionService("Votre nouvelle commande de code chez Monprof a été enrégistrée avec succès\n Veillez valider le paiment et vous recevrez le code par SMS.\n Monprof vous remercie 🤗🤗🤗🤗", 'Nouvelle Commande de code Monprof');
                $notifOneCode->sendNotificationToToken($token);
            } else {
                $quantity = $validated['nombre_de_code'];
                $notifManyCode = new PushNotifictaionService("Votre nouvelle commande de {$quantity} codes chez Monprof a été enregistrée avec succès.\nVeuillez confirmer le paiement sur votre téléphone.", 'Nouvelle commande MonProf');
                $notifManyCode->sendNotificationToToken($token);
            }

            $paiment->setRelation('transaction', $trx);

            return response()->json([
                'status' => true,
                'data' => $paiment,
                'transaction' => [
                    'id' => $trx->id,
                    'reference' => $trx->reference,
                    'provider_reference' => $trx->provider_reference,
                    'payment_token' => $trx->payment_token,
                    'status' => $trx->status,
                ],
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
