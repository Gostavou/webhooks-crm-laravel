<?php

namespace App\Http\Services;

use App\Jobs\SaveLeadDataJob;
use App\Models\Company;
use App\Models\CustomField;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WebhookService
{
    public function handle(array $data): void
    {
        $requestId = $data['_request_id'] ?? null;
        $payload = $data['data'] ?? $data;
        $lead = $payload['lead'] ?? null;

        if (!is_array($lead)) {
            throw new InvalidArgumentException(
                'Dados do lead não encontrados.'
            );
        }

        Log::info('job.lead.recebido', [
            'request_id' => $requestId,
            'email' => $lead['email'] ?? null,
        ]);

        $lead['_request_id'] = $requestId;

        SaveLeadDataJob::dispatch($lead);

        Log::info('job.lead.enfileirado', [
            'request_id' => $requestId,
            'queue' => 'save-lead',
        ]);
    }

    public function findOrCreateLead(array $lead): Lead
    {
        $requestId = $lead['_request_id'] ?? null;

        Log::info('job.lead.persistencia.inicio', [
            'request_id' => $requestId,
        ]);

        $createdLead = DB::transaction(function () use ($lead) {
            $company = Company::firstOrCreate([
                'name' => $lead['company'],
            ]);

            $values = [
                'first_name' => $lead['first_name'],
                'last_name' => $lead['last_name'],
                'email' => $lead['email'],
                'phone' => $lead['phone'] ?? null,
                'company_id' => $company->id,
                'job_title' => $lead['job_title'] ?? null,
                'source' => $lead['source'],
                'status' => $lead['status'],
            ];

            $createdLead = Lead::updateOrCreate(
                ['email' => $lead['email']],
                $values
            );

            $customFields = $lead['custom_fields'] ?? [];

            if (
                !empty($customFields['segment']) &&
                !empty($customFields['interesse'])
            ) {
                $customField = CustomField::firstOrCreate([
                    'segment' => $customFields['segment'],
                    'interest' => $customFields['interesse'],
                ]);

                $createdLead->customFields()
                    ->syncWithoutDetaching([$customField->id]);
            }

            return $createdLead;
        }, 3);

        Log::info('job.lead.persistencia.sucesso', [
            'request_id' => $requestId,
            'lead_id' => $createdLead->id,
        ]);

        return $createdLead;
    }
}
