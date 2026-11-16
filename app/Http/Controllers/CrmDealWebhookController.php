<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCrmDealJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CrmDealWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->input('data', $request->all());

        $validator = Validator::make($payload, [
            'event_id' => ['required', 'string'],
            'event_type' => ['required', 'string'],

            'organization' => ['required', 'array'],
            'organization.external_id' => ['required', 'string'],
            'organization.name' => ['required', 'string'],
            'organization.industry' => ['nullable', 'string'],

            'deal' => ['required', 'array'],
            'deal.external_id' => ['required', 'string'],
            'deal.title' => ['required', 'string'],
            'deal.value' => ['required', 'numeric', 'min:0'],
            'deal.stage' => ['required', 'string'],
            'deal.pipeline' => ['required', 'string'],
            'deal.currency' => ['required', 'string', 'size:3'],

            'contacts' => ['required', 'array', 'min:1'],
            'contacts.*.external_id' => ['required', 'string'],
            'contacts.*.first_name' => ['required', 'string'],
            'contacts.*.last_name' => ['required', 'string'],
            'contacts.*.email' => ['nullable', 'email'],
            'contacts.*.phone' => ['nullable', 'string'],
            'contacts.*.role' => ['nullable', 'string'],
            'contacts.*.is_primary' => ['sometimes', 'boolean'],

            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.external_id' => ['required', 'string'],
            'line_items.*.name' => ['required', 'string'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],

            'activities' => ['required', 'array', 'min:1'],
            'activities.*.external_id' => ['required', 'string'],
            'activities.*.type' => ['required', 'string'],
            'activities.*.subject' => ['required', 'string'],
            'activities.*.description' => ['nullable', 'string'],
            'activities.*.occurred_at' => ['nullable', 'date'],

            'custom_fields' => ['required', 'array'],
            'metadata' => ['required', 'array'],
        ]);

        $data = $validator->validate();
        $requestId = (string) Str::uuid();

        Log::info('webhook.crm.recebido', [
            'request_id' => $requestId,
            'event_id' => $data['event_id'],
            'payload' => $data,
        ]);

        ProcessCrmDealJob::dispatch($data, $requestId)
            ->onConnection('redis')
            ->onQueue('high');

        Log::info('webhook.crm.enfileirado', [
            'request_id' => $requestId,
            'queue' => 'high',
        ]);

        return response()->json([
            'message' => 'Webhook aceito para processamento.',
            'request_id' => $requestId,
            'event_id' => $data['event_id'],
        ], 202);
    }
}
