<?php

namespace App\Http\Controllers\Webhooks;

use App\DTO\CampayWebhookPayload;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentCallbackRequest;
use App\Jobs\ProcessWebhook;
use App\Services\Payments\CampayWebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CampayWebhookController extends Controller
{
    public function __invoke(PaymentCallbackRequest $request): JsonResponse
    {
        $webhookKey = (string) config('campay.webhook_key');

        if (filled($webhookKey)
            && ! CampayWebhookSignature::isValid($request->input('signature'), $webhookKey)) {
            Log::warning('Webhook CamPay rejeté : signature invalide ou absente.', [
                'reference' => $request->input('reference'),
                'external_reference' => $request->input('external_reference'),
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        if (blank($webhookKey)) {
            Log::warning('CAMPAY_WEBHOOK_KEY absente : callback CamPay accepté puis revérifié auprès du fournisseur.');
        }

        $dto = CampayWebhookPayload::normalize($request->all());
        ProcessWebhook::dispatch($dto);

        Log::info('Webhook CamPay reçu.', ['payload' => $dto->toString()]);

        return response()->json(['message' => 'Processing started'], 200);
    }
}
