<?php

namespace App\Services;

use App\Models\BulkOperation;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\Invoice;
use Exception;

class BulkOperationService
{
    /**
     * Create a bulk invoice generation operation
     */
    public function bulkGenerateInvoices(array $clientIds, array $invoiceData, int $userId, ?int $teamId = null): BulkOperation
    {
        return BulkOperation::create(
            [
                'user_id' => $userId,
                'team_id' => $teamId,
                'type' => 'invoice_generation',
                'parameters' => [
                    'client_ids' => $clientIds,
                    'invoice_data' => $invoiceData,
                ],
                'total_items' => count($clientIds),
                'status' => 'pending',
            ]
        );
    }

    /**
     * Process bulk invoice generation
     */
    public function processBulkInvoiceGeneration(BulkOperation $operation): void
    {
        $operation->markAsProcessing();

        try {
            $clientIds = $operation->parameters['client_ids'];
            $invoiceData = $operation->parameters['invoice_data'];

            foreach ($clientIds as $clientId) {
                try {
                    // Invoices belong to a customer and need a unique invoice_number;
                    // the per-item number is overridable by explicit $invoiceData.
                    Invoice::create(
                        array_merge(
                            ['invoice_number' => 'INV-'.strtoupper(uniqid())],
                            $invoiceData,
                            [
                                'customer_id' => $clientId,
                            ]
                        )
                    );

                    $operation->incrementProcessed();
                } catch (Exception) {
                    $operation->incrementFailed();
                }
            }

            $operation->markAsCompleted();
        } catch (Exception $e) {
            $operation->markAsFailed($e->getMessage());
        }
    }

    /**
     * Export clients data
     */
    public function exportClients(array $filters, int $userId, ?int $teamId = null): BulkOperation
    {
        $operation = BulkOperation::create(
            [
                'user_id' => $userId,
                'team_id' => $teamId,
                'type' => 'data_export',
                'parameters' => [
                    'filters' => $filters,
                    'entity' => 'clients',
                ],
                'status' => 'pending',
            ]
        );

        $this->processDataExport($operation);

        return $operation;
    }

    /**
     * Process data export
     */
    public function processDataExport(BulkOperation $operation): void
    {
        $operation->markAsProcessing();

        try {
            $entity = $operation->parameters['entity'];
            $filters = $operation->parameters['filters'] ?? [];

            if ($entity === 'clients') {
                $data = Client::query();

                // Apply filters
                if (! empty($filters['team_id'])) {
                    $data->where(
                        'team_id',
                        $filters['team_id']
                    );
                }

                $clients = $data->get();
                $operation->update(['total_items' => $clients->count()]);

                // Generate CSV
                $filename = 'exports/clients_'.time().'.csv';
                $path = storage_path('app/'.$filename);

                // Ensure directory exists
                if (! file_exists(dirname($path))) {
                    mkdir(
                        dirname($path),
                        0755,
                        true
                    );
                }

                $file = fopen(
                    $path,
                    'w'
                );

                // Headers
                fputcsv(
                    $file,
                    [
                        'ID',
                        'Name',
                        'Email',
                        'Phone',
                        'Created At',
                    ],
                    escape: '\\'
                );

                foreach ($clients as $client) {
                    fputcsv(
                        $file,
                        array_map(
                            [$this, 'neutralizeCsvValue'],
                            [
                                $client->id,
                                $client->name,
                                $client->email,
                                $client->phone ?? '',
                                $client->created_at,
                            ]
                        ),
                        escape: '\\'
                    );
                    $operation->incrementProcessed();
                }

                fclose($file);
                $operation->update(['result_file' => $filename]);
            }

            $operation->markAsCompleted();
        } catch (Exception $e) {
            $operation->markAsFailed($e->getMessage());
        }
    }

    /**
     * Neutralize CSV formula injection: prefix risky values with an apostrophe
     * so spreadsheet apps treat them as text, not formulas.
     */
    private function neutralizeCsvValue(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Import clients from CSV
     */
    public function importClients(string $filePath, int $userId, ?int $teamId = null): BulkOperation
    {
        $operation = BulkOperation::create(
            [
                'user_id' => $userId,
                'team_id' => $teamId,
                'type' => 'client_import',
                'parameters' => ['file_path' => $filePath],
                'status' => 'pending',
            ]
        );

        $this->processClientImport($operation);

        return $operation;
    }

    /**
     * Process client import
     */
    public function processClientImport(BulkOperation $operation): void
    {
        $operation->markAsProcessing();

        try {
            $filePath = $operation->parameters['file_path'];
            $file = fopen(
                $filePath,
                'r'
            );

            // Skip header row
            $headers = fgetcsv(
                $file,
                escape: '\\'
            );

            // Count total rows
            $totalRows = 0;
            while (fgetcsv(
                $file,
                escape: '\\'
            ) !== false) {
                $totalRows++;
            }
            rewind($file);
            fgetcsv(
                $file,
                escape: '\\'
            ); // Skip header again

            $operation->update(['total_items' => $totalRows]);

            while (($row = fgetcsv(
                $file,
                escape: '\\'
            )) !== false) {
                try {
                    Client::create(
                        [
                            'name' => $row[0] ?? '',
                            'email' => $row[1] ?? '',
                            'phone' => $row[2] ?? null,
                            'team_id' => $operation->team_id,
                        ]
                    );
                    $operation->incrementProcessed();
                } catch (Exception) {
                    $operation->incrementFailed();
                }
            }

            fclose($file);
            $operation->markAsCompleted();
        } catch (Exception $e) {
            $operation->markAsFailed($e->getMessage());
        }
    }

    /**
     * Create email campaign
     */
    public function createEmailCampaign(
        string $name,
        string $subject,
        string $content,
        array $recipientFilters,
        int $createdBy,
        ?int $teamId = null
    ): EmailCampaign {
        return EmailCampaign::create(
            [
                'team_id' => $teamId,
                'created_by' => $createdBy,
                'name' => $name,
                'subject' => $subject,
                'content' => $content,
                'recipient_filters' => $recipientFilters,
                'status' => 'draft',
            ]
        );
    }

    /**
     * Send email campaign
     */
    public function sendEmailCampaign(EmailCampaign $campaign): void
    {
        $campaign->markAsSending();

        try {
            // Get recipients based on filters
            $recipients = $this->getRecipients($campaign->recipient_filters);
            $campaign->update(['total_recipients' => $recipients->count()]);

            foreach ($recipients as $recipient) {
                try {
                    // Send email (implementation depends on mail system)
                    // Mail::to($recipient->email)->send(new CampaignEmail($campaign));

                    $campaign->incrementSent();
                } catch (Exception) {
                    $campaign->incrementFailed();
                }
            }

            $campaign->markAsSent();
        } catch (Exception) {
            // Log error
        }
    }

    /**
     * Get recipients based on filters
     */
    protected function getRecipients(array $filters)
    {
        $query = Client::query();

        if (! empty($filters['team_id'])) {
            $query->where(
                'team_id',
                $filters['team_id']
            );
        }

        if (! empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        return $query->get();
    }
}
