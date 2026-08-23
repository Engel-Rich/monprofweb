<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
// use App\Models\AppMessage;
use App\Models\Categorie;
use App\Models\Paiements;
use App\Models\PayementServices;
use App\Models\User;
use App\Rules\CameroonMobileNumber;
use App\Services\PushNotifictaionService;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
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
    public function store(Request $request)
    {
        try {
            $request->validate([
                // 'user_id' => 'integer|required|exists:users,id',
                'categorie_id' => 'integer|required|exists:categories,id',
                'nombre_de_code' => 'integer|required',
                'numero_payeur' => ['required', 'string', new CameroonMobileNumber],
                'numero_client' => ['required', 'string', new CameroonMobileNumber],
                'subscription_id' => 'integer|required|exists:payment_services,subscription_id',
            ]);
            $user = User::find(auth()->id());
            $categorie = Categorie::find($request->categorie_id);
            $paymentService = PayementServices::query()
                ->where('subscription_id', $request->subscription_id)
                ->where('is_active', true)
                ->whereHas('provider', fn ($query) => $query->where('is_active', true))
                ->firstOrFail();
            $data = $request->all();
            $data['user_id'] = $user->id;
            $data['montant'] = $categorie->prix * $request->nombre_de_code;
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
                'subscription_id' => $request['subscription_id'],
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
            if ($request->nombre_de_code == 1) {
                $notifOneCode = new PushNotifictaionService("Votre nouvelle commande de code chez Monprof a été enrégistrée avec succès\n Veillez valider le paiment et vous recevrez le code par SMS.\n Monprof vous remercie 🤗🤗🤗🤗", 'Nouvelle Commande de code Monprof');
                $notifOneCode->sendNotificationToToken($token);
            } else {
                $notifManyCode = new PushNotifictaionService("Votre nouvelle commande de $request->nombre_de_code codes chez Monprof a été enrégistrée avec succès\n Veillez valider le paiment et vous recevrez la liste des codes par Mail à l'adresse $user->email.\n Monprof vous remercie 🤗🤗🤗🤗", 'Nouvelle Commande de code Monprof');
                $notifManyCode->sendNotificationToToken($token);
            }

            return response()->json(['status' => true, 'data' => $paiment], 200);
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
