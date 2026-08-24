<?php

namespace App\Http\Controllers\Webhooks;

use App\DTO\MundiPayWebhookPayload;
use App\Http\Controllers\Controller;
use App\Http\Requests\MundiPayCallbackRequest;
use App\Jobs\ProcessWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MundiPayWebhookController extends Controller
{
    public function __invoke(MundiPayCallbackRequest $request): JsonResponse
    {
        $dto = MundiPayWebhookPayload::normalize($request->all());
        ProcessWebhook::dispatch($dto);

        // Le statut reçu n'est jamais appliqué directement : le Job interroge
        // MundiPay avec le pay_token avant toute finalisation locale.
        Log::info('Webhook MundiPay reçu.', ['payload' => $dto->toString()]);

        return response()->json(['message' => 'Processing started'], 200);
    }
}
