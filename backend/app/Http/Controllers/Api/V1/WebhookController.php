<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentEventProcessor;
use App\Services\Payments\WebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    public function handle(Request $request, string $provider, PaymentEventProcessor $processor): JsonResponse
    {
        $config = config("payments.providers.{$provider}");
        abort_unless(is_array($config), 404);

        $secret = (string) ($config['webhook_secret'] ?? '');
        $rawBody = $request->getContent();

        // Sans secret configuré, aucun événement n'est accepté.
        $valid = $secret !== '' && WebhookSignature::verify(
            $secret,
            (string) $request->header('X-Webhook-Timestamp'),
            $rawBody,
            (string) $request->header('X-Webhook-Signature'),
            (int) ($config['webhook_tolerance'] ?? 300),
        );

        if (! $valid) {
            Log::warning('payments.webhook.invalid_signature', [
                'provider' => $provider,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Signature invalide.'], 401);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Corps de requête invalide.'], 400);
        }

        $event = Validator::make($payload, [
            'id' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:60'],
            'data' => ['required', 'array'],
            'data.provider_reference' => ['required', 'string', 'max:100'],
            'data.amount_minor' => ['nullable', 'integer'],
            'data.currency' => ['nullable', 'string', 'size:3'],
        ])->validate();

        return response()->json([
            'received' => true,
            'outcome' => $processor->process($provider, $event),
        ]);
    }
}
