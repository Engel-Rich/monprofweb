<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\Codes;
use App\Models\Paiements;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PaiementsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paiments  =  Paiements::with('user', 'categorie')->paginate(20);
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

    protected function genererCodeActivation($id_paiement_attente): string
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


    protected function saveManyCod(int $paiement_id, int $qte): array
    {
        $count = 1;
        $codeList = [];
        $error = null;
        do {
            $code = $this->saveOneCode($paiement_id);
            if ($code != null) {
                $codeList[$count] = $code;
                $count++;
            } else {
                Codes::where('paiement_id', $paiement_id)->deleted();
                $error = 'erreur de serveur inconnue';
                $codeList = [];
                break;
            }
        } while ($count <= $qte);

        // dd($codeList);
        return $codeList;
    }


    protected function saveOneCode(int $paiement_id): string|null
    {
        try {
            $id = $paiement_id;
            $code = $this->genererCodeActivation($id);
            // dd($code);
            while (Codes::where('code', $code)->exists()) {
                $code =     $this->genererCodeActivation($id);
            }
            Codes::create([
                'paiements_id' => $id,
                'code' => $code,
            ]);
            return $code;
        } catch (\Throwable $th) {
            Codes::where('paiement_id', $paiement_id)->deleted();
            return null;
        }
    }


    /**
     * Function permettant d'envoiyer le sms
     */
    protected function sendSMS(Paiements $paie, string $code = null, string $message = null)
    {

        $url = 'https://sms.etech-keys.com/ss/api.php';
        $params = [
            'login' => '699784188',
            'password' => 'NA9n335',
            'sender_id' => 'MonProf',
            'destinataire' => $paie->numero_client,
            'message' => $message ?: 'Bonjour Mr/Mm Votre code d\'activation est le ssuivant   ' . $code . ' a utiliser pour se connecter sur mon prof',
        ];
        $headers = [
            'Accept' => 'application/json', // Exemple d'en-tête pour indiquer le type de contenu attendu            
        ];
        /**
         * @\Illuminate\Http\Client\Response
         */
        $data = Http::withHeaders($headers)->get($url, $params);
    }

    /**
     * 
     * Function permettant d'envoyer les mails
     * 
     */

    protected function sendEmail(array $codes, Paiements $paie, User $user): bool|array
    {
        $fichier = $this->createandStoreFile($codes, $paie, $user);

        if ($fichier !== null) {
            $fileUrl = $fichier;
            $smtpUrl = "https://api.emailjs.com/api/v1.0/email/send";
            $tamplate_id = "template_ljtz61w";
            $publicKey = "OpI44bJgBxEW76yfA";
            $privateKey = 'Ff_WCj2VjcsMvAx6JyqC6';
            $serviceId = 'service_bslmdnc';
            $templateParam = [
                "to_name" => $user->name . ' ' . $user->last_name,
                "quantite" => count($codes),
                "file_link" => $fileUrl,
                "to_email" => $user->email,
                'from_name'=>"Monprof"
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
                // dd($data);
            } catch (\Throwable $th) {
                return ['error' => 'Les codes ont été généré mais impossible d\'envoyer le mail'.$th->getMessage() ];
            }

            // $curl = curl_init($smtpUrl);
            // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($curl, CURLOPT_POST, true);
            // curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parametter));
            // curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            // $response = curl_exec($curl);
            // curl_close($curl);

            return true;
        } else {
            return ['error' => 'Les codes ont été généré mais impossible de générer le ficher'];
            // echo "Erreur de création du fichier";

        }
    }


    /**
     * Function permettant de générer les fichier 
     */

    protected  function createAndStoreFile(array $codeList, Paiements $paie, User $user)
    {
        try {
            $fileName = 'codes/' . $user->name . now()->format('Ymd_His') . '.txt';
            // $cheminFichier = storage_path('app/' . $fileName);
            $contenu = "Date: " . now()->format('Y-m-d H:i:s') . "\n\n";
            $contenu = $contenu."Nombre de code : " . $paie->nombre_de_code . "\n";
            $contenu = $contenu."Montant du paiement: " . $paie->montant . "XAF \n";
            $contenu = $contenu."Numéro débité " . $paie->numero_payeur . "\n";
            $contenu = $contenu."Numéro à notifier " . $paie->numero_client . "\n\n";
            $contenu = $contenu."Liste des codes. \n\n";
            foreach ($codeList as $code => $valeur) {
                $contenu .= "$code:    $valeur\n";
            }
            $write = Storage::disk('public')->put($fileName,$contenu);           
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


    /**
     * Function permettant de valider un code
     */

    public function valide(Request $request)
    {
        $id = $request->paiement;
        $paie = Paiements::find($request->paiement);
        $user = User::find($paie->user_id);
        $qte = $paie->nombre_de_code;
        $error = null;
        if ($qte == 1) {
            $code = $this->saveOneCode($id);
            if ($code != null) {
                $this->sendSMS($paie, code: $code);
            } else {
                $error = ['error' => 'Imposible de générer le code'];
            }
        } else {
            $data = $this->saveManyCod($id, $qte);
            if (count($data) == 0) {
                $error = ['error' => 'Imposible de générer les codes'];
            } else {
                $sendEmail =  $this->sendEmail($data, $paie, $user);
                if ($sendEmail!=true) {
                    $error =$sendEmail;
                }
                $this->sendSMS($paie, message: "Vous venez d'activer " . $qte . " de codes chez MONPROF un mail a été envoyé à l'adresse" . $user->email . " contenant la liste de codes");
            }
        }
        if ($error == null) {

            $dateTime =  Carbon::now();
            $paieUp = Paiements::find($id);
            $paieUp->paiement_date = $dateTime;
            $paieUp->status = true;
            $paieUp->save();

            return redirect()->route('paiements.index');
        } else {
            return redirect()->route('paiement.active', $paie->id)->withErrors($error);
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
