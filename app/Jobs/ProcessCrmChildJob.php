<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\CrmMetadata;
use App\Models\Deal;
use App\Models\DealCustomField;
use App\Models\LineItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCrmChildJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public array $backoff = [2, 5];

    public function __construct(
        private int $dealId,
        private string $type,
        private array $data,
        private string $requestId
    ) {
    }

    public function tags(): array
    {
        return [
            'crm:' . $this->type,
            'request:' . $this->requestId,
        ];
    }

    public function handle(): void
    {
        Log::info('job.crm.filho.inicio', [
            'request_id' => $this->requestId,
            'type' => $this->type,
            'deal_id' => $this->dealId,
            'attempt' => $this->attempts(),
        ]);

        try {
            DB::transaction(function () {
                $deal = Deal::findOrFail($this->dealId);

                switch ($this->type) {
                    case 'contacts':
                        $this->saveContacts($deal);
                        break;

                    case 'line_items':
                        $this->saveLineItems($deal);
                        break;

                    case 'activities':
                        $this->saveActivities($deal);
                        break;

                    case 'extras':
                        $this->saveExtras($deal);
                        break;

                    default:
                        throw new \InvalidArgumentException(
                            'Tipo de processamento invalido.'
                        );
                }
            }, 3);

            Log::info('job.crm.filho.sucesso', [
                'request_id' => $this->requestId,
                'type' => $this->type,
                'deal_id' => $this->dealId,
            ]);

        } catch (Throwable $e) {
            Log::error('job.crm.filho.erro', [
                'request_id' => $this->requestId,
                'type' => $this->type,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function saveContacts(Deal $deal): void
    {
        foreach ($this->data as $data) {
            $contact = Contact::updateOrCreate(
                ['external_id' => $data['external_id']],
                [
                    'organization_id' => $deal->organization_id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]
            );

            $deal->contacts()->syncWithoutDetaching([
                $contact->id => [
                    'role' => $data['role'] ?? null,
                    'is_primary' => $data['is_primary'] ?? false,
                ],
            ]);
        }
    }

    private function saveLineItems(Deal $deal): void
    {
        foreach ($this->data as $data) {
            LineItem::updateOrCreate(
                [
                    'deal_id' => $deal->id,
                    'external_id' => $data['external_id'],
                ],
                [
                    'name' => $data['name'],
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                ]
            );
        }
    }

    private function saveActivities(Deal $deal): void
    {
        foreach ($this->data as $data) {
            Activity::updateOrCreate(
                [
                    'deal_id' => $deal->id,
                    'external_id' => $data['external_id'],
                ],
                [
                    'type' => $data['type'],
                    'subject' => $data['subject'],
                    'description' => $data['description'] ?? null,
                    'occurred_at' => $data['occurred_at'] ?? null,
                ]
            );
        }
    }

    private function saveExtras(Deal $deal): void
    {
        foreach ($this->data['custom_fields'] as $key => $value) {
            DealCustomField::updateOrCreate(
                [
                    'deal_id' => $deal->id,
                    'field_key' => $key,
                ],
                [
                    'field_value' => $value,
                ]
            );
        }

        foreach ($this->data['metadata'] as $key => $value) {
            CrmMetadata::updateOrCreate(
                [
                    'deal_id' => $deal->id,
                    'meta_key' => $key,
                ],
                [
                    'meta_value' => $value,
                ]
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('job.crm.filho.falha_final', [
            'request_id' => $this->requestId,
            'type' => $this->type,
            'error' => $exception?->getMessage(),
        ]);
    }
}
