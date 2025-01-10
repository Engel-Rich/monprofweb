<?php

namespace App\Services;

use App\Models\Paiements;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SendMessageService
{

    protected Paiements $paie;
    protected User $user;

    public function __construct(Paiements $paie, User $user)
    {
        $this->paie = $paie;
        $this->user = $user;
    }


    public function sendSMS(string $code = null, string $message = null)
    {

        $body = [
            'message' => $message ?: 'Bonjour Mr/Mm Votre code d\'activation est le ssuivant   ' . $code . ' a utiliser pour se connecter sur mon prof',
            "senderId" => env("SMS_SENDER_ID"),
            "msisdn" => ['237' . $this->paie->numero_client]
        ];
        $url = "https://sms.lmtgroup.com/api/v1/pushes";
        $headers = [
            "X-Api-Key" => env('SMS_API_KEY'),
            "Content-Type" => "application/json",
            "X-Secret" => env('SMS_API_SECRET')
        ];


        /**
         * @\Illuminate\Http\Client\Response
         */
        try {
            Log::info("started send Message");
            $data = Http::withHeaders($headers)->post($url, $body);
            Log::notice($data->json());
            Log::info("Message has been sent successfully");
        } catch (\Throwable $th) {
            Log::info("Message has not been sent successfully");
            Log::info($th->getMessage());
        }
    }


    protected function createAndStoreFile(array $codeList)
    {
        try {
            $paie = $this->paie;

            $fileName = 'codes/' . $this->user->name . now()->format('Ymd_His') . '.txt';
            // $cheminFichier = storage_path('app/' . $fileName);
            $contenu = "Date: " . now()->format('Y-m-d H:i:s') . "\n\n";
            $contenu = $contenu . "Nombre de code : " . $paie->nombre_de_code . "\n";
            $contenu = $contenu . "Montant du paiement: " . $paie->montant . "XAF \n";
            $contenu = $contenu . "Numéro débité " . $paie->numero_payeur . "\n";
            $contenu = $contenu . "Numéro à notifier " . $paie->numero_client . "\n\n";
            $contenu = $contenu . "Liste des codes. \n\n";
            foreach ($codeList as $code => $valeur) {
                $contenu .= "$code:    $valeur\n";
            }
            $write = Storage::disk('public')->put($fileName, $contenu);
            if ($write) {
                $urlFichier = url(Storage::url($fileName));
                return $urlFichier;
            } else {
                File::delete($fileName);
                return null;
            }
        } catch (\Throwable $th) {
            // echo $th;
            return null;
        }
    }

    protected function sendEmail(array $codes)
    {
        // $paie = $this->paie;
        $fichier = $this->createandStoreFile($codes);

        if ($fichier !== null) {

            $fileUrl = $fichier;
            $smtpUrl = "https://api.emailjs.com/api/v1.0/email/send";
            $tamplate_id = "template_ljtz61w";
            $publicKey = "OpI44bJgBxEW76yfA";
            $privateKey = 'Ff_WCj2VjcsMvAx6JyqC6';
            $serviceId = 'service_bslmdnc';
            $templateParam = [
                "to_name" =>  $this->user->name . ' ' .  $this->user->last_name,
                "quantite" => count($codes),
                "file_link" => $fileUrl,
                "to_email" =>  $this->user->email,
                'from_name' => "Monprof"
            ];
            $parametter = array(
                "service_id" => $serviceId,
                "template_id" => $tamplate_id,
                "user_id" => $publicKey,
                "template_params" => $templateParam,
                "accessToken" => $privateKey,
            );

            $headers = ['Content-Type' => 'application/json'];

            /////////////////////////////////////////////////////////////////
            // initialisation de le requette vers l'API rest 
            // //////////////////////////////////////////////

            try {
                $data = Http::withHeaders($headers)->post($smtpUrl, $parametter);
            } catch (\Throwable $th) {
                Log::info('Les codes ont été généré mais impossible d\'envoyer le mail' . $th->getMessage());
            }
        } else {
            Log::info("Les codes ont été généré mais impossible de générer le ficher");
        }
    }
}
