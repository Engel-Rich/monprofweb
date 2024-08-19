<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendMailJob;
use App\Jobs\SendMessageJob;
use App\Models\Codes;
use App\Models\Paiements;
use App\Models\User;
use App\Services\SendMessageService;
use Carbon\Carbon;
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
        do {
            $code = $this->saveOneCode($paiement_id);
            if ($code != null) {
                // $codeList[$count] = $code;
                array_push($codeList, $code);
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
        $id = $request->paiement;
        $paie = Paiements::find($request->paiement);
        $user = User::find($paie->user_id);
        $qte = $paie->nombre_de_code;
        $error = null;
        
        $messageService  = new SendMessageService($paie, $user);
        if ($qte == 1) {
            $code = $this->saveOneCode($id);

            SendMessageJob::dispatch($messageService, $code)->delay(now());

            return redirect()->route('paiement.index');
        } else {
            $data = $this->saveManyCod($id, $qte);        
            if (count($data) == 0) {
                  return redirect()->route('paiement.index');
            }
             else {            
            SendMailJob::dispatch($messageService, $data)->delay(now());
            return redirect()->route('paiement.index');
            }
        }
        
       
        // if ($error == null) {

        //     $dateTime =  Carbon::now();
        //     $paieUp = Paiements::find($id);
        //     $paieUp->paiement_date = $dateTime;
        //     $paieUp->status = true;
        //     $paieUp->save();

        //     return redirect()->route('paiements.index');
        // } else {
        //     return redirect()->back()->withErrors($error);
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
