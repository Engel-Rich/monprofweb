<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PhoneEmei
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $requestHeaderEmei = $request->header('phone-emei');
        if ($requestHeaderEmei == null) {
            return response()->json([
                'message' => 'Veuillez renseigner un idifiant de téléphone',
                'status' => false
            ], 422);
        }
        if ($request->user() != null) {
            $emeiUser = $request->user()->user_phone_emei;
        $currentUser=$request->user();
            if ($requestHeaderEmei != $emeiUser && $currentUser->rule_id==2 ) {
                return response()->json([
                    'message' => 'Vous devez vous connecter avec votre ancien téléphone',
                    'status' => false
                ], 422);
            }
        }
        return $next($request);
    }
}
