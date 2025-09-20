<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadFormWebhookRequest;
use App\Jobs\ProcessInboundWebhook;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function leadForm(LeadFormWebhookRequest $request): JsonResponse
    {
        // Validate webhook token
        $expectedToken = config('app.webhook_lead_token');
        $providedToken = $request->header('X-Starfolk-Webhook-Token');
        
        if (!$expectedToken || !hash_equals($expectedToken, $providedToken ?? '')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Generate idempotency key if not provided
        $idempotencyKey = $request->input('idempotency_key') 
            ?? $request->input('submission_id') 
            ?? Str::uuid()->toString();

        // Check if we've already processed this webhook
        $existingEvent = WebhookEvent::where('idempotency_key', $idempotencyKey)->first();
        if ($existingEvent) {
            return response()->json([
                'message' => 'Webhook already processed',
                'receipt_id' => $existingEvent->id,
                'status' => $existingEvent->status
            ], 202);
        }

        // Create webhook event record
        $webhookEvent = WebhookEvent::create([
            'idempotency_key' => $idempotencyKey,
            'event_type' => 'lead_form_submission',
            'source' => 'website_form',
            'payload' => $request->validated(),
            'signature' => null,
            'received_at' => now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        // Dispatch processing job
        ProcessInboundWebhook::dispatch($webhookEvent->id);

        return response()->json([
            'message' => 'Webhook received and queued for processing',
            'receipt_id' => $webhookEvent->id
        ], 202);
    }
}
