<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessLeadData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->input('data');

        if (!is_array($payload)) {
            $payload = $request->all();
        }

        $validator = Validator::make($payload, [
            'user' => ['sometimes', 'array'],
            'user.test' => ['sometimes', 'boolean'],
            'user.timestamp' => ['sometimes', 'date'],

            'lead' => ['required', 'array'],
            'lead.first_name' => ['required', 'string', 'max:255'],
            'lead.last_name' => ['required', 'string', 'max:255'],
            'lead.email' => ['required', 'email', 'max:255'],
            'lead.phone' => ['nullable', 'string', 'max:255'],
            'lead.company' => ['required', 'string', 'max:255'],
            'lead.job_title' => ['nullable', 'string', 'max:255'],
            'lead.source' => ['required', 'string', 'max:255'],
            'lead.status' => ['required', 'string', 'max:255'],
            'lead.custom_fields' => ['nullable', 'array'],
            'lead.custom_fields.segment' => ['sometimes', 'string'],
            'lead.custom_fields.interesse' => ['sometimes', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $requestId = (string) Str::uuid();

        Log::info('webhook.recebido', [
            'request_id' => $requestId,
            'payload' => $payload,
        ]);

        ProcessLeadData::dispatch([
            ...$payload,
            '_request_id' => $requestId,
        ])->onConnection('redis')->onQueue('default');

        Log::info('webhook.enfileirado', [
            'request_id' => $requestId,
            'queue' => 'default',
        ]);

        return response()->json([
            'message' => 'Webhook aceito para processamento.',
            'request_id' => $requestId,
        ], 202);
    }
}
