<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\OTP;
use Illuminate\Http\Request;
use App\DTO\OTPDTO;
use App\Services\SendSMSService;
use Illuminate\Support\Str;

class OTPController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, SendSMSService $sendSMSService)
    {
        try {
            $request->validate(
                [
                    'phone' => "required|string|exists:users,phone",
                ]
            );

            $otpPending  = OTP::where('phone', $request->phone)->where('is_used', false)
                // ->where('expired_at', '>', now()->addMinutes(7))
                ->first();
            if ($otpPending) {
                if ($otpPending->created_at > now()->subMinutes(3)) {
                    return response()->json([
                        'status' => false,
                        'data' => null,
                        'error' => "Un code de verification est deja en cours d'envoi",
                    ], 400);
                }
            }

            $totalOTPDay = OTP::where('phone', $request->phone)->whereDate('created_at', today())->count();

            if ($totalOTPDay >= 3) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                    'error' => "Vous avez atteint le nombre maximal de code de verification pour aujourd'hui",
                ], 400);
            }

            $otpDTO  = new OTPDTO([
                'phone' => $request->phone,
                'otp' => rand(1000, 9999),
                'is_used' => false,
                'verification_id' => Str::uuid(),
                'expired_at' => now()->addMinutes(10),
            ]);
            OTP::create($otpDTO->toArray());
            $sendSMSService->sendSMS("Votre code de verification MONPROF est " . $otpDTO->otp, $otpDTO->phone);
            return response()->json([
                'status' => true,
                'data' => $otpDTO->toArrayWithoutOtp(),
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ]);
        }
    }


    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'phone' => "required|string|exists:users,phone",
                'otp' => "required|string",
            ]);

            $otp = OTP::where('phone', $request->phone)->where('otp', $request->otp)->where('is_used', false)->first();
            if (!$otp) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                    'error' => "Code de verification invalide ",
                ], 400);
            }
            if ($otp->expired_at < now()) {
                return response()->json([
                    'status' => false,
                    'data' => null,
                    'error' => "Code de verification expiré",
                ], 400);
            }
            // $otp->update(['is_used' => true]);
            return response()->json([
                'status' => true,
                'data' => $otp,
                'error' => null,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $th->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OTP $oTP)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OTP $oTP)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OTP $oTP)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OTP $oTP)
    {
        //
    }
}
