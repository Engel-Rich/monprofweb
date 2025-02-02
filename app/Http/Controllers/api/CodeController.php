<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\ActiveCourseJob;
use App\Models\Codes;
use App\Models\Eleve;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\PushNotifictaionService;
use Illuminate\Support\Facades\Log;

class CodeController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    //
    public function activeCode(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->post(),
                [
                    'code' => [
                        "string",
                        'required',
                        // Rule::exists('codes', 'code')->where('actif', 0),
                    ],
                ]
            );
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                    'error' => $validator->errors()->first(),
                ], status: 402);
            }
            $user  = Auth::user();
            $eleve = Eleve::with("classe")->where("user_id", $user->id)->limit(1)->get()->first();
            if ($eleve == null) {
                throw new Exception(message: 'Aucun élève trouvé');
            } else {
                $code = Codes::with("paiement.categorie")->where('code', $request->post('code'))
                    ->limit(1)->get()->first();
                $code->active_date = Carbon::now();
                $code->actif = 1;
                $code->eleve_id = $eleve->id;
                $paiement =  $code->paiement;
                $categorie = $paiement->categorie;
                $code->save();
                if ($user->fcm_token) {
                    $token = $user->fcm_token;
                    $notificationService = new PushNotifictaionService("Félicitations " . $user->name . " " . $user->last_name . ", vous venez de débloquer les cours de la catégorie " . $categorie->libelle . " dans votre classe " . $eleve->classe->libelle . ".\nVous pouvez désormais vous entraîner sans relâche.\nMonprof vous remercie 🤗🤗🤗🤗", 'Validation des cours dans Monprof');
                    ActiveCourseJob::dispatch($notificationService, $token);
                }
                return response()->json([
                    'status' => true,
                    'data' => $code,
                    'error' => null,
                ],);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()], 400);
        }
    }
}
