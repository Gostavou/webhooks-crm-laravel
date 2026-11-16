<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Models\Organization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCrmDealJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public array $backoff = [2, 5];

    public function __construct(
        private array $payload,
        private string $requestId
    ) {
    }

    public function handle(): void
    {
        Log::info('job.crm.principal.inicio', [
            'request_id' => $this->requestId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $deal = DB::transaction(function () {
                $org = $this->payload['organization'];
                $data = $this->payload['deal'];

                $organization = Organization::updateOrCreate(
                    ['external_id' => $org['external_id']],
                    [
                        'name' => $org['name'],
                        'industry' => $org['industry'] ?? null,
                    ]
                );

                return Deal::updateOrCreate(
                    ['external_id' => $data['external_id']],
                    [
                        'organization_id' => $organization->id,
                        'title' => $data['title'],
                        'value' => $data['value'],
                        'stage' => $data['stage'],
                        'pipeline' => $data['pipeline'],
                        'currency' => $data['currency'],
                    ]
                );
            }, 3);

            Log::info('job.crm.principal.persistido', [
                'request_id' => $this->requestId,
                'deal_id' => $deal->id,
            ]);

            ProcessCrmChildJob::dispatch(
                $deal->id,
                'contacts',
                $this->payload['contacts'],
                $this->requestId
            )->onConnection('redis')->onQueue('default');

            ProcessCrmChildJob::dispatch(
                $deal->id,
                'line_items',
                $this->payload['line_items'],
                $this->requestId
            )->onConnection('redis')->onQueue('bulk');

            ProcessCrmChildJob::dispatch(
                $deal->id,
                'activities',
                $this->payload['activities'],
                $this->requestId
            )->onConnection('redis')->onQueue('bulk');

            ProcessCrmChildJob::dispatch(
                $deal->id,
                'extras',
                [
                    'custom_fields' => $this->payload['custom_fields'],
                    'metadata' => $this->payload['metadata'],
                ],
                $this->requestId
            )->onConnection('redis')->onQueue('default');

            Log::info('job.crm.subjobs.enfileirados', [
                'request_id' => $this->requestId,
                'deal_id' => $deal->id,
                'total_jobs' => 4,
            ]);

        } catch (Throwable $e) {
            Log::error('job.crm.principal.erro', [
                'request_id' => $this->requestId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('job.crm.principal.falha_final', [
            'request_id' => $this->requestId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
