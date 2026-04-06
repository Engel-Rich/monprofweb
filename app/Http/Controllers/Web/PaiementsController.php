<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendMailJob;
use App\Models\User;
use App\Services\PushNotifictaionService;
use App\Services\SendMessageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Paiements;
use App\Services\PaiementService;

class PaiementsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paiments = Paiements::with('user', 'categorie')->paginate(25);
        return view('screen.paiements.index_paiements', ['paiements' => $paiments]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    /**
     * Active ai paiment
     */

    public function active(int $paiements)
    {
        $data = Paiements::with('user', 'categorie')->findOrFail($paiements);
        // dd($data);
        return view('screen.paiements.active_paiement', ['paie' => $data]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Function permettant d'envoiyer le sms
     */


    /**
     * 
     * Function permettant d'envoyer les mails
     * 
     */




    /**
     * Function permettant de générer les fichier 
     */




    /**
     * Function permettant de valider un code
     */

    public function valide(Request $request)
    {
        app(PaiementService::class)->validePayment($request->all());
        return redirect()->route('paiement.index');
        
        // $id = $request->paiement;
        // $paie = Paiements::find($request->paiement);
        // $user = User::find($paie->user_id);
        // $qte = $paie->nombre_de_code;


        // $messageService = new SendMessageService($paie, $user);
        // $paie->paiement_date = Carbon::now();
        // $token = $user->fcm_token;
        // // dd($user->fcm_token);
        // if ($qte == 1) {
        //     $code = app(PaiementService::class)->saveOneCode($id) ; //$this->saveOneCode($id);
        //     // SendMessageJob::dispatch($messageService, $code, $token)->delay(now());
        //     $paie->save();
        //     $messageService->sendSMS($code);
        //     if ($token != null) {
        //         $notifOneCode = new PushNotifictaionService("Votre paiement a été validé avec succès et votre code a été activé\n Vous recevrez le code par SMS.\n Monprof vous remercie 🤗🤗🤗🤗", 'Validation de compte Monprof');
        //         $notifOneCode->sendNotificationToToken($token);
        //     }
        //     return redirect()->route('paiement.index');
        // } else {
        //     $data = app(PaiementService::class)->saveManyCod($id, $qte) ;//$this->saveManyCod($id, $qte);
        //     $paie->save();
        //     if (count($data) == 0) {
        //         return redirect()->route('paiement.index');
        //     } else {
        //         SendMailJob::dispatch($messageService, $data, $token)->delay(now());
        //         if ($token != null) {
        //             $notifManyCode = new PushNotifictaionService("Votre paiement de $qte a été validé avec succès et vos codes ont été activé\n Vous recevrez la liste des codes par Mail.\n Monprof vous remercie 🤗🤗🤗🤗", 'Validation de compte Monprof');
        //             $notifManyCode->sendNotificationToToken($token, even_type: "PAYMENT");
        //         }
        //         return redirect()->route('paiement.index');
        //     }
        // }
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
