<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\ActiveCourseJob;
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
            $student = $user->eleve()->with('classe')->first();
            $category = $code->paiement->categorie;

            if ($user->fcm_token) {
                $notificationService = new PushNotifictaionService(
                    "Félicitations {$user->name} {$user->last_name}, vous venez de débloquer les cours de la catégorie {$category->libelle} dans votre classe {$student->classe->libelle}.",
                    'Validation des cours dans Monprof',
                );
                ActiveCourseJob::dispatch($notificationService, $user->fcm_token);
            }

            return response()->json([
                'status' => true,
                'data' => $code,
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            Log::warning('Activation de code refusée.', [
                'user_id' => Auth::id(),
                'error' => $th->getMessage(),
            ]);

            return response()->json(['status' => false, 'data' => null, 'error' => $th->getMessage()], 400);
        }
    }
}
