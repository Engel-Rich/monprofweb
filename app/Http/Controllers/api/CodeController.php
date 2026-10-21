<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\ActiveCourseJob;
use App\Models\Codes;
use App\Models\User;
use App\Services\CourseAccessService;
use App\Services\PushNotifictaionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    //
    public function activeCode(Request $request, CourseAccessService $courseAccess)
    {
        try {
            $validated = $request->validate([
                'code' => ['required', 'string', 'max:100'],
            ]);
            $user = Auth::user();
            $code = $courseAccess->activateCodeForUser($user, $validated['code']);
        } catch (\Throwable $th) {
            Log::warning('Activation de code refusée.', [
                'user_id' => Auth::id(),
                'error' => $th->getMessage(),
            ]);

            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()], 400);
        }

        // Le code est déjà activé en base : un échec de notification ne doit
        // pas faire croire à l'élève que l'activation a échoué.
        $this->notifyActivation($user, $code);

        return response()->json([
            'status' => true,
            'data' => $code,
            'error' => null,
        ]);
    }

    private function notifyActivation(User $user, Codes $code): void
    {
        if (! $user->fcm_token) {
            return;
        }

        try {
            $category = $code->paiement?->categorie?->libelle;
            $classe = $user->eleve()->with('classe')->first()?->classe?->libelle;
            $message = "Félicitations {$user->name} {$user->last_name}, vous venez de débloquer les cours"
                .($category ? " de la catégorie {$category}" : '')
                .($classe ? " dans votre classe {$classe}" : '')
                .'.';

            ActiveCourseJob::dispatch(
                new PushNotifictaionService($message, 'Validation des cours dans Monprof'),
                $user->fcm_token,
            );
        } catch (\Throwable $th) {
            Log::error('Notification d’activation de code non envoyée.', [
                'user_id' => $user->id,
                'code_id' => $code->id,
                'exception' => $th,
            ]);
        }
    }
}
