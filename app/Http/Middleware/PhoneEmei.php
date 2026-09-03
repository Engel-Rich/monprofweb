<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Support\DeviceGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PhoneEmei
{
    /**
     * Vérifie que la requête provient du téléphone rattaché au compte.
     *
     * Les réponses portent un « code » machine (App\Enums\ApiErrorCode) : le
     * mobile route dessus, pas sur le texte du message ni sur le statut HTTP.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestHeaderEmei = $request->header('phone-emei');

        if ($requestHeaderEmei === null) {
            return response()->json(ApiErrorCode::DEVICE_ID_MISSING->response(), 422);
        }

        // Compte de test : identifié soit par le corps de la requête (connexion,
        // avant authentification), soit par le porteur du jeton.
        if (DeviceGuard::bypasses($request->input('email'))) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null && ! DeviceGuard::isAuthorized($user, $requestHeaderEmei)) {
            return response()->json(
                ApiErrorCode::DEVICE_NOT_AUTHORIZED->response([
                    'phone_hint' => DeviceGuard::maskPhone($user->phone),
                ]),
                422
            );
        }

        return $next($request);
    }
}
