<?php
namespace App\Services;

use App\Services\SendMessageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendMailJob;
use App\Models\Codes;
use App\Models\Paiements;
use App\Models\User;
use App\Services\PushNotifictaionService;

class PaiementService{

public function genererCodeActivation($id_paiement_attente): string
    {
        $formatDate = 'd/m/Y';
        $formatHeure = 'H:i:s';
        $dateActuelle = date($formatDate);
        $heureActuelle = date($formatHeure);

        $dateActuelleDetail = explode('/', $dateActuelle);
        $heureActuelleDetail = explode(':', $heureActuelle);
        $code = $dateActuelleDetail[1] . $dateActuelleDetail[0] . $dateActuelleDetail[2] . "" . $id_paiement_attente;

        $finalCode = "";
        for ($i = 0; $i < 10; $i++) {
            $index = rand(0, strlen($code) - 1);
            $finalCode .= $code[$index];
        }
        return "C" . $finalCode;
    }


public function saveOneCode(int $paiement_id): string|null
    {
        try {
            $id = $paiement_id;
            $code = $this->genererCodeActivation($id);
            // dd($code);
            while (Codes::where('code', $code)->exists()) {
                $code = $this->genererCodeActivation($id);
            }
            Codes::create([
                'paiements_id' => $id,
                'code' => $code,
            ]);
            return $code;
        } catch (\Throwable $th) {
            Codes::where('paiements_id', $paiement_id)->delete();
            return null;
        }
    }

 public  function validePayment(array $request)
    {
        $id = $request['paiement'];
        $paie = Paiements::find($request['paiement']);
        $user = User::find($paie->user_id);
        $qte = $paie->nombre_de_code;
        $paie->paiement_date = Carbon::now();
        $messageService = new SendMessageService($paie, $user);
        $token = $user->fcm_token;
        // dd($user->fcm_token);
        if ($qte == 1) {
            $code = $this->saveOneCode($id);            
            $paie->save();
            $messageService->sendSMS($code);
            if ($token != null) {
                $notifOneCode = new PushNotifictaionService("Votre paiement a été validé avec succès et votre code a été activé\n Vous recevrez le code par SMS.\n Monprof vous remercie 🤗🤗🤗🤗", 'Validation de compte Monprof');
                $notifOneCode->sendNotificationToToken($token);
            }
            Log::info("Code activated successfully");
        } else {
            $data = $this->saveManyCod($id, $qte);
            $paie->save();
            if (count($data) == 0) {
                Log::alert("Email Hasn't been send because code file has not been created successfully");
            } else {
                SendMailJob::dispatch($messageService, $data, $token)->delay(now());
                if ($token != null) {
                    $notifManyCode = new PushNotifictaionService("Votre paiement de $qte a été validé avec succès et vos codes ont été activé\n Vous recevrez la liste des codes par Mail.\n Monprof vous remercie 🤗🤗🤗🤗", 'Validation de compte Monprof');
                    $notifManyCode->sendNotificationToToken($token, even_type: "PAYMENT");
                }
                Log::info("Email Has been send successfully user can now download file");
            }
        }
    }

public function saveManyCod(int $paiement_id, int $qte): array
    {
        $count = 1;
        $codeList = [];
        do {
            $code = $this->saveOneCode($paiement_id);
            if ($code != null) {
                // $codeList[$count] = $code;
                array_push($codeList, $code);
                $count++;
            } else {
                Codes::where('paiements_id', $paiement_id)->delete();
                $error = 'erreur de serveur inconnue';
                $codeList = [];
                break;
            }
        } while ($count <= $qte);

        // dd($codeList);
        return $codeList;
    }


}